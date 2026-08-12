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

    <!-- Launcher -->
    <button type="button" id="wai-launcher" aria-label="Open chat">
        <span class="wai-launcher-icon"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai-icon.jpeg') }}" class="wai-bot-img" alt="Chat"></span>
        <span class="wai-launcher-spark"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
    </button>

    <!-- Launcher chooser: Ask Wisdom AI vs message a colleague -->
    <div id="uc-chooser" role="menu">
        <button type="button" class="uc-choice" id="uc-choice-ai" role="menuitem">
            <span class="uc-choice-icon"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai-icon.jpeg') }}" class="wai-bot-img" alt=""></span>
            <span class="uc-choice-text"><strong>Ask Wisdom AI</strong><small>Your HR assistant</small></span>
        </button>
        <button type="button" class="uc-choice" id="uc-choice-users" role="menuitem">
            <span class="uc-choice-icon uc-choice-icon-users"><i class="fa-solid fa-comments"></i></span>
            <span class="uc-choice-text"><strong>Message a colleague</strong><small>Chat with staff at your resort</small></span>
        </button>
    </div>

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

        <div class="wai-input" id="wai-form">
            <textarea id="wai-text" rows="1" placeholder="Ask Wisdom AI…" maxlength="2000"></textarea>
            <button type="button" id="wai-send" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
        <div class="wai-foot">Wisdom AI can make mistakes. Verify important HR decisions.</div>
    </div>

    <!-- Users chat panel -->
    <div id="uc-panel" role="dialog" aria-label="Colleague chat">

        <!-- List view -->
        <div id="uc-view-list" class="uc-view">
            <div class="wai-header">
                <div class="wai-header-id">
                    <div class="wai-avatar"><i class="fa-solid fa-comments"></i></div>
                    <div class="wai-titles">
                        <div class="wai-title">Messages</div>
                        <div class="wai-subtitle">{{ $wisdomCtx['user_name'] }}</div>
                    </div>
                </div>
                <div class="wai-header-actions">
                    <button type="button" id="uc-new-group" title="New group"><i class="fa-solid fa-user-group"></i></button>
                    <button type="button" id="uc-new-chat" title="New chat"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button type="button" id="uc-list-close" title="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="uc-search"><input type="text" id="uc-list-search" placeholder="Search conversations…"></div>
            <div class="uc-list" id="uc-conversations"><div class="uc-empty">Loading…</div></div>
        </div>

        <!-- New chat / new group member picker -->
        <div id="uc-view-picker" class="uc-view" style="display:none;">
            <div class="wai-header">
                <div class="wai-header-id">
                    <button type="button" class="uc-back" id="uc-picker-back"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="wai-titles"><div class="wai-title" id="uc-picker-title">New chat</div></div>
                </div>
                <div class="wai-header-actions"><button type="button" id="uc-picker-close" title="Close"><i class="fa-solid fa-xmark"></i></button></div>
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
                <div class="wai-header-id">
                    <button type="button" class="uc-back" id="uc-thread-back"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="wai-avatar" id="uc-thread-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="wai-titles">
                        <div class="wai-title" id="uc-thread-title">&nbsp;</div>
                        <div class="wai-subtitle" id="uc-thread-subtitle">&nbsp;</div>
                    </div>
                </div>
                <div class="wai-header-actions">
                    <button type="button" id="uc-thread-info" title="Group info"><i class="fa-solid fa-circle-info"></i></button>
                </div>
            </div>
            <div class="wai-messages" id="uc-messages"></div>
            <div class="wai-input uc-thread-input" id="uc-send-form">
                <label class="uc-attach-btn" title="Attach a photo or file">
                    <input type="file" id="uc-attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" hidden>
                    <i class="fa-solid fa-paperclip"></i>
                </label>
                <textarea id="uc-text" rows="1" placeholder="Message…" maxlength="2000"></textarea>
                <button type="button" id="uc-send" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
            <div class="uc-attach-preview" id="uc-attach-preview" style="display:none;"></div>
        </div>

        <!-- Group info / manage -->
        <div id="uc-view-group-info" class="uc-view" style="display:none;">
            <div class="wai-header">
                <div class="wai-header-id">
                    <button type="button" class="uc-back" id="uc-info-back"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="wai-titles"><div class="wai-title">Group info</div></div>
                </div>
                <div class="wai-header-actions"><button type="button" id="uc-info-close" title="Close"><i class="fa-solid fa-xmark"></i></button></div>
            </div>
            <div class="uc-group-info-body" id="uc-group-info-body"></div>
        </div>
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

