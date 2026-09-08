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
     data-suggestions='@json($wisdomSuggestions)'
     data-uc-list-url="{{ route('resort.chat.list') }}"
     data-uc-new-chat-url="{{ route('resort.chat.newChat') }}"
     data-uc-group-candidates-url="{{ route('resort.chat.groupCandidates') }}"
     data-uc-create-group-url="{{ route('resort.chat.createGroup') }}"
     data-uc-view-url-tpl="{{ route('resort.chat.view', ['type' => '__type__', 'type_id' => '__id__']) }}"
     data-uc-send-url="{{ route('resort.chat.send') }}"
     data-uc-mark-read-url="{{ route('resort.chat.markRead') }}"
     data-uc-new-member-url-tpl="{{ route('resort.chat.newEmployeeList', ['type_id' => '__id__']) }}"
     data-uc-add-member-url-tpl="{{ route('resort.chat.addMember', ['type_id' => '__id__']) }}"
     data-uc-remove-member-url-tpl="{{ route('resort.chat.removeMember', ['type_id' => '__id__']) }}"
     data-uc-update-group-url-tpl="{{ route('resort.chat.updateGroup', ['type_id' => '__id__']) }}"
     data-uc-delete-group-url-tpl="{{ route('resort.chat.deleteGroup', ['type_id' => '__id__']) }}"
     data-uc-my-id="{{ auth()->guard('resort-admin')->id() ?? 0 }}">

    <!-- Dim scrim behind the floating panels — the Dynamic Island that opens
         them lives in header.blade.php; this partial owns the panels + the
         scrim that dismisses them (click closes whichever is open). -->
    <div class="wai-scrim" id="wai-scrim"></div>

    <!-- Live-activity pop-up — a new message/notification arriving while
         everything is closed surfaces here instead of silently updating
         just the badge. Chat gets an inline Reply; notifications are
         dismiss-only. -->
    <div class="ha-wrap" id="ha-wrap" role="status" aria-live="polite">
        <div class="ha-card">
            <div class="ha-main">
                <span class="ha-av" id="haAv"></span>
                <div class="ha-tx"><b id="haName"></b><span id="haMsg"></span></div>
                <button type="button" class="ha-reply" id="haReplyBtn">Reply</button>
                <button type="button" class="ha-x" id="haDismiss" aria-label="Dismiss">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <div class="ha-rr">
                <input type="text" class="ha-input" id="haInput" placeholder="Reply">
                <button type="button" class="ha-send" id="haSend" aria-label="Send">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- WAI panel -->
    <div id="wai-panel" class="wai-float" role="dialog" aria-label="WAI assistant">
        <div class="wai-header">
            <div class="wai-titles">
                <div class="wai-title">WAI <span class="wai-dot"></span></div>
                <div class="wai-subtitle">{{ $wisdomCtx['tier_label'] }}</div>
            </div>
            <div class="wai-header-actions">
                <button type="button" id="wai-clear" class="wai-gbtn" title="Clear conversation"><i class="fa-solid fa-trash-can"></i></button>
                <button type="button" id="wai-close" class="wai-gbtn" title="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>

        <div class="wai-messages" id="wai-messages"></div>

        <div class="wai-suggestions" id="wai-suggestions"></div>

        <div class="wai-input" id="wai-form">
            <textarea id="wai-text" rows="1" placeholder="Ask WAI…" maxlength="2000"></textarea>
            <button type="button" id="wai-send" aria-label="Send" disabled><i class="fa-solid fa-paper-plane"></i></button>
        </div>
        <div class="wai-foot">WAI can make mistakes. Verify important decisions.</div>
    </div>

    <!-- Users chat panel -->
    <div id="uc-panel" class="wai-float" role="dialog" aria-label="Colleague chat">

        <!-- List view -->
        <div id="uc-view-list" class="uc-view">
            <div class="wai-header">
                <div class="wai-titles">
                    <div class="wai-title">Messages</div>
                    <div class="wai-subtitle">{{ $wisdomCtx['user_name'] }}</div>
                </div>
                <div class="wai-header-actions">
                    <button type="button" id="uc-new-group" class="wai-gbtn" title="New group"><i class="fa-solid fa-user-group"></i></button>
                    <button type="button" id="uc-new-chat" class="wai-gbtn" title="New chat"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button type="button" id="uc-list-close" class="wai-gbtn" title="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="uc-search"><div class="uc-search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="uc-list-search" placeholder="Search"></div></div>
            <div class="uc-list" id="uc-conversations"><div class="uc-empty">Loading…</div></div>
        </div>

        <!-- New chat / new group member picker -->
        <div id="uc-view-picker" class="uc-view" style="display:none;">
            <div class="wai-header">
                <button type="button" class="wai-gbtn" id="uc-picker-back"><i class="fa-solid fa-arrow-left"></i></button>
                <div class="wai-titles"><div class="wai-title" id="uc-picker-title">New chat</div></div>
                <div class="wai-header-actions"><button type="button" id="uc-picker-close" class="wai-gbtn" title="Close"><i class="fa-solid fa-xmark"></i></button></div>
            </div>
            <div id="uc-group-name-row" class="uc-search" style="display:none;">
                <input type="text" id="uc-group-name" placeholder="Group name…">
            </div>
            <div class="uc-search"><input type="text" id="uc-picker-search" placeholder="Search people…"></div>
            <div class="uc-list" id="uc-picker-list"></div>
            <div id="uc-group-create-row" class="uc-create-row" style="display:none;">
                <span id="uc-group-selected-count">0 selected</span>
                <button type="button" id="uc-group-create-btn" class="uc-primary-btn">Create group</button>
            </div>
        </div>

        <!-- Conversation thread -->
        <div id="uc-view-thread" class="uc-view" style="display:none;">
            <div class="wai-header">
                <button type="button" class="wai-gbtn" id="uc-thread-back"><i class="fa-solid fa-arrow-left"></i></button>
                <div class="wai-avatar" id="uc-thread-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="wai-titles">
                    <div class="wai-title" id="uc-thread-title">&nbsp;</div>
                    <div class="wai-subtitle" id="uc-thread-subtitle">&nbsp;</div>
                </div>
                <div class="wai-header-actions">
                    <button type="button" id="uc-thread-info" class="wai-gbtn" title="Group info"><i class="fa-solid fa-circle-info"></i></button>
                </div>
            </div>
            <div class="wai-messages" id="uc-messages"></div>
            <div class="wai-input uc-thread-input" id="uc-send-form">
                <label class="uc-attach-btn" title="Attach a photo or file">
                    <input type="file" id="uc-attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" hidden>
                    <i class="fa-solid fa-paperclip"></i>
                </label>
                <textarea id="uc-text" rows="1" placeholder="Message" maxlength="2000"></textarea>
                <button type="button" id="uc-send" aria-label="Send" disabled><i class="fa-solid fa-paper-plane"></i></button>
            </div>
            <div class="uc-attach-preview" id="uc-attach-preview" style="display:none;"></div>
        </div>

        <!-- Group info / manage -->
        <div id="uc-view-group-info" class="uc-view" style="display:none;">
            <div class="wai-header">
                <button type="button" class="wai-gbtn" id="uc-info-back"><i class="fa-solid fa-arrow-left"></i></button>
                <div class="wai-titles"><div class="wai-title">Group info</div></div>
                <div class="wai-header-actions"><button type="button" id="uc-info-close" class="wai-gbtn" title="Close"><i class="fa-solid fa-xmark"></i></button></div>
            </div>
            <div class="uc-group-info-body" id="uc-group-info-body"></div>
        </div>
    </div>
</div>