/* Input — shared pill bar for both the AI panel and the Users chat panel */
.wai-input {
    display: flex; align-items: flex-end; gap: 9px; padding: 10px 12px 12px; background: #f7f7fb;
}
#wai-text, #uc-text {
    flex: 1; resize: none; border: 1.5px solid #e1e6f0; border-radius: 22px; padding: 11px 17px;
    font-size: 14px; max-height: 110px; outline: none; background: #fff; color: #25243a; line-height: 1.45;
    box-shadow: 0 1px 3px rgba(30,12,80,.04);
    transition: border-color .15s ease, box-shadow .15s ease;
}
#wai-text::placeholder, #uc-text::placeholder { color: #a6a2b8; }
#wai-text:focus, #uc-text:focus { border-color: #1c7c81; box-shadow: 0 0 0 3px rgba(28,124,129,.16); }
#wai-send, #uc-send {
    width: 44px; height: 44px; flex: 0 0 44px; border: none; border-radius: 50%; cursor: pointer;
    background: var(--wai-send); color: #0b2e37; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(170,207,0,.45);
    transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
}
#wai-send i, #uc-send i { transform: translateX(-1px); }
#wai-send:hover, #uc-send:hover { transform: translateY(-1px) scale(1.05); box-shadow: 0 6px 18px rgba(170,207,0,.55); }
#wai-send:active, #uc-send:active { transform: scale(.96); }
#wai-send:disabled, #uc-send:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }
.wai-foot { text-align: center; font-size: 10.5px; color: #a09ab5; padding: 0 12px 10px; }

@media (max-width: 480px) {
    #wai-panel, #uc-panel { right: 8px; bottom: 8px; width: calc(100vw - 16px); height: calc(100vh - 90px); }
    #wai-launcher { right: 16px; bottom: 16px; }
}

/* ---- Launcher chooser ------------------------------------------------ */
#uc-chooser {
    position: fixed; right: 26px; bottom: 98px; z-index: 99992;
    background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(30,12,80,.28);
    padding: 8px; display: flex; flex-direction: column; gap: 4px;
    opacity: 0; transform: translateY(10px) scale(.97); pointer-events: none;
    transition: opacity .16s ease, transform .16s ease; width: 250px;
}
#uc-chooser.uc-open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.uc-choice {
    display: flex; align-items: center; gap: 11px; width: 100%; border: none; background: none;
    padding: 9px 10px; border-radius: 11px; cursor: pointer; text-align: left; transition: background .12s;
}
.uc-choice:hover { background: #f1f3fb; }
.uc-choice-icon { width: 36px; height: 36px; border-radius: 10px; overflow: hidden; flex: 0 0 36px; display: flex; align-items: center; justify-content: center; }
.uc-choice-icon-users { background: var(--wai-grad); color: #fff; font-size: 15px; }
.uc-choice-text { display: flex; flex-direction: column; }
.uc-choice-text strong { font-size: 13.5px; color: #25243a; }
.uc-choice-text small { font-size: 11.5px; color: #8a86a0; }

/* ---- Users chat panel — same shell as #wai-panel ---------------------- */
#uc-panel {
    position: fixed; right: 26px; bottom: 26px; z-index: 99991;
    width: 390px; max-width: calc(100vw - 32px);
    height: 600px; max-height: calc(100vh - 80px);
    background: #f7f7fb; border-radius: 18px; overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 24px 60px rgba(30, 12, 80, .35);
    opacity: 0; transform: translateY(24px) scale(.96); pointer-events: none;
    transition: opacity .22s ease, transform .22s ease;
}
#uc-panel.wai-open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.uc-view { display: flex; flex-direction: column; height: 100%; }
.uc-back { background: rgba(255,255,255,.12); border: none; color: #fff; width: 32px; height: 32px; border-radius: 9px; cursor: pointer; margin-right: 2px; }
.uc-back:hover { background: rgba(255,255,255,.28); }

.uc-search { padding: 10px 14px; background: #f7f7fb; }
.uc-search input {
    width: 100%; border: 1px solid #d8e2de; border-radius: 12px; padding: 9px 12px;
    font-size: 13.5px; outline: none; background: #fff;
}
.uc-search input:focus { border-color: #1c7c81; box-shadow: 0 0 0 3px rgba(28,124,129,.15); }

.uc-list { flex: 1; overflow-y: auto; padding: 2px 6px 10px; }
.uc-empty { text-align: center; color: #9a95ac; font-size: 13px; padding: 30px 10px; }

.uc-conv-item, .uc-picker-item {
    display: flex; align-items: center; gap: 11px; padding: 9px 8px; border-radius: 12px;
    cursor: pointer; transition: background .12s;
}
.uc-conv-item:hover, .uc-picker-item:hover { background: #eef0f8; }
.uc-conv-avatar, .uc-picker-avatar {
    width: 42px; height: 42px; border-radius: 50%; flex: 0 0 42px; overflow: hidden;
    background: var(--wai-grad); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px;
}
.uc-conv-avatar img, .uc-picker-avatar img { width: 100%; height: 100%; object-fit: cover; }
.uc-conv-body { flex: 1; min-width: 0; }
.uc-conv-name-row { display: flex; justify-content: space-between; align-items: baseline; gap: 6px; }
.uc-conv-name { font-size: 13.5px; font-weight: 600; color: #25243a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.uc-conv-time { font-size: 10.5px; color: #a09ab5; flex: 0 0 auto; }
.uc-conv-last { font-size: 12px; color: #837e97; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.uc-badge { background: var(--wai-green); color: #0b2e37; font-size: 10.5px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; }

.uc-picker-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: #1c7c81; }
.uc-picker-name { font-size: 13.5px; color: #25243a; flex: 1; }

.uc-create-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #fff; border-top: 1px solid #eceef5; }
.uc-create-row span { font-size: 12px; color: #837e97; }
.uc-primary-btn {
    background: var(--wai-grad-soft); color: #fff; border: none; border-radius: 11px; padding: 9px 16px;
    font-size: 13px; font-weight: 600; cursor: pointer;
}
.uc-primary-btn:disabled { opacity: .5; cursor: not-allowed; }

.uc-thread-input { align-items: center; }
.uc-attach-btn { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 11px; color: #6a6580; cursor: pointer; flex: 0 0 38px; }
.uc-attach-btn:hover { background: #ecebf5; }
.uc-attach-preview { display: flex; align-items: center; gap: 8px; padding: 0 14px 10px; font-size: 12px; color: #5a5570; }
.uc-attach-preview img { width: 34px; height: 34px; object-fit: cover; border-radius: 7px; }
.uc-attach-preview .uc-attach-remove { cursor: pointer; color: #c0455a; margin-left: auto; }

.uc-bubble-attachment img { max-width: 180px; border-radius: 10px; display: block; margin-top: 4px; cursor: pointer; }
.uc-bubble-attachment a { font-size: 12.5px; }

.uc-group-info-body { flex: 1; overflow-y: auto; padding: 14px; }
.uc-group-info-body h5 { margin: 14px 0 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; color: #a09ab5; }
.uc-member-row { display: flex; align-items: center; gap: 10px; padding: 7px 4px; }
.uc-member-row .uc-conv-avatar { width: 32px; height: 32px; flex: 0 0 32px; font-size: 12px; }
.uc-member-row span { flex: 1; font-size: 13px; color: #25243a; }
.uc-member-row button { background: none; border: none; color: #c0455a; cursor: pointer; font-size: 12px; }
.uc-info-action { display: block; width: 100%; text-align: left; background: #fff; border: 1px solid #e3e6f0; border-radius: 10px; padding: 9px 12px; margin-bottom: 8px; font-size: 13px; color: #25243a; cursor: pointer; }
.uc-info-action.uc-danger { color: #c0455a; border-color: #f1d5da; }
.uc-info-name-edit { width: 100%; border: 1px solid #d8e2de; border-radius: 10px; padding: 8px 10px; font-size: 14px; margin-bottom: 10px; }
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
    // Current logged-in user's profile picture (falls back to the configured
    // default placeholder URL when none is set), so the user bubble shows their
    // avatar instead of a generic icon.
    var WAI_USER_AVATAR = "{{ Common::getResortUserPicture(auth()->guard('resort-admin')->id() ?? 0) }}";
    function botAvatar() { return '<div class="wai-mini-avatar"><img src="' + WAI_BOT_ICON + '" class="wai-bot-img" alt="Wisdom AI"></div>'; }
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

    // Launcher now opens the AI/Users chooser (see the second script block
    // below) instead of this panel directly — it calls openPanel() via this
    // event once the user actually picks "Ask Wisdom AI".
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

    var launcher = document.getElementById('wai-launcher');
    var waiPanel = document.getElementById('wai-panel');
    var ucPanel = document.getElementById('uc-panel');
    var chooser = document.getElementById('uc-chooser');

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
        } else if (ucPanel.classList.contains('wai-open') && viewList.style.display !== 'none') {
            loadConversations();
        }
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
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', subscribeRealtime);
    } else {
        subscribeRealtime();
    }

    // ---- Launcher chooser --------------------------------------------------
    launcher.addEventListener('click', function () {
        if (waiPanel.classList.contains('wai-open') || ucPanel.classList.contains('wai-open')) return;
        chooser.classList.toggle('uc-open');
    });
    document.addEventListener('click', function (e) {
        if (chooser.classList.contains('uc-open') && !chooser.contains(e.target) && !launcher.contains(e.target)) {
            chooser.classList.remove('uc-open');
        }
    });
    document.getElementById('uc-choice-ai').addEventListener('click', function () {
        chooser.classList.remove('uc-open');
        document.dispatchEvent(new CustomEvent('wai:open-ai'));
    });
    document.getElementById('uc-choice-users').addEventListener('click', function () {
        chooser.classList.remove('uc-open');
        openUsersChat();
    });

    function openUsersChat() {
        ucPanel.classList.add('wai-open'); launcher.classList.add('wai-hidden');
        showView(viewList);
        loadConversations();
    }
    function closeUsersChat() { ucPanel.classList.remove('wai-open'); launcher.classList.remove('wai-hidden'); }
    document.getElementById('uc-list-close').addEventListener('click', closeUsersChat);
    document.getElementById('uc-picker-close').addEventListener('click', closeUsersChat);
    document.getElementById('uc-info-close').addEventListener('click', closeUsersChat);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && ucPanel.classList.contains('wai-open')) closeUsersChat(); });

    // ---- Conversation list --------------------------------------------------
    function loadConversations() {
        document.getElementById('uc-conversations').innerHTML = '<div class="uc-empty">Loading…</div>';
        fetch(LIST_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                convCache = (data && data.chats) ? data.chats : [];
                renderConversations(convCache);
                subscribeToGroups(convCache);
            })
            .catch(function () { document.getElementById('uc-conversations').innerHTML = '<div class="uc-empty">Couldn\'t load conversations.</div>'; });
    }
    function renderConversations(list) {
        var wrap = document.getElementById('uc-conversations');
        if (!list.length) { wrap.innerHTML = '<div class="uc-empty">No conversations yet. Tap the pencil to start one.</div>'; return; }
        wrap.innerHTML = '';
        list.forEach(function (c) {
            var item = document.createElement('div');
            item.className = 'uc-conv-item';
            var avatar = c.type === 'group'
                ? '<div class="uc-conv-avatar"><i class="fa-solid fa-user-group"></i></div>'
                : '<div class="uc-conv-avatar"><img src="' + c.profile + '" alt=""></div>';
            item.innerHTML = avatar +
                '<div class="uc-conv-body">' +
                    '<div class="uc-conv-name-row"><span class="uc-conv-name">' + escapeHtml(c.name) + '</span>' +
                    '<span class="uc-conv-time">' + timeAgo(c.last_seen) + '</span></div>' +
                    '<div class="uc-conv-last">' + (c.last_msg ? escapeHtml(c.last_msg) : 'No messages yet') + '</div>' +
                '</div>' +
                (c.unread_count > 0 ? '<span class="uc-badge">' + c.unread_count + '</span>' : '');
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
                '<div class="uc-picker-avatar"><img src="' + p.profile + '" alt=""></div>' +
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
        state.current = { type: type, id: id, name: name, isAdmin: false, members: [] };
        document.getElementById('uc-thread-title').textContent = name || '';
        document.getElementById('uc-thread-subtitle').textContent = type === 'group' ? 'Group' : '';
        var avatarEl = document.getElementById('uc-thread-avatar');
        avatarEl.innerHTML = type === 'group'
            ? '<i class="fa-solid fa-user-group"></i>'
            : (profile ? '<img src="' + profile + '" alt="">' : '<i class="fa-solid fa-user"></i>');
        document.getElementById('uc-thread-info').style.display = type === 'group' ? 'flex' : 'none';
        showView(viewThread);
        loadThread();
    }
    document.getElementById('uc-thread-back').addEventListener('click', function () { showView(viewList); loadConversations(); });

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
                }
                renderMessages(res.body.messages || []);
                markRead(res.body.messages || []);
            })
            .catch(function () { msgsEl.innerHTML = '<div class="uc-empty">Something went wrong.</div>'; });
    }

    function renderMessages(messages) {
        var msgsEl = document.getElementById('uc-messages');
        msgsEl.innerHTML = '';
        if (!messages.length) { msgsEl.innerHTML = '<div class="uc-empty">Say hello 👋</div>'; return; }
        messages.forEach(function (m) {
            var mine = parseInt(m.sender_id, 10) === MY_ID;
            var row = document.createElement('div');
            row.className = 'wai-row ' + (mine ? 'wai-user' : 'wai-bot');
            var bubble = '<div class="wai-bubble">';
            if (m.message) bubble += escapeHtml(m.message).replace(/\n/g, '<br>');
            if (m.attachment) {
                bubble += '<div class="uc-bubble-attachment">' + (isImageAttachment(m.attachment)
                    ? '<img src="' + m.attachment + '" onclick="window.open(this.src)">'
                    : '<a href="' + m.attachment + '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> Attachment</a>') + '</div>';
            }
            bubble += '</div>';
            row.innerHTML = bubble;
            msgsEl.appendChild(row);
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
    ucTextEl.addEventListener('input', function () {
        this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 110) + 'px';
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
                    textEl.value = ''; pendingAttachment = null;
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
            html += '<div class="uc-member-row"><div class="uc-conv-avatar"><img src="' + m.profile + '" alt=""></div>' +
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