<style>
:root {
    /* Locally-scoped extras the global design tokens don't define — the
       rest of this file uses the app's real tokens (--teal, --teal-soft,
       --lime, --ink, --muted, --faint, --line, --line-2, --neutral-bg)
       directly, per the finalized minimal-palette spec. */
    --wai-g1: #3A4145;
    --wai-g4: #C7CDCF;
    --wai-g6: #F7F8F8;
    --wai-spring: cubic-bezier(.34, 1.56, .64, 1);
}
#wai-root * { box-sizing: border-box; }
/* Only force the app font on form controls — NOT on <i> icons, or we'd
   override Font Awesome's icon font and turn every glyph into a tofu box. */
#wai-root button, #wai-root input, #wai-root textarea { font-family: inherit; }

/* ================= Dim scrim behind the floating panels ================= */
.wai-scrim {
    position: fixed; inset: 0; background: rgba(3,20,24,.14);
    opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 99989;
}
.wai-scrim.show { opacity: 1; pointer-events: auto; }

/* ================= Live-activity pop-up =================
   Surfaces a brand-new message/notification while nothing is open — a
   charcoal pill fading+scaling in near the top of the page, centered.
   The reference ported this off the app's existing .serch-box reveal, but
   .page-hedding (the per-page title row it would nest inside) is
   copy-pasted across 400+ view files, not a shared partial — so instead
   of touching every page, this is a self-contained fixed overlay with the
   same timing/material, wired from this one global partial. */
.ha-wrap {
    /* 100px matches the app's own .serch-box reveal (default.css) — the
       existing "safe zone below the 40px-tall nav row" value already used
       for the search reveal this mechanism was modeled on. The reference's
       84px left only ~5px between the Island's bottom edge (79px) and the
       popup, which its own box-shadow blur (0 12px 34px) bled upward into. */
    position: fixed; left: 50%; top: 100px; z-index: 99988;
    width: min(480px, calc(100% - 16px));
    transform: translateX(-50%) translateY(-6px) scale(.97);
    opacity: 0; pointer-events: none;
    transition: opacity .28s ease, transform .38s cubic-bezier(.22,.61,.36,1);
}
.ha-wrap.show { opacity: 1; transform: translateX(-50%); pointer-events: auto; }
.ha-card { background: #06181c; border-radius: 16px; box-shadow: 0 12px 34px rgba(0,0,0,.34); overflow: hidden; }
.ha-main { display: flex; align-items: center; gap: 11px; padding: 9px 9px 9px 14px; }
.ha-av {
    width: 36px; height: 36px; flex: none; border-radius: 50%; position: relative; overflow: hidden;
    background: rgba(255,255,255,.1); color: #fff; font-size: 12px; font-weight: 600;
    display: flex; align-items: center; justify-content: center;
}
.ha-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.ha-tx { flex: 1; min-width: 0; line-height: 1.3; }
.ha-tx b { font-size: 13.5px; font-weight: 600; color: #fff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ha-tx span { font-size: 12px; color: rgba(255,255,255,.68); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ha-reply { background: var(--lime); color: #06181c; border: none; border-radius: 11px; padding: 8px 15px; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; flex: none; }
.ha-reply:hover { filter: brightness(.95); }
.ha-x {
    width: 32px; height: 32px; flex: none; border-radius: 50%; background: transparent; border: none;
    color: rgba(255,255,255,.5); cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.ha-x:hover { background: rgba(255,255,255,.1); color: #fff; }
.ha-wrap.notif .ha-reply { display: none; }
.ha-rr { display: none; align-items: center; gap: 9px; padding: 2px 10px 10px 14px; }
.ha-wrap.replying .ha-reply { display: none; }
.ha-wrap.replying .ha-rr { display: flex; }
.ha-input { flex: 1; min-width: 0; border: none; background: rgba(255,255,255,.1); border-radius: 11px; padding: 9px 13px; font: inherit; font-size: 13px; outline: none; color: #fff; }
.ha-input::placeholder { color: rgba(255,255,255,.5); }
.ha-send {
    width: 36px; height: 36px; flex: none; border-radius: 50%; background: var(--lime); border: none;
    color: #06181c; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
@media (prefers-reduced-motion: reduce) {
    .ha-wrap { transition: none; }
}

/* ================= Float shell shared by #wai-panel / #uc-panel =================
   Minimal solid material (white body + tinted header band) — glass is
   reserved for the transient notification drawer only. Floats top-right
   from the Dynamic Island in header.blade.php, spring pop-in. */
.wai-float {
    position: fixed; top: 78px; right: 20px; z-index: 99991;
    width: 390px; max-width: calc(100vw - 32px);
    height: 600px; max-height: calc(100vh - 100px);
    background: #fff; border-radius: 22px; overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 1px 2px rgba(1,70,83,.05), 0 24px 60px rgba(1,70,83,.20);
    opacity: 0; pointer-events: none;
    transform: scale(.96) translateY(-6px); transform-origin: top right;
    transition: opacity .2s, transform .28s var(--wai-spring);
}
.wai-float.wai-open { opacity: 1; transform: none; pointer-events: auto; }

/* Header band — the only structural tint, on every screen of both panels */
.wai-header {
    display: flex; align-items: center; gap: 11px; flex: none;
    padding: 18px 18px 14px;
    background: var(--teal-soft); border-bottom: 1px solid var(--line);
}
.wai-titles { flex: 1; min-width: 0; line-height: 1.3; }
.wai-title { font-size: 16px; font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 8px; }
.wai-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--lime); flex: none; }
.wai-subtitle { font-size: 11.5px; color: var(--faint); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wai-header-actions { display: flex; gap: 4px; flex: none; }
.wai-gbtn {
    width: 30px; height: 30px; flex: none; border-radius: 9px;
    background: transparent; border: none; color: var(--muted); cursor: pointer;
    display: grid; place-items: center; font-size: 13px; transition: background .15s, color .15s;
}
.wai-gbtn:hover { background: rgba(1,70,83,.07); color: var(--teal); }

/* Contact avatar — thread header only now (list/picker rows use .crow .av,
   member rows keep their own .uc-conv-avatar). */
.wai-avatar {
    position: relative;
    width: 32px; height: 32px; flex: none; border-radius: 50%;
    background: var(--teal-soft); color: var(--teal); font-size: 11px; font-weight: 600;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.wai-bot-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }

/* ================= WAI panel body ================= */
.wai-messages { flex: 1; overflow-y: auto; padding: 24px 20px 6px; display: flex; flex-direction: column; gap: 12px; background: #fff; }
.wai-messages::-webkit-scrollbar { width: 7px; }
.wai-messages::-webkit-scrollbar-thumb { background: var(--line); border-radius: 8px; }

.wai-welcome .wgreet { font-size: 23px; font-weight: 600; color: var(--ink); letter-spacing: -.02em; }
.wai-welcome .wsub { font-size: 13px; color: var(--muted); margin-top: 6px; line-height: 1.5; max-width: 262px; }

.wai-suggestions { padding: 0 20px 16px; flex: none; }
.wai-suggestions:empty { display: none; }
.slabel { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: var(--faint); margin: 0 2px 10px; }
.scard { background: var(--teal-soft); border: 1px solid var(--line); border-radius: 15px; overflow: hidden; }
.prow { display: flex; align-items: center; gap: 11px; padding: 13px 14px; cursor: pointer; color: var(--wai-g1); font-size: 13px; border-top: 1px solid var(--line); transition: background .12s; }
.prow:first-child { border-top: none; }
.prow:hover { background: #fff; }
.prow-a { flex: 1; line-height: 1.35; }
.prow-c { color: var(--wai-g4); flex: none; transition: color .12s, transform .12s; }
.prow:hover .prow-c { color: var(--teal); transform: translateX(2px); }

/* Message bubbles — shared by the WAI panel and the colleague thread */
.wai-row { display: flex; gap: 9px; align-items: flex-end; max-width: 100%; }
.wai-row.wai-user { flex-direction: row-reverse; }
.wai-bubble {
    padding: 10px 13px; border-radius: 16px; font-size: 13.5px; line-height: 1.5;
    max-width: 80%; word-wrap: break-word; overflow-wrap: anywhere;
}
.wai-bot .wai-bubble { background: var(--wai-g6); color: var(--ink); border-bottom-left-radius: 6px; }
.wai-user .wai-bubble { background: var(--teal); color: #fff; border-bottom-right-radius: 6px; }
.wai-mini-avatar {
    width: 28px; height: 28px; border-radius: 50%; flex: 0 0 28px; font-size: 12px;
    display: flex; align-items: center; justify-content: center; color: #fff; overflow: hidden; position: relative;
}
.wai-bot .wai-mini-avatar { background: var(--teal); }
.wai-user .wai-mini-avatar { background: var(--neutral-bg); color: var(--muted); }
.uc-msg-avatar-ghost { visibility: hidden; }
.wai-bubble p { margin: 0 0 8px; } .wai-bubble p:last-child { margin-bottom: 0; }
.wai-bubble ul, .wai-bubble ol { margin: 6px 0; padding-left: 20px; }
.wai-bubble li { margin: 2px 0; }
.wai-bubble code { background: rgba(1,70,83,.08); padding: 1px 5px; border-radius: 5px; font-size: 12.5px; }
.wai-bubble pre { background: var(--ink); color: #f3f0ff; padding: 10px; border-radius: 10px; overflow-x: auto; font-size: 12.5px; }
.wai-bubble pre code { background: none; padding: 0; }
.wai-bubble table { border-collapse: collapse; width: 100%; font-size: 12.5px; margin: 6px 0; }
.wai-bubble th, .wai-bubble td { border: 1px solid var(--line); padding: 4px 7px; text-align: left; }
.wai-bubble a { color: var(--teal-2); }
.uc-bt { font-size: 10px; margin-top: 4px; opacity: .65; }
.wai-user .uc-bt { text-align: right; }

/* Typing indicator */
.wai-typing { display: flex; gap: 4px; padding: 4px 2px; }
.wai-typing span { width: 7px; height: 7px; border-radius: 50%; background: var(--faint); animation: wai-bounce 1.2s infinite; }
.wai-typing span:nth-child(2) { animation-delay: .15s; } .wai-typing span:nth-child(3) { animation-delay: .3s; }
@keyframes wai-bounce { 0%,60%,100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-5px); opacity: 1; } }

/* Input — shared pill bar for both panels */
.wai-input {
    display: flex; align-items: flex-end; gap: 8px; flex: none;
    margin: 12px 16px; padding: 7px 7px 7px 16px;
    border: 1px solid var(--line); border-radius: 16px;
    transition: border-color .15s ease;
}
.wai-input:focus-within { border-color: var(--wai-g4); }
#wai-text, #uc-text {
    flex: 1; resize: none; border: none; background: transparent;
    font: inherit; font-size: 13.5px; outline: none; color: var(--ink); line-height: 1.4;
    padding: 6px 0; max-height: 80px;
}
#wai-text::placeholder, #uc-text::placeholder { color: var(--wai-g4); }
#wai-send, #uc-send {
    width: 36px; height: 36px; flex: 0 0 36px; border: none; border-radius: 50%; cursor: pointer;
    background: var(--lime); color: #06181c;
    display: flex; align-items: center; justify-content: center;
    transition: transform .15s ease;
}
#wai-send i, #uc-send i { transform: translateX(-1px); }
#wai-send:hover, #uc-send:hover { transform: scale(1.05); }
#wai-send:active, #uc-send:active { transform: scale(.96); }
#wai-send:disabled, #uc-send:disabled { background: var(--wai-g6); color: var(--wai-g4); cursor: default; transform: none; }
.wai-foot { text-align: center; font-size: 10px; color: var(--wai-g4); padding: 0 16px 14px; flex: none; }

@media (max-width: 480px) {
    .wai-float { top: 8px; right: 8px; width: calc(100vw - 16px); height: calc(100vh - 90px); }
}

/* ================= Messages panel (#uc-panel) ================= */
.uc-view { display: flex; flex-direction: column; height: 100%; }

.uc-search { padding: 10px 14px; flex: none; }
.uc-search-box { display: flex; align-items: center; gap: 8px; background: var(--line-2); border: 1px solid var(--line); border-radius: 11px; padding: 8px 12px; }
.uc-search-box i { color: var(--faint); font-size: 12.5px; flex: none; }
.uc-search-box input { border: none; background: none; outline: none; font-family: inherit; font-size: 13.5px; color: var(--ink); width: 100%; padding: 0; }
.uc-search-box input::placeholder { color: var(--faint); }
#uc-picker-search, #uc-group-name {
    width: 100%; border: 1px solid var(--line); border-radius: 12px; padding: 9px 12px;
    font-size: 13.5px; outline: none; background: #fff; color: var(--ink);
}
#uc-picker-search:focus, #uc-group-name:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(1,70,83,.1); }

.uc-list { flex: 1; overflow-y: auto; padding: 4px 8px; }
.uc-empty { text-align: center; color: var(--faint); font-size: 13px; padding: 30px 10px; }

/* Conversation rows — renderConversations() output */
.crow { display: flex; align-items: center; gap: 12px; padding: 11px 10px; border-radius: 12px; cursor: pointer; }
.crow:hover { background: var(--wai-g6); }
.crow .av {
    width: 40px; height: 40px; flex: none; border-radius: 50%; overflow: hidden; position: relative;
    background: var(--teal-soft); color: var(--teal); font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center;
}
.crow .cw { min-width: 0; flex: 1; }
.crow .r1 { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
.crow .cn { font-size: 13.5px; font-weight: 500; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.crow.unread .cn { font-weight: 600; }
.crow .ct { font-size: 10.5px; color: var(--wai-g4); flex: none; }
.crow .r2 { display: flex; align-items: center; gap: 8px; margin-top: 2px; }
.crow .cs { font-size: 12px; color: var(--faint); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.crow.unread .cs { color: var(--muted); }
.crow .cs.crow-cs-none { font-style: italic; }
.crow .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--teal); flex: none; }

/* Picker (new chat / new group / add members) — unchanged structure, retoned */
.uc-picker-item { display: flex; align-items: center; gap: 11px; padding: 9px 8px; border-radius: 12px; cursor: pointer; transition: background .12s; }
.uc-picker-item:hover { background: var(--wai-g6); }
.uc-picker-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--teal); }
.uc-picker-name { font-size: 13.5px; color: var(--ink); flex: 1; }
.uc-create-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #fff; border-top: 1px solid var(--line); }
.uc-create-row span { font-size: 12px; color: var(--faint); }
.uc-primary-btn { background: var(--teal); color: #fff; border: none; border-radius: 11px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }
.uc-primary-btn:disabled { opacity: .5; cursor: not-allowed; }

.uc-thread-input { align-items: center; }
.uc-attach-btn { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 11px; color: var(--muted); cursor: pointer; flex: 0 0 38px; }
.uc-attach-btn:hover { background: var(--wai-g6); }
.uc-attach-preview { display: flex; align-items: center; gap: 8px; padding: 0 14px 10px; font-size: 12px; color: var(--muted); }
.uc-attach-preview img { width: 34px; height: 34px; object-fit: cover; border-radius: 7px; }
.uc-attach-preview .uc-attach-remove { cursor: pointer; color: var(--error); margin-left: auto; }

.uc-bubble-attachment img { max-width: 180px; border-radius: 10px; display: block; margin-top: 4px; cursor: pointer; }
.uc-bubble-attachment a { font-size: 12.5px; }

.uc-group-info-body { flex: 1; overflow-y: auto; padding: 14px; }
.uc-group-info-body h5 { margin: 14px 0 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--faint); }
.uc-member-row { display: flex; align-items: center; gap: 10px; padding: 7px 4px; }
.uc-member-row .uc-conv-avatar {
    width: 32px; height: 32px; flex: 0 0 32px; font-size: 12px; border-radius: 50%; overflow: hidden; position: relative;
    background: var(--neutral-bg); display: flex; align-items: center; justify-content: center; color: #fff;
}
.uc-member-row span { flex: 1; font-size: 13px; color: var(--ink); }
.uc-member-row button { background: none; border: none; color: var(--error); cursor: pointer; font-size: 12px; }
.uc-info-action { display: block; width: 100%; text-align: left; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 9px 12px; margin-bottom: 8px; font-size: 13px; color: var(--ink); cursor: pointer; }
.uc-info-action.uc-danger { color: var(--error); border-color: var(--error-bg); }
.uc-info-name-edit { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 8px 10px; font-size: 14px; margin-bottom: 10px; }

/* ---- Colleague chat (#uc-panel) — teal redesign -----------------------
   Scoped entirely under #uc-panel so the Wisdom AI assistant panel above
   (which shares several base classes: .wai-header, .wai-row, .wai-bubble,
   .wai-input, .wai-mini-avatar) keeps its own existing look untouched.
   No presence dots — the only "presence" field the API returns
   (last_seen) is a ResortAdmin row's updated_at, not real activity
   tracking, so a dot/"Active now" text driven by it would just be wrong. */
#uc-panel .wai-header { background: var(--teal); }
#uc-panel .uc-back,
#uc-panel .wai-header-actions button { background: rgba(255,255,255,.14); }
#uc-panel .uc-back:hover,
#uc-panel .wai-header-actions button:hover { background: rgba(255,255,255,.26); }

/* Search */
#uc-panel .uc-search-box { display: flex; align-items: center; gap: 8px; background: var(--line-2); border: 1px solid var(--line); border-radius: 11px; padding: 8px 12px; }
#uc-panel .uc-search-box i { color: var(--faint); font-size: 12.5px; flex: none; }
#uc-panel .uc-search-box input { border: none; background: none; outline: none; font-family: inherit; font-size: 13.5px; color: var(--ink); width: 100%; padding: 0; }
#uc-panel .uc-search-box input::placeholder { color: var(--faint); }

/* Photo-first avatars with initials fallback (list rows via .crow .av,
   picker, thread header, member rows, and message-bubble sender avatars
   all funnel through the same ucAvatarInner() JS helper into this markup). */
#uc-panel .uc-conv-avatar, #uc-panel .uc-picker-avatar, #uc-panel #uc-thread-avatar, #uc-panel .wai-mini-avatar.uc-msg-avatar {
    position: relative; background: var(--neutral-bg);
}
/* z-index keeps the fallback initials (below) from painting over a
   successfully loaded photo — onerror only removes the <img> on a 404, so
   without this the fallback (inserted alongside the img regardless) sat on
   top of it in normal DOM stacking order. .crow .av wasn't in this list
   before — the conversation LIST rows were still exposed to the same
   overlap bug already fixed here for picker/thread/member/bubble avatars. */
.crow .av img, #uc-panel .uc-conv-avatar img, #uc-panel .uc-picker-avatar img, #uc-panel #uc-thread-avatar img, #uc-panel .wai-mini-avatar.uc-msg-avatar img, .uc-member-row .uc-conv-avatar img {
    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;
}
.uc-picker-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex: 0 0 42px; overflow: hidden; position: relative;
    background: var(--neutral-bg); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px;
}
.uc-av-fallback {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 600; font-size: inherit; z-index: 0;
}

#uc-messages { background: #fff; }
.uc-daysep { align-self: center; font-size: 10.5px; color: var(--wai-g4); padding: 2px 0 6px; margin: 4px 0; }
.uc-thread-empty { margin: auto; text-align: center; padding: 20px; color: var(--muted); }
.uc-thread-empty-ic {
    width: 44px; height: 44px; border-radius: 50%; background: var(--teal-soft); color: var(--teal);
    display: flex; align-items: center; justify-content: center; font-size: 18px; margin: 0 auto 10px;
}
.uc-thread-empty-t { font-size: 13.5px; font-weight: 600; color: var(--ink); }
.uc-thread-empty-s { font-size: 12px; color: var(--muted); margin-top: 3px; }

@media (prefers-reduced-motion: reduce) {
    .wai-scrim, .wai-float, .wai-gbtn, #wai-send, #uc-send, .prow, .prow-c, .crow {
        transition: none;
    }
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

    var dock     = document.getElementById('waiDock'); // retired launcher — guarded below, may be null
    var scrim    = document.getElementById('wai-scrim');
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
    // Current logged-in user's profile picture (falls back to the configured
    // default placeholder URL when none is set), so the user bubble shows their
    // avatar instead of a generic icon.
    var WAI_USER_AVATAR = "{{ Common::getResortUserPicture(auth()->guard('resort-admin')->id() ?? 0) }}";
    function botAvatar() { return '<div class="wai-mini-avatar"><img src="' + WAI_BOT_ICON + '" class="wai-bot-img" alt="WAI"></div>'; }
    function userAvatar() {
        return WAI_USER_AVATAR
            ? '<div class="wai-mini-avatar"><img src="' + WAI_USER_AVATAR + '" class="wai-bot-img" alt="You"></div>'
            : '<div class="wai-mini-avatar"><i class="fa-solid fa-user"></i></div>';
    }

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
                '<div class="wgreet">Hi ' + escapeHtml(USER_NAME.split(' ')[0]) + '</div>' +
                '<div class="wsub">Ask me anything about your people, leave, or payroll.</div>' +
            '</div>';
    }

    function renderSuggestions() {
        sugWrap.innerHTML = '';
        if (!SUGGESTIONS.length) return;
        var card = document.createElement('div');
        card.className = 'scard';
        SUGGESTIONS.forEach(function (s) {
            var row = document.createElement('div');
            row.className = 'prow';
            row.innerHTML = '<span class="prow-a"></span><svg class="prow-c" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6l6 6-6 6"/></svg>';
            row.querySelector('.prow-a').textContent = s;
            row.addEventListener('click', function () { if (!busy) { input.value = s; sendMessage(); } });
            card.appendChild(row);
        });
        sugWrap.innerHTML = '<div class="slabel">Suggested</div>';
        sugWrap.appendChild(card);
    }
    function toggleSuggestions(show) { sugWrap.style.display = show ? 'block' : 'none'; }

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
        .finally(function () { busy = false; sendBtn.disabled = !input.value.trim(); input.focus(); });
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
        var other = document.getElementById('uc-panel');
        if (other) other.classList.remove('wai-open'); // only one panel open at a time
        panel.classList.add('wai-open');
        if (dock) dock.classList.add('wdn-hide');
        if (scrim) scrim.classList.add('show');
        loadHistory(); setTimeout(function () { input.focus(); }, 250);
    }
    function closePanel() {
        panel.classList.remove('wai-open');
        if (dock) dock.classList.remove('wdn-hide');
        if (scrim) scrim.classList.remove('show');
    }
    if (scrim) scrim.addEventListener('click', function () { if (panel.classList.contains('wai-open')) closePanel(); });

    function autoGrow() {
        input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 110) + 'px';
        sendBtn.disabled = !input.value.trim();
    }

    // The dock (see the second script block below) opens this panel
    // indirectly — it calls openPanel() via this event once the user
    // actually picks "Ask WAI" from the notch panel.
    document.addEventListener('wai:open-ai', openPanel);
    closeBtn.addEventListener('click', closePanel);
    clearBtn.addEventListener('click', clearChat);
    // Send button and Enter-key both call sendMessage() directly — no
    // <form> submit involved, so this can't be swallowed or redirected by
    // an ancestor form elsewhere on the page (the wai-input/uc-send-form
    // wrappers were plain <form> elements nested inside the page's other
    // forms, which is invalid HTML the browser is free to reparse/ignore).
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('input', autoGrow);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('wai-open')) closePanel(); });

    renderSuggestions();
})();
</script>

<script>
(function () {
    var root = document.getElementById('wai-root');
    if (!root || root.dataset.ucInit) return;
    root.dataset.ucInit = '1';

    var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var MY_ID = parseInt(root.dataset.ucMyId || '0', 10);

    var LIST_URL = root.dataset.ucListUrl;
    var NEW_CHAT_URL = root.dataset.ucNewChatUrl;
    var GROUP_CANDIDATES_URL = root.dataset.ucGroupCandidatesUrl;
    var CREATE_GROUP_URL = root.dataset.ucCreateGroupUrl;
    var VIEW_URL_TPL = root.dataset.ucViewUrlTpl;
    var SEND_URL = root.dataset.ucSendUrl;
    var MARK_READ_URL = root.dataset.ucMarkReadUrl;
    var NEW_MEMBER_URL_TPL = root.dataset.ucNewMemberUrlTpl;
    var ADD_MEMBER_URL_TPL = root.dataset.ucAddMemberUrlTpl;
    var REMOVE_MEMBER_URL_TPL = root.dataset.ucRemoveMemberUrlTpl;
    var UPDATE_GROUP_URL_TPL = root.dataset.ucUpdateGroupUrlTpl;
    var DELETE_GROUP_URL_TPL = root.dataset.ucDeleteGroupUrlTpl;

    function viewUrl(type, id) { return VIEW_URL_TPL.replace('__type__', type).replace('__id__', id); }
    function withId(tpl, id) { return tpl.replace('__id__', id); }
    function toastrOrAlert(msg) { if (window.toastr && window.toastr.error) { toastr.error(msg); } else { alert(msg); } }
    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function timeAgo(dateStr) {
        if (!dateStr) return '';
        var d = new Date(String(dateStr).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        var diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return 'now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return Math.floor(diff / 86400) + 'd';
    }
    function isImageAttachment(url) { return /\.(jpe?g|png|gif|webp)(\?|$)/i.test(url); }

    // Photo-first avatar with an initials fallback — Common::getResortUserPicture()
    // (server side) already guarantees a non-empty URL, but that URL can still
    // 404 (a missing/never-uploaded file), so this covers that case rather than
    // trusting the URL always resolves.
    var UC_AV_PALETTE = ['#0E8A9E', '#6B4FA0', '#1F9D6B', '#D98A00', '#4A5F8A', '#A0527A'];
    function ucAvatarColor(name) {
        var hash = 0, s = String(name || '');
        for (var i = 0; i < s.length; i++) { hash = (s.charCodeAt(i) + ((hash << 5) - hash)) % 1000000007; }
        return UC_AV_PALETTE[Math.abs(hash) % UC_AV_PALETTE.length];
    }
    function ucInitials(name) {
        var parts = String(name || '').trim().split(/\s+/).slice(0, 2);
        var s = parts.map(function (p) { return p.charAt(0).toUpperCase(); }).join('');
        return s || '?';
    }
    function ucAvatarInner(profile, name) {
        return (profile ? '<img src="' + profile + '" alt="" onerror="this.remove()">' : '') +
            '<span class="uc-av-fallback" style="background:' + ucAvatarColor(name) + '">' + escapeHtml(ucInitials(name)) + '</span>';
    }
    function ucDayLabel(dateStr) {
        if (!dateStr) return '';
        var d = new Date(String(dateStr).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        var today = new Date(), yest = new Date(today.getTime()); yest.setDate(today.getDate() - 1);
        var sameDay = function (a, b) { return a.toDateString() === b.toDateString(); };
        if (sameDay(d, today)) return 'Today';
        if (sameDay(d, yest)) return 'Yesterday';
        return d.toLocaleDateString(undefined, d.getFullYear() === today.getFullYear() ? { month: 'short', day: 'numeric' } : { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function ucTimeLabel(dateStr) {
        if (!dateStr) return '';
        var d = new Date(String(dateStr).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }

    var dock = document.getElementById('waiDock'); // retired launcher — guarded below, may be null
    var scrim = document.getElementById('wai-scrim');
    var msgCount = document.getElementById('msgCount'); // now the Dynamic Island's Messages badge (header.blade.php)
    var waiPanel = document.getElementById('wai-panel');
    var ucPanel = document.getElementById('uc-panel');

    var viewList = document.getElementById('uc-view-list');
    var viewPicker = document.getElementById('uc-view-picker');
    var viewThread = document.getElementById('uc-view-thread');
    var viewInfo = document.getElementById('uc-view-group-info');

    var state = { pickerMode: 'chat', selected: {}, current: null };
    var pendingAttachment = null;
    var convCache = [];
    var subscribedChannels = {};

    function showView(v) {
        [viewList, viewPicker, viewThread, viewInfo].forEach(function (el) { el.style.display = (el === v) ? 'flex' : 'none'; });
    }

    // ---- Realtime (Pusher via the window.Echo shim in partials.pusher-init) ----
    // My own private channel covers every 1-1 message sent TO me; each group
    // I'm in needs its own presence-channel subscription (joined lazily as
    // groups show up in the conversation list, same "join on load" pattern
    // the resort-online presence roster already uses elsewhere).
    // ---- Live-activity pop-up (nothing open, a new arrival surfaces here) ----
    var haWrap = document.getElementById('ha-wrap');
    var haName = document.getElementById('haName');
    var haMsg = document.getElementById('haMsg');
    var haAv = document.getElementById('haAv');
    var haInput = document.getElementById('haInput');
    var haReplyBtn = document.getElementById('haReplyBtn');
    var haSend = document.getElementById('haSend');
    var haDismissBtn = document.getElementById('haDismiss');
    var haReplyCtx = null;
    var haHideTimer = null;

    function showAct(mode, name, avatarHtml, text, replyCtx) {
        haName.textContent = name;
        haMsg.textContent = text;
        haAv.innerHTML = avatarHtml;
        haInput.value = '';
        // A group reply posts to the group, not the sender shown as "name"
        // — the placeholder must say so, or it reads like a private DM to
        // whoever's name is on top.
        var replyLabel = (replyCtx && replyCtx.label) || name.split(' ')[0];
        haInput.placeholder = mode === 'chat' ? ('Reply to ' + replyLabel) : '';
        haWrap.classList.remove('replying');
        haWrap.classList.toggle('notif', mode === 'notif');
        haReplyCtx = replyCtx || null;
        haWrap.classList.add('show');
        clearTimeout(haHideTimer);
        haHideTimer = setTimeout(hideAct, 6000);
    }
    function hideAct() {
        haWrap.classList.remove('show');
        clearTimeout(haHideTimer);
        setTimeout(function () { haWrap.classList.remove('replying'); }, 320);
    }
    haReplyBtn.addEventListener('click', function () {
        haWrap.classList.add('replying');
        haInput.focus();
        clearTimeout(haHideTimer); // don't auto-dismiss while composing
    });
    function sendHaReply() {
        var text = haInput.value.trim();
        if (!text || !haReplyCtx) return;
        var fd = new FormData();
        fd.append('type', haReplyCtx.type);
        fd.append('type_id', haReplyCtx.type_id);
        fd.append('message', text);
        fetch(SEND_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                if (res.ok && res.body.success) { hideAct(); loadConversations(); }
                else { toastrOrAlert((res.body && res.body.message) || 'Could not send reply.'); }
            })
            .catch(function () { toastrOrAlert('Network error. Please try again.'); });
    }
    haSend.addEventListener('click', sendHaReply);
    haInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendHaReply(); } });
    haDismissBtn.addEventListener('click', hideAct);
    // Notifications (js.blade.php's Resortevent-channel listener) reach this
    // popup the same way the panels do — a CustomEvent, not a direct call.
    document.addEventListener('wai:activity', function (e) {
        var d = e.detail || {};
        showAct(d.mode, d.name, d.avatarHtml, d.text, null);
    });

    function handleIncomingMessage(data) {
        if (parseInt(data.sender_id, 10) === MY_ID) return; // echo of my own send
        var open = state.current;
        var forOpenThread = open && data.type === open.type && String(data.type_id) === String(
            open.type === 'individual' ? MY_ID : open.id
        ) && (open.type === 'group' ? true : parseInt(data.sender_id, 10) === parseInt(open.id, 10));
        if (forOpenThread && ucPanel.classList.contains('wai-open')) {
            // Re-fetch rather than append the raw broadcast payload — the
            // attachment path in the socket event isn't resolved to a real
            // URL (only the REST endpoints do that), so a re-fetch is the
            // simplest correct way to render it.
            loadThread();
        } else if (!ucPanel.classList.contains('wai-open') && !waiPanel.classList.contains('wai-open')) {
            // Nothing open at all — surface the live-activity pop-up instead
            // of letting the arrival go silent behind just a badge bump.
            if (data.type === 'group') {
                // convCache only has the group's own name/avatar, not its
                // members — resolve the actual sender via the same
                // resort.chat.view endpoint loadThread() already uses (a
                // lightweight one-off fetch, not the full thread-open flow,
                // so it doesn't mark anything read or touch state.current).
                var groupConvo = (convCache || []).find(function (c) { return c.type === 'group' && String(c.id) === String(data.type_id); });
                var groupName = groupConvo ? groupConvo.name : 'Group';
                fetch(viewUrl('group', data.type_id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        var members = (res && res.success && res.data && res.data.members) || [];
                        var sender = members.find(function (m) { return parseInt(m.id, 10) === parseInt(data.sender_id, 10); });
                        var senderName = sender ? sender.name : 'Someone';
                        showAct('chat', senderName, ucAvatarInner(sender ? sender.profile : null, senderName),
                            groupName + ' · ' + (data.message || 'Sent an attachment'),
                            { type: 'group', type_id: data.type_id, label: groupName });
                    })
                    .catch(function () {
                        showAct('chat', groupName, '<i class="fa-solid fa-user-group"></i>', data.message || 'Sent an attachment',
                            { type: 'group', type_id: data.type_id, label: groupName });
                    });
            } else {
                var convo = (convCache || []).find(function (c) { return c.type === 'individual' && String(c.id) === String(data.sender_id); });
                // "New message" reads fine as a fallback title, but
                // split(' ')[0] for the "Reply to {FirstName}" placeholder
                // turned it into the nonsensical "Reply to New" — use a
                // name-shaped fallback instead.
                var name = convo ? convo.name : 'Someone';
                showAct('chat', name, ucAvatarInner(convo ? convo.profile : null, name), data.message || 'Sent an attachment',
                    { type: 'individual', type_id: data.sender_id });
            }
        }
        // Always refresh in the background (updates the Island/panel unread
        // counts and list preview) — not just while the list view is on
        // screen, since the count has to update even with the panel closed.
        loadConversations();
        if (window.playChatPing) window.playChatPing();
    }

    function subscribeRealtime() {
        if (!window.Echo || !MY_ID) return;
        var myChannel = 'chat.' + MY_ID;
        if (!subscribedChannels[myChannel]) {
            subscribedChannels[myChannel] = true;
            window.Echo.private(myChannel).listen('MessageSent', handleIncomingMessage);
        }
    }
    function subscribeToGroups(list) {
        if (!window.Echo) return;
        list.forEach(function (c) {
            if (c.type !== 'group') return;
            var name = 'group.' + c.id;
            if (subscribedChannels[name]) return;
            subscribedChannels[name] = true;
            window.Echo.join(name).listen('MessageSent', handleIncomingMessage);
        });
    }
    // partials.pusher-init (which defines window.Echo) is included in
    // resorts.layouts.js — loaded AFTER resorts.layouts.footer (this
    // widget) in the page layout, so window.Echo doesn't exist yet at this
    // point in document order. DOMContentLoaded fires once every
    // synchronous script — including the later one — has run.
    function initRealtimeAndBadge() { subscribeRealtime(); loadConversations(); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRealtimeAndBadge);
    } else {
        initRealtimeAndBadge();
    }

    // ---- Opened from the Dynamic Island (header.blade.php) ------------------
    // The Island dispatches this event when "Messages" is clicked — same
    // hand-off pattern as the existing wai:open-ai event for the WAI panel.
    document.addEventListener('wai:open-users', openUsersChat);

    function openUsersChat() {
        var other = document.getElementById('wai-panel');
        if (other) other.classList.remove('wai-open'); // only one panel open at a time
        ucPanel.classList.add('wai-open');
        if (dock) dock.classList.add('wdn-hide');
        if (scrim) scrim.classList.add('show');
        showView(viewList);
        loadConversations();
    }
    function closeUsersChat() {
        ucPanel.classList.remove('wai-open');
        if (dock) dock.classList.remove('wdn-hide');
        if (scrim) scrim.classList.remove('show');
    }
    if (scrim) scrim.addEventListener('click', function () { if (ucPanel.classList.contains('wai-open')) closeUsersChat(); });
    document.getElementById('uc-list-close').addEventListener('click', closeUsersChat);
    document.getElementById('uc-picker-close').addEventListener('click', closeUsersChat);
    document.getElementById('uc-info-close').addEventListener('click', closeUsersChat);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && ucPanel.classList.contains('wai-open')) closeUsersChat(); });

    // ---- Conversation list --------------------------------------------------
    // Renders the unread total on the Dynamic Island's "Messages" option —
    // same total the old corner badge showed, capped to a single digit
    // ("9+") since it has to fit inside the Island's compact menu row.
    function updateLauncherBadge(list) {
        var total = (list || []).reduce(function (sum, c) { return sum + (parseInt(c.unread_count, 10) || 0); }, 0);
        if (msgCount) msgCount.textContent = total > 0 ? (total > 9 ? '9+' : String(total)) : '';
    }

    // Called both to render the visible list and, silently, in the
    // background (panel closed, or another view open) purely to keep the
    // notch/panel unread count accurate as messages arrive in realtime.
    function loadConversations() {
        var listVisible = ucPanel.classList.contains('wai-open') && viewList.style.display !== 'none';
        if (listVisible) document.getElementById('uc-conversations').innerHTML = '<div class="uc-empty">Loading…</div>';
        fetch(LIST_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                convCache = (data && data.chats) ? data.chats : [];
                updateLauncherBadge(convCache);
                if (listVisible) renderConversations(convCache);
                subscribeToGroups(convCache);
            })
            .catch(function () {
                if (listVisible) document.getElementById('uc-conversations').innerHTML = '<div class="uc-empty">Couldn\'t load conversations.</div>';
            });
    }
    function renderConversations(list) {
        var wrap = document.getElementById('uc-conversations');
        if (!list.length) { wrap.innerHTML = '<div class="uc-empty">No conversations yet. Tap the pencil to start one.</div>'; return; }
        wrap.innerHTML = '';
        list.forEach(function (c) {
            var unread = c.unread_count > 0;
            var item = document.createElement('div');
            item.className = 'crow' + (unread ? ' unread' : '');
            var avatar = c.type === 'group'
                ? '<span class="av"><i class="fa-solid fa-user-group"></i></span>'
                : '<span class="av">' + ucAvatarInner(c.profile, c.name) + '</span>';
            item.innerHTML = avatar +
                '<div class="cw">' +
                    '<div class="r1"><span class="cn">' + escapeHtml(c.name) + '</span>' +
                    '<span class="ct">' + timeAgo(c.last_seen) + '</span></div>' +
                    '<div class="r2">' +
                        '<span class="cs' + (c.last_msg ? '' : ' crow-cs-none') + '">' + (c.last_msg ? escapeHtml(c.last_msg) : 'No messages yet') + '</span>' +
                        (unread ? '<span class="dot"></span>' : '') +
                    '</div>' +
                '</div>';
            item.addEventListener('click', function () { openThread(c.type, c.id, c.name, c.profile); });
            wrap.appendChild(item);
        });
    }
    document.getElementById('uc-list-search').addEventListener('input', function () {
        var q = this.value.toLowerCase();
        renderConversations(convCache.filter(function (c) { return c.name.toLowerCase().indexOf(q) !== -1; }));
    });

    // ---- New chat / new group picker ----------------------------------------
    function openPicker(mode) {
        state.pickerMode = mode; state.selected = {};
        document.getElementById('uc-picker-title').textContent = mode === 'group' ? 'New group' : 'New chat';
        document.getElementById('uc-group-name-row').style.display = mode === 'group' ? 'block' : 'none';
        document.getElementById('uc-group-create-row').style.display = mode === 'group' ? 'flex' : 'none';
        document.getElementById('uc-group-create-btn').textContent = 'Create group';
        document.getElementById('uc-group-create-btn').disabled = true;
        document.getElementById('uc-group-selected-count').textContent = '0 selected';
        document.getElementById('uc-group-name').value = '';
        document.getElementById('uc-picker-search').value = '';
        showView(viewPicker);
        loadPickerList('');
    }
    document.getElementById('uc-new-chat').addEventListener('click', function () { openPicker('chat'); });
    document.getElementById('uc-new-group').addEventListener('click', function () { openPicker('group'); });
    document.getElementById('uc-picker-back').addEventListener('click', function () { showView(viewList); });

    function loadPickerList(search) {
        var wrap = document.getElementById('uc-picker-list');
        wrap.innerHTML = '<div class="uc-empty">Loading…</div>';
        var url = state.pickerMode === 'group'
            ? GROUP_CANDIDATES_URL
            : (NEW_CHAT_URL + (search ? ('?search=' + encodeURIComponent(search)) : ''));
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                if (!res.ok || !res.body.success) {
                    wrap.innerHTML = '<div class="uc-empty">' + escapeHtml(res.body.message || 'Not authorized.') + '</div>';
                    return;
                }
                renderPickerList(res.body.data || []);
            })
            .catch(function () { wrap.innerHTML = '<div class="uc-empty">Something went wrong.</div>'; });
    }
    function renderPickerList(list) {
        var wrap = document.getElementById('uc-picker-list');
        if (!list.length) { wrap.innerHTML = '<div class="uc-empty">No one available.</div>'; return; }
        wrap.innerHTML = '';
        list.forEach(function (p) {
            var item = document.createElement('div');
            item.className = 'uc-picker-item';
            var checkable = state.pickerMode === 'group' || state.pickerMode === 'add-member';
            item.innerHTML =
                (checkable ? '<input type="checkbox" data-id="' + p.id + '">' : '') +
                '<div class="uc-picker-avatar">' + ucAvatarInner(p.profile, p.name) + '</div>' +
                '<span class="uc-picker-name">' + escapeHtml(p.name) + '</span>';
            if (checkable) {
                var cb = item.querySelector('input');
                cb.addEventListener('change', function () {
                    if (cb.checked) { state.selected[p.id] = p.name; } else { delete state.selected[p.id]; }
                    var count = Object.keys(state.selected).length;
                    document.getElementById('uc-group-selected-count').textContent = count + ' selected';
                    document.getElementById('uc-group-create-btn').disabled = count === 0;
                });
                item.addEventListener('click', function (e) { if (e.target !== cb) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); } });
            } else {
                item.addEventListener('click', function () { openThread('individual', p.id, p.name, p.profile); });
            }
            wrap.appendChild(item);
        });
    }
    document.getElementById('uc-picker-search').addEventListener('input', function () {
        var q = this.value;
        if (state.pickerMode === 'chat') { loadPickerList(q); return; }
        var items = document.getElementById('uc-picker-list').children;
        for (var i = 0; i < items.length; i++) {
            var name = items[i].querySelector('.uc-picker-name').textContent.toLowerCase();
            items[i].style.display = name.indexOf(q.toLowerCase()) !== -1 ? 'flex' : 'none';
        }
    });

    document.getElementById('uc-group-create-btn').addEventListener('click', function () {
        var members = Object.keys(state.selected);
        if (!members.length) return;

        if (state.pickerMode === 'add-member') {
            fetch(withId(ADD_MEMBER_URL_TPL, state.current.id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ members: members })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) { showView(viewInfo); loadThread().then(renderGroupInfo); } else { toastrOrAlert(res.message); }
            });
            return;
        }

        var name = document.getElementById('uc-group-name').value.trim();
        if (!name) { toastrOrAlert('Please enter a group name.'); return; }
        fetch(CREATE_GROUP_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ name: name, members: members })
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            if (res.ok && res.body.success) { openThread('group', res.body.group.id, name, null); }
            else { toastrOrAlert(res.body.message || 'Could not create group.'); }
        });
    });

    // ---- Thread view ----------------------------------------------------------
    function openThread(type, id, name, profile) {
        state.current = { type: type, id: id, name: name, profile: profile || null, isAdmin: false, members: [] };
        document.getElementById('uc-thread-title').textContent = name || '';
        document.getElementById('uc-thread-subtitle').textContent = type === 'group' ? 'Group' : '';
        var avatarEl = document.getElementById('uc-thread-avatar');
        avatarEl.innerHTML = type === 'group'
            ? '<i class="fa-solid fa-user-group"></i>'
            : ucAvatarInner(profile, name);
        document.getElementById('uc-thread-info').style.display = type === 'group' ? 'flex' : 'none';
        showView(viewThread);
        loadThread();
    }
    document.getElementById('uc-thread-back').addEventListener('click', function () {
        // Was never cleared here — handleIncomingMessage()'s forOpenThread
        // check kept matching the last-viewed thread indefinitely, so a
        // new message arriving while the user was back on the LIST screen
        // still silently re-fetched and marked that thread's messages read.
        state.current = null;
        showView(viewList);
        loadConversations();
    });

    function loadThread() {
        var msgsEl = document.getElementById('uc-messages');
        msgsEl.innerHTML = '<div class="uc-empty">Loading…</div>';
        return fetch(viewUrl(state.current.type, state.current.id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                if (!res.ok || !res.body.success) {
                    msgsEl.innerHTML = '<div class="uc-empty">' + escapeHtml(res.body.message || 'Could not open this chat.') + '</div>';
                    return;
                }
                if (state.current.type === 'group') {
                    state.current.name = res.body.data.name;
                    state.current.isAdmin = !!res.body.data.is_admin;
                    state.current.members = res.body.data.members || [];
                    document.getElementById('uc-thread-title').textContent = res.body.data.name;
                    document.getElementById('uc-thread-subtitle').textContent = state.current.members.length + ' members';
                } else if (res.body.data && res.body.data.profile) {
                    state.current.profile = res.body.data.profile;
                }
                renderMessages(res.body.messages || []);
                markRead(res.body.messages || []);
                loadConversations(); // refresh the notch/panel unread count now that these are read
            })
            .catch(function () { msgsEl.innerHTML = '<div class="uc-empty">Something went wrong.</div>'; });
    }

    // Looks up a message's sender name/photo from data already fetched into
    // state.current (data.profile for a 1-1 thread, data.members for a
    // group) — no new request per message.
    function ucSenderInfo(senderId) {
        if (state.current.type === 'group') {
            var m = (state.current.members || []).find(function (x) { return parseInt(x.id, 10) === parseInt(senderId, 10); });
            return m ? { name: m.name, profile: m.profile } : { name: '', profile: null };
        }
        return { name: state.current.name, profile: state.current.profile };
    }

    function renderMessages(messages) {
        var msgsEl = document.getElementById('uc-messages');
        msgsEl.innerHTML = '';
        if (!messages.length) {
            msgsEl.innerHTML =
                '<div class="uc-thread-empty">' +
                    '<div class="uc-thread-empty-ic"><i class="fa-solid fa-comment-dots"></i></div>' +
                    '<div class="uc-thread-empty-t">No messages yet</div>' +
                    '<div class="uc-thread-empty-s">Your first message starts the conversation.</div>' +
                '</div>';
            return;
        }
        var lastDay = null, lastSenderId = null;
        messages.forEach(function (m) {
            var mine = parseInt(m.sender_id, 10) === MY_ID;

            var thisDay = ucDayLabel(m.created_at);
            if (thisDay && thisDay !== lastDay) {
                var sep = document.createElement('div');
                sep.className = 'uc-daysep';
                sep.textContent = thisDay;
                msgsEl.appendChild(sep);
                lastDay = thisDay;
                lastSenderId = null; // show the avatar again after a day break
            }

            var row = document.createElement('div');
            row.className = 'wai-row ' + (mine ? 'wai-user' : 'wai-bot');

            var avatarHtml = '';
            if (!mine) {
                var showAvatar = parseInt(m.sender_id, 10) !== lastSenderId;
                var s = ucSenderInfo(m.sender_id);
                avatarHtml = '<div class="wai-mini-avatar uc-msg-avatar' + (showAvatar ? '' : ' uc-msg-avatar-ghost') + '">' +
                    (showAvatar ? ucAvatarInner(s.profile, s.name) : '') + '</div>';
            }

            var bubble = '<div class="wai-bubble">';
            if (m.message) bubble += escapeHtml(m.message).replace(/\n/g, '<br>');
            if (m.attachment) {
                bubble += '<div class="uc-bubble-attachment">' + (isImageAttachment(m.attachment)
                    ? '<img src="' + m.attachment + '" onclick="window.open(this.src)">'
                    : '<a href="' + m.attachment + '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> Attachment</a>') + '</div>';
            }
            bubble += '<div class="uc-bt">' + ucTimeLabel(m.created_at) + '</div>';
            bubble += '</div>';
            row.innerHTML = avatarHtml + bubble;
            msgsEl.appendChild(row);

            lastSenderId = mine ? lastSenderId : parseInt(m.sender_id, 10);
        });
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    function markRead(messages) {
        messages.forEach(function (m) {
            if (parseInt(m.sender_id, 10) !== MY_ID) {
                fetch(MARK_READ_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ conversation_id: m.id })
                });
            }
        });
    }

    document.getElementById('uc-attachment').addEventListener('change', function () {
        var file = this.files[0];
        var prev = document.getElementById('uc-attach-preview');
        if (!file) { prev.style.display = 'none'; pendingAttachment = null; return; }
        pendingAttachment = file;
        prev.style.display = 'flex';
        prev.innerHTML = '<i class="fa-solid fa-paperclip"></i> ' + escapeHtml(file.name) + ' <span class="uc-attach-remove">Remove</span>';
        prev.querySelector('.uc-attach-remove').addEventListener('click', function () {
            pendingAttachment = null; document.getElementById('uc-attachment').value = ''; prev.style.display = 'none';
        });
    });

    var ucTextEl = document.getElementById('uc-text');
    var ucSendBtn = document.getElementById('uc-send');
    ucTextEl.addEventListener('input', function () {
        this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 110) + 'px';
        ucSendBtn.disabled = !this.value.trim();
    });
    ucTextEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendUsersChatMessage(); }
    });
    document.getElementById('uc-send').addEventListener('click', sendUsersChatMessage);

    function sendUsersChatMessage() {
        var textEl = document.getElementById('uc-text');
        var text = textEl.value.trim();
        if (!text && !pendingAttachment) return;
        var fd = new FormData();
        fd.append('type', state.current.type);
        fd.append('type_id', state.current.id);
        if (text) fd.append('message', text);
        if (pendingAttachment) fd.append('attachment', pendingAttachment);

        fetch(SEND_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                if (res.ok && res.body.success) {
                    textEl.value = ''; textEl.dispatchEvent(new Event('input')); pendingAttachment = null;
                    document.getElementById('uc-attachment').value = '';
                    document.getElementById('uc-attach-preview').style.display = 'none';
                    renderMessages(res.body.chat_history || []);
                } else {
                    var firstError = res.body.errors ? Object.values(res.body.errors)[0][0] : null;
                    toastrOrAlert(firstError || res.body.message || 'Could not send message.');
                }
            })
            .catch(function () { toastrOrAlert('Network error. Please try again.'); });
    }

    // ---- Group info -------------------------------------------------------------
    document.getElementById('uc-thread-info').addEventListener('click', function () { showView(viewInfo); renderGroupInfo(); });
    document.getElementById('uc-info-back').addEventListener('click', function () { showView(viewThread); });

    function renderGroupInfo() {
        var body = document.getElementById('uc-group-info-body');
        var g = state.current;
        var isAdmin = !!g.isAdmin;
        var html = '<h5>Group name</h5>';
        html += isAdmin
            ? '<input type="text" id="uc-rename-input" class="uc-info-name-edit" value="' + escapeHtml(g.name) + '"><button type="button" class="uc-info-action" id="uc-rename-btn">Save name</button>'
            : '<div>' + escapeHtml(g.name) + '</div>';
        html += '<h5>Members (' + g.members.length + ')</h5>';
        g.members.forEach(function (m) {
            html += '<div class="uc-member-row"><div class="uc-conv-avatar">' + ucAvatarInner(m.profile, m.name) + '</div>' +
                '<span>' + escapeHtml(m.name) + (m.role === 'admin' ? ' · Admin' : '') + '</span>' +
                (isAdmin && m.role !== 'admin' ? '<button data-id="' + m.id + '" class="uc-remove-member">Remove</button>' : '') +
                '</div>';
        });
        if (isAdmin) {
            html += '<button type="button" class="uc-info-action" id="uc-add-member-btn">Add members</button>' +
                    '<button type="button" class="uc-info-action uc-danger" id="uc-delete-group-btn">Delete group</button>';
        }
        body.innerHTML = html;

        if (!isAdmin) return;

        document.getElementById('uc-rename-btn').addEventListener('click', function () {
            var newName = document.getElementById('uc-rename-input').value.trim();
            if (!newName) return;
            var fd = new FormData(); fd.append('name', newName);
            fetch(withId(UPDATE_GROUP_URL_TPL, g.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) { g.name = newName; document.getElementById('uc-thread-title').textContent = newName; renderGroupInfo(); }
                    else { toastrOrAlert(res.message); }
                });
        });
        document.getElementById('uc-add-member-btn').addEventListener('click', function () { openAddMemberPicker(); });
        document.getElementById('uc-delete-group-btn').addEventListener('click', function () {
            if (!confirm('Delete this group? This cannot be undone.')) return;
            fetch(withId(DELETE_GROUP_URL_TPL, g.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) { if (res.success) { showView(viewList); loadConversations(); } else { toastrOrAlert(res.message); } });
        });
        body.querySelectorAll('.uc-remove-member').forEach(function (btn) {
            btn.addEventListener('click', function () {
                fetch(withId(REMOVE_MEMBER_URL_TPL, g.id), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ member_id: btn.dataset.id })
                })
                .then(function (r) { return r.json(); })
                .then(function (res) { if (res.success) { loadThread().then(renderGroupInfo); } else { toastrOrAlert(res.message); } });
            });
        });
    }

    function openAddMemberPicker() {
        state.pickerMode = 'add-member'; state.selected = {};
        document.getElementById('uc-picker-title').textContent = 'Add members';
        document.getElementById('uc-group-name-row').style.display = 'none';
        document.getElementById('uc-group-create-row').style.display = 'flex';
        document.getElementById('uc-group-create-btn').textContent = 'Add';
        document.getElementById('uc-group-create-btn').disabled = true;
        document.getElementById('uc-group-selected-count').textContent = '0 selected';
        document.getElementById('uc-picker-search').value = '';
        showView(viewPicker);

        var wrap = document.getElementById('uc-picker-list');
        wrap.innerHTML = '<div class="uc-empty">Loading…</div>';
        fetch(withId(NEW_MEMBER_URL_TPL, state.current.id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) { renderPickerList(res.data || []); });
    }
})();
</script>
@endif
