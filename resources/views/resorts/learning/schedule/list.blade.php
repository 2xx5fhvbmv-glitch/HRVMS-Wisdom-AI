@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@php
    // Same fallback-detection as the Mark Attendance roster: Common::getResortUserPicture()
    // always returns a URL, falling back to this exact one when there's no real photo.
    $ts_defaultPic = url(config('settings.default_picture'));
@endphp

@section('content')
    <style>
        #learning-schedule-list-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #learning-schedule-list-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="learning-schedule-list-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card ts-panel">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5 col-6">
                            {{-- dropped .select2t-none: despite the name it's the global
                                 auto-init hook (resorts.layouts.js) that turns a <select>
                                 into select2 — the .dd component below is now this
                                 select's entire visual layer, so select2 would just render
                                 a second, unstyled trigger on top of it. --}}
                            <select id="typeFilter" class="form-select dd-native-select">
                                <option value="">By Learning Type</option>
                                <option value="face-to-face">Face-to-Face</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="online">Online</option>
                            </select>
                            <div class="dd" id="typeFilterDd" data-target="#typeFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">By Learning Type</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="By Learning Type">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">By Learning Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="face-to-face"><span class="dd-nm">Face-to-Face</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="hybrid"><span class="dd-nm">Hybrid</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="online"><span class="dd-nm">Online</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-5 col-md-6 col-6">
                            <div class="ts-jump" id="tsJump">
                                <button type="button" class="ts-jump-btn" id="tsJumpBtn">
                                    <i class="fa-regular fa-calendar-days"></i>
                                    <span>Jump to month</span>
                                    <i class="fa-solid fa-chevron-down ts-jump-caret"></i>
                                </button>
                                <div class="ts-jump-menu" id="tsJumpMenu"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="ts-timeline"></div>

                <div class="ts-loadmore" id="tsLoadmore">
                    <button type="button" id="tsLoadBtn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                        Load older sessions
                    </button>
                    <span class="ts-rem" id="tsRem"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- shared frosted popover for attendees -->
    <div class="att-pop" id="attPop">
        <div class="k"><span class="dot"></span><span id="attCount">Attendees</span></div>
        <div class="list" id="attList"></div>
    </div>
@include('resorts.Learning._learning_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    :root {
        --ts-g2: var(--muted, #6B7378);
        --ts-g3: var(--faint, #99A1A5);
        --ts-g4: var(--line, #E2EBEC);
    }

    /* Jump-to-month control — same box model as the app's real .form-select/
       .form-control (padding, radius, border, font-size), not a bespoke size,
       so it stays visually identical to the search/type controls beside it.
       Still needs its own class: a native <button> carries browser chrome
       that a shared .form-select class (meant for <select>) doesn't reset. */
    .ts-jump { position: relative; }
    .ts-jump-btn { appearance: none; -webkit-appearance: none; display: flex; align-items: center; gap: 8px; width: 100%; padding: 7px 18px; border: 1px solid var(--neutral-bg); border-radius: 13px; background: #fff; cursor: pointer; font-family: inherit; font-size: 16px; line-height: 30px; color: var(--ink); }
    .ts-jump-btn:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(var(--teal-rgb),.10); }
    .ts-jump-btn i:first-child { color: var(--ts-g3); font-size: 14px; }
    .ts-jump-caret { margin-left: auto; font-size: 11px; color: var(--ts-g3); }
    .ts-jump-menu { position: absolute; top: 52px; right: 0; z-index: 40; width: 220px; max-height: 280px; overflow-y: auto; background: #fff; border: 1px solid var(--ts-g4); border-radius: 14px; box-shadow: 0 16px 40px rgba(var(--teal-rgb),.16); padding: 6px; display: none; }
    .ts-jump-menu.open { display: block; }
    .ts-jump-menu button { display: flex; justify-content: space-between; width: 100%; border: none; background: none; font-family: inherit; font-size: 12.5px; color: var(--ink); padding: 9px 10px; border-radius: 9px; cursor: pointer; text-align: left; }
    .ts-jump-menu button:hover { background: var(--teal-soft); color: var(--teal); }
    .ts-jump-menu button .c { color: var(--ts-g3); font-variant-numeric: tabular-nums; }
    .ts-jump-empty { padding: 12px 10px; font-size: 12px; color: var(--ts-g3); }

    .ts-mode { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 400; color: var(--ts-g2); }
    .ts-mode .d { width: 7px; height: 7px; border-radius: 50%; }
    .ts-mode.face .d { background: var(--teal); }
    .ts-mode.hybrid .d { background: var(--teal-bright, #2EACB3); }
    .ts-mode.online .d { background: var(--positive); }

    .ts-status { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 500; padding: 4px 10px; border-radius: 20px; }
    .ts-status .d { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .ts-status.completed { background: var(--positive-bg); color: var(--positive); }
    .ts-status.ongoing { background: var(--teal-3); color: var(--teal); }
    .ts-status.scheduled { background: var(--warning-bg); color: var(--warning); }

    .att { display: inline-flex; align-items: center; cursor: pointer; padding: 2px; border-radius: 20px; transition: background .15s; }
    .att:hover { background: var(--line-2, #EEF4F4); }
    .att .avstack { display: flex; align-items: center; }
    /* Plain <span> placeholders, not <i> — Font Awesome is loaded globally and
       styles every bare <i> element as an icon glyph, which silently ate these
       (no image, no icon, so nothing painted). */
    .att .avstack .avc { width: 26px; height: 26px; border-radius: 50%; background: var(--ts-g4); border: 2px solid #fff; margin-left: -8px; display: block; object-fit: cover; }
    .att .avstack .avc:first-child { margin-left: 0; }
    .att .avstack .avc.avc-initials { background: var(--teal-3); color: var(--teal); display: grid; place-items: center; font-size: 9px; font-weight: 600; }
    .att .avstack .more { min-width: 26px; height: 26px; padding: 0 6px; border-radius: 13px; background: var(--teal-3); border: 2px solid #fff; margin-left: -8px; display: grid; place-items: center; font-size: 10px; font-weight: 600; color: var(--teal); }
    .att.solo .avstack .avc { margin-left: 0; }

    .ts-acts { display: flex; gap: 8px; }
    .abtn { width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--ts-g4); background: #fff; display: grid; place-items: center; color: var(--ts-g2); cursor: pointer; transition: .15s; }
    .abtn:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-soft); }

    /* timeline */
    .tl-group { margin-bottom: 2px; scroll-margin-top: 16px; }
    .tl-divider { display: flex; align-items: center; gap: 12px; margin: 24px 4px 12px; }
    .tl-divider .mo { font-size: 12px; font-weight: 600; color: var(--ink); letter-spacing: .2px; }
    .tl-divider .ct { font-size: 11px; color: var(--ts-g3); font-weight: 400; }
    .tl-divider .ln { flex: 1; height: 1px; background: var(--ts-g4); }
    .tl { background: #fff; border: 1px solid var(--ts-g4); border-radius: 14px; box-shadow: 0 1px 2px rgba(var(--teal-rgb),.05), 0 8px 24px rgba(var(--teal-rgb),.05); overflow: hidden; }
    .tlr { display: flex; align-items: center; gap: 18px; padding: 15px 20px; border-bottom: 1px solid var(--line-2, #EEF4F4); }
    .tlr:last-child { border-bottom: none; }
    .tlr:hover { background: var(--teal-soft); }
    .tlr .ts-date { flex: none; width: 52px; text-align: center; }
    .tlr .ts-date .d { font-size: 19px; font-weight: 600; color: var(--ink); letter-spacing: -.02em; line-height: 1; }
    .tlr .ts-date .wd { font-size: 9.5px; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; color: var(--ts-g3); margin-top: 3px; }
    .tlr .rail { flex: none; width: 1px; align-self: stretch; background: var(--ts-g4); }
    .tlr .ts-main { flex: 1.6; min-width: 0; }
    .tlr .nm { font-size: 14px; font-weight: 500; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tlr .sub { font-size: 12px; color: var(--ts-g2); margin-top: 3px; display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
    .tlr .sub .time { font-variant-numeric: tabular-nums; }
    .tlr .sub .drange { font-variant-numeric: tabular-nums; font-weight: 500; color: var(--teal); background: var(--teal-3); padding: 1px 8px; border-radius: 20px; }
    .tlr .sub .dot-sep { width: 3px; height: 3px; border-radius: 50%; background: var(--ts-g4); }
    .tlr .ts-right { flex: none; display: flex; align-items: center; gap: 20px; }

    /* inline-edit state — a real grid, never absolutely positioned, so fields
       never overlap the title (that was the bug). */
    .tlr.editing { display: flex; align-items: flex-start; gap: 18px; background: var(--teal-soft); flex-wrap: wrap; }
    .tlr.editing .editbox { flex: 1; min-width: 260px; }
    .edit-title { font-size: 14px; font-weight: 500; color: var(--ink); margin-bottom: 12px; }
    .edit-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .ef { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
    .ef span { font-size: 9.5px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--ts-g3); }
    .ef input { height: 38px; width: 100%; border: 1px solid var(--ts-g4); border-radius: 10px; padding: 0 11px; font-family: inherit; font-size: 12.5px; color: var(--ink); background: #fff; outline: none; }
    .ef input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(var(--teal-rgb),.10); }
    .edit-actions { flex: none; display: flex; gap: 8px; align-self: flex-end; padding-bottom: 1px; }
    .edit-actions .btn-primary { height: 38px; padding: 0 18px; border: none; border-radius: 10px; background: var(--teal); color: #fff; font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; appearance: none; -webkit-appearance: none; }
    .edit-actions .btn-primary:hover { background: var(--teal-2); }
    .edit-actions .btn-secondary { height: 38px; padding: 0 16px; border: 1px solid var(--ts-g4); border-radius: 10px; background: #fff; color: var(--ts-g2); font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; appearance: none; -webkit-appearance: none; }
    .edit-actions .btn-secondary:hover { border-color: var(--ts-g4); color: var(--ink); }
    @media(max-width: 820px) {
        .edit-grid { grid-template-columns: 1fr 1fr; }
        .edit-actions { width: 100%; justify-content: flex-end; margin-top: 6px; }
    }

    .expand { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 13px; font-size: 12px; font-weight: 500; color: var(--teal); cursor: pointer; background: var(--teal-soft); border-top: 1px solid var(--ts-g4); }
    .expand:hover { background: var(--teal-3); }
    .expand svg { transition: transform .2s; }
    .expand.open svg { transform: rotate(180deg); }

    .ts-loadmore { display: flex; flex-direction: column; align-items: center; gap: 6px; margin: 26px 0 4px; }
    .ts-loadmore button { border: 1px solid var(--ts-g4); background: #fff; font-family: inherit; font-size: 13px; font-weight: 500; color: var(--teal); padding: 11px 22px; border-radius: 20px; cursor: pointer; transition: .15s; display: flex; align-items: center; gap: 8px; }
    .ts-loadmore button:hover { border-color: var(--teal); background: var(--teal-soft); }
    .ts-rem { font-size: 11.5px; color: var(--ts-g3); }
    .ts-loadmore.done button { display: none; }

    .ts-empty { padding: 40px 20px; text-align: center; color: var(--ts-g3); font-size: 13px; }

    /* frosted attendee popover — same material as the WAI recommendation modal */
    .att-pop { position: fixed; z-index: 1060; width: 250px; border-radius: 22px; padding: 20px 20px 12px;
        background: rgba(255,255,255,.82); backdrop-filter: blur(28px) saturate(160%); -webkit-backdrop-filter: blur(28px) saturate(160%);
        border: 1px solid rgba(255,255,255,.7); box-shadow: 0 24px 70px rgba(var(--teal-rgb),.20), 0 2px 8px rgba(var(--teal-rgb),.06);
        opacity: 0; transform: translateY(8px) scale(.985); pointer-events: none; transition: opacity .28s ease, transform .3s cubic-bezier(.16,1,.3,1); }
    .att-pop.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
    .att-pop .k { display: flex; align-items: center; gap: 7px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .9px; color: var(--ts-g2); margin-bottom: 14px; }
    .att-pop .k .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--teal); }
    .att-pop .list { display: flex; flex-direction: column; gap: 12px; max-height: 236px; overflow-y: auto; margin-right: -8px; padding-right: 8px; }
    /* .a-row, not .row — Bootstrap's own .row grid class is active on this page
       (toolbar/page-heading use it) and its negative margins were clipping the
       avatar against the popover's rounded edge. Same fix as the Learning
       Programs audience popover earlier. */
    .att-pop .a-row { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--ink); font-weight: 400; }
    .att-pop .a-row .av { width: 24px; height: 24px; border-radius: 50%; background: var(--ts-g4); flex: none; object-fit: cover; }
    .att-pop .a-row .av.av-initials { background: var(--teal-3); color: var(--teal); display: grid; place-items: center; font-size: 9px; font-weight: 600; }

    @media(max-width: 900px) {
        .tlr { flex-wrap: wrap; }
        .tlr .ts-right { margin-left: 70px; }
    }
</style>
@endsection

@section('import-scripts')
<script>
    var TS_DEFAULT_PIC = @json($ts_defaultPic);
    function tsInitials(name) {
        var parts = (name || '').trim().split(/\s+/);
        var a = parts[0] ? parts[0][0] : '';
        var b = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (a + b).toUpperCase() || '?';
    }

    var TS_CAP = 8;          // rows shown per month before "Show all"
    var TS_MONTH_BATCH = 3;  // months revealed per "Load older"
    var TS_MONTHNAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    var tsMonths = [];       // [{key,label,sessions:[...]}], newest first
    var tsVisibleMonths = 3;
    var tsExpanded = {};     // month index -> bool
    var tsTotalSessions = 0;

    // The list endpoint returns each column pre-rendered as HTML (editColumn/
    // addColumn), same as every other DataTable page in this app. Rather than
    // re-querying the server for a second, differently-shaped payload, pull the
    // raw values already embedded for exactly this purpose (data-iso/data-raw —
    // the existing inline-edit handler already relied on the same attributes)
    // with cheap regexes instead of building throwaway DOM per row.
    function tsAttr(html, attr) {
        var m = html && html.match(new RegExp(attr + '="([^"]*)"'));
        return m ? m[1] : '';
    }
    function tsText(html) {
        if (!html) return '';
        var m = html.match(/>([^<]*)<\/span>/);
        return m ? m[1] : html.replace(/<[^>]*>/g, '');
    }
    // The attendees column already renders real profile-photo <img> tags
    // (server resolves them via Common::getResortUserPicture) for the first
    // 5 attendees — pull those URLs out instead of re-fetching anything.
    function tsAvatarUrls(html) {
        var urls = [], re = /<img src="([^"]*)"/g, m;
        while (html && (m = re.exec(html)) !== null) urls.push(m[1]);
        return urls;
    }

    function tsMapRow(row) {
        var startIso = tsAttr(row.start_date, 'data-iso');
        var endIso = tsAttr(row.end_date, 'data-iso');
        var startRaw = tsAttr(row.start_time, 'data-raw');
        var endRaw = tsAttr(row.end_time, 'data-raw');
        var d = startIso ? new Date(startIso + 'T00:00:00') : null;
        var statusText = tsText(row.status) || 'Scheduled';
        return {
            id: row.id,
            name: row.learning_name || 'Untitled program',
            mode: row.learning_type || 'face-to-face',
            startIso: startIso, endIso: endIso,
            startRaw: startRaw, endRaw: endRaw,
            startDisplay: tsText(row.start_time),
            endDisplay: tsText(row.end_time),
            day: d ? d.getDate() : 0,
            wd: d ? d.toLocaleDateString('en-US', { weekday: 'short' }) : '',
            year: d ? d.getFullYear() : 0,
            month: d ? d.getMonth() : 0,
            multiDay: startIso && endIso && startIso !== endIso,
            endDay: endIso ? new Date(endIso + 'T00:00:00').getDate() : null,
            monAbbr: d ? TS_MONTHNAMES[d.getMonth()].slice(0, 3) : '',
            statusText: statusText,
            statusClass: statusText.toLowerCase(),
            names: row.employee_names ? row.employee_names.split('|') : [],
            avatars: tsAvatarUrls(row.attendees),
            editUrl: 'javascript:void(0)'
        };
    }

    function tsGroupByMonth(rows) {
        var byKey = {};
        rows.forEach(function (s) {
            var key = s.year + '-' + s.month;
            if (!byKey[key]) byKey[key] = { key: key, y: s.year, m: s.month, label: TS_MONTHNAMES[s.month] + ' ' + s.year, sessions: [] };
            byKey[key].sessions.push(s);
        });
        var months = Object.keys(byKey).map(function (k) { return byKey[k]; });
        months.sort(function (a, b) { return (b.y - a.y) || (b.m - a.m); });
        months.forEach(function (mn) { mn.sessions.sort(function (a, b) { return b.day - a.day; }); });
        return months;
    }

    var ML = { 'face-to-face': 'Face-to-face', 'hybrid': 'Hybrid', 'online': 'Online' };
    var MC = { 'face-to-face': 'face', 'hybrid': 'hybrid', 'online': 'online' };

    function tsAvatarNode(name, url) {
        if (url && url !== TS_DEFAULT_PIC) {
            return '<img class="avc" src="' + url + '" alt="" title="' + name + '">';
        }
        return '<span class="avc avc-initials" title="' + name + '">' + tsInitials(name) + '</span>';
    }

    function tsStack(s) {
        var n = s.names.length;
        var show = Math.min(n, 3), h = '';
        for (var i = 0; i < show; i++) h += tsAvatarNode(s.names[i] || '', s.avatars[i]);
        if (n > 3) h += '<span class="more">+' + (n - 3) + '</span>';
        var people = s.names.map(function (nm, i) { return { n: nm, a: s.avatars[i] || '' }; });
        return '<span class="att' + (n <= 1 ? ' solo' : '') + '" data-people="' + encodeURIComponent(JSON.stringify(people)) + '"><span class="avstack">' + h + '</span></span>';
    }

    var TS_EDIT_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
    var TS_ATTEND_ICON = '<i class="fas fa-calendar-check" aria-hidden="true"></i>';

    // Multi-day sessions never cram a range into the small date box — it shows
    // only the start day. The full span becomes a teal pill at the front of the
    // sub-line instead. One shared pair of builders so this can't drift out of
    // sync between initial render, cancel-restore, and update-restore.
    function tsDateNodeHTML(s) {
        return '<div class="d tnum">' + s.day + '</div><div class="wd">' + s.wd + '</div>';
    }
    function tsSubLineHTML(s) {
        var rangeChip = s.multiDay
            ? '<span class="drange tnum">' + s.day + '&ndash;' + s.endDay + ' ' + s.monAbbr + '</span><span class="dot-sep"></span>'
            : '';
        return rangeChip +
            '<span class="time tnum">' + s.startDisplay + ' &ndash; ' + s.endDisplay + '</span>' +
            '<span class="dot-sep"></span>' +
            '<span class="ts-mode ' + (MC[s.mode] || 'face') + '"><span class="d"></span>' + (ML[s.mode] || s.mode) + '</span>';
    }

    function tsRowInnerHTML(s) {
        return '<div class="ts-date">' + tsDateNodeHTML(s) + '</div>' +
            '<div class="rail"></div>' +
            '<div class="ts-main"><div class="nm">' + s.name + '</div>' +
                '<div class="sub">' + tsSubLineHTML(s) + '</div>' +
            '</div>' +
            '<div class="ts-right">' + tsStack(s) +
                '<span class="ts-status ' + s.statusClass + '"><span class="d"></span>' + s.statusText + '</span>' +
                '<div class="ts-acts">' +
                    '<button type="button" class="abtn ts-edit-btn" title="Edit" data-schedule-id="' + s.id + '">' + TS_EDIT_ICON + '</button>' +
                    '<a href="{{ route("learning.schedule.attendance") }}?schedule_id=' + btoa(String(s.id)) + '" class="abtn" title="Mark Attendance">' + TS_ATTEND_ICON + '</a>' +
                '</div>' +
            '</div>';
    }

    function tsRowHTML(s) {
        return '<div class="tlr" data-schedule-id="' + s.id + '">' + tsRowInnerHTML(s) + '</div>';
    }

    function tsRender() {
        var html = '';
        var shownCount = Math.min(tsVisibleMonths, tsMonths.length);
        if (tsMonths.length === 0) {
            html = '<div class="ts-empty">No training sessions found.</div>';
        }
        for (var i = 0; i < shownCount; i++) {
            var mn = tsMonths[i], ss = mn.sessions;
            var showAll = tsExpanded[i] || ss.length <= TS_CAP;
            var shown = showAll ? ss : ss.slice(0, TS_CAP);
            html += '<div class="tl-group" id="ts-mo-' + i + '"><div class="tl-divider"><span class="mo">' + mn.label + '</span><span class="ct">' + ss.length + ' session' + (ss.length > 1 ? 's' : '') + '</span><span class="ln"></span></div><div class="tl">';
            html += shown.map(tsRowHTML).join('');
            if (ss.length > TS_CAP) {
                html += '<div class="expand' + (tsExpanded[i] ? ' open' : '') + '" data-mi="' + i + '">' +
                    (tsExpanded[i] ? 'Show less' : 'Show all ' + ss.length + ' sessions in ' + mn.label.split(' ')[0]) +
                    ' <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></div>';
            }
            html += '</div></div>';
        }
        document.getElementById('ts-timeline').innerHTML = html;

        var lm = document.getElementById('tsLoadmore'), rem = document.getElementById('tsRem');
        if (tsMonths.length === 0) {
            lm.classList.add('done'); rem.textContent = '';
        } else if (tsVisibleMonths >= tsMonths.length) {
            lm.classList.add('done');
            rem.textContent = 'All ' + tsMonths.length + ' months shown · ' + tsTotalSessions + ' sessions total';
        } else {
            lm.classList.remove('done');
            var remMonths = tsMonths.length - tsVisibleMonths;
            rem.textContent = remMonths + ' earlier month' + (remMonths > 1 ? 's' : '') + ' available';
        }

        tsBindStacks();
        tsBindExpanders();
        tsBindEdit();
    }

    function tsBindExpanders() {
        document.querySelectorAll('.expand').forEach(function (ex) {
            ex.addEventListener('click', function () {
                var mi = +ex.dataset.mi;
                tsExpanded[mi] = !tsExpanded[mi];
                tsRender();
                var el = document.getElementById('ts-mo-' + mi);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    }

    document.getElementById('tsLoadBtn').addEventListener('click', function () {
        tsVisibleMonths = Math.min(tsVisibleMonths + TS_MONTH_BATCH, tsMonths.length);
        tsRender();
    });

    /* jump-to-month menu */
    (function () {
        var btn = document.getElementById('tsJumpBtn'), menu = document.getElementById('tsJumpMenu');
        btn.addEventListener('click', function (e) { e.stopPropagation(); menu.classList.toggle('open'); });
        document.addEventListener('click', function () { menu.classList.remove('open'); });
        menu.addEventListener('click', function (e) {
            var b = e.target.closest('button[data-i]');
            if (!b) return;
            e.stopPropagation();
            var i = +b.dataset.i;
            if (i >= tsVisibleMonths) { tsVisibleMonths = i + 1; tsRender(); }
            menu.classList.remove('open');
            setTimeout(function () {
                var el = document.getElementById('ts-mo-' + i);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 60);
        });
    })();

    function tsRenderJumpMenu() {
        var menu = document.getElementById('tsJumpMenu');
        if (!tsMonths.length) { menu.innerHTML = '<div class="ts-jump-empty">No sessions yet</div>'; return; }
        menu.innerHTML = tsMonths.map(function (m, i) {
            return '<button type="button" data-i="' + i + '">' + m.label + '<span class="c">' + m.sessions.length + '</span></button>';
        }).join('');
    }

    /* frosted attendee popover — verbatim scroll/hover-guard logic from the
       finalized reference component: overPop flag + pop.contains(e.target)
       guard so scrolling inside the popover (or hovering it) never closes it. */
    var attPopBound = false;
    function tsBindStacks() {
        var pop = document.getElementById('attPop'), listEl = document.getElementById('attList'), countEl = document.getElementById('attCount');
        var pinned = false, overPop = false;
        function fill(people) {
            countEl.textContent = people.length + ' attendee' + (people.length > 1 ? 's' : '');
            listEl.innerHTML = people.map(function (p) {
                var av = (p.a && p.a !== TS_DEFAULT_PIC)
                    ? '<img class="av" src="' + p.a + '" alt="">'
                    : '<span class="av av-initials">' + tsInitials(p.n) + '</span>';
                return '<div class="a-row">' + av + p.n + '</div>';
            }).join('');
        }
        function place(el) {
            var r = el.getBoundingClientRect();
            pop.style.visibility = 'hidden'; pop.classList.add('open');
            var pw = pop.offsetWidth, ph = pop.offsetHeight;
            var left = r.left + r.width / 2 - pw / 2;
            left = Math.max(12, Math.min(left, window.innerWidth - pw - 12));
            var top = r.bottom + 8;
            if (top + ph > window.innerHeight - 12) top = r.top - ph - 8;
            pop.style.left = left + 'px'; pop.style.top = top + 'px';
            pop.style.visibility = '';
        }
        function open(el) {
            var people = [];
            try { people = JSON.parse(decodeURIComponent(el.dataset.people)); } catch (e) {}
            fill(people); place(el); pop.classList.add('open');
        }
        function close() { if (!pinned && !overPop) pop.classList.remove('open'); }

        document.querySelectorAll('.att').forEach(function (a) {
            a.addEventListener('mouseenter', function () { if (!pinned) open(a); });
            a.addEventListener('mouseleave', function () { setTimeout(close, 120); });
            a.addEventListener('click', function (e) { e.stopPropagation(); pinned = true; open(a); });
        });

        if (!attPopBound) {
            attPopBound = true;
            pop.addEventListener('mouseenter', function () { overPop = true; pop.classList.add('open'); });
            pop.addEventListener('mouseleave', function () { overPop = false; setTimeout(function () { if (!overPop) pop.classList.remove('open'); }, 60); });
            document.addEventListener('click', function (e) {
                if (!pop.contains(e.target) && !e.target.closest('.att')) { pinned = false; overPop = false; pop.classList.remove('open'); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { pinned = false; overPop = false; pop.classList.remove('open'); }
            });
            window.addEventListener('scroll', function (e) {
                if (pinned || overPop || pop.contains(e.target)) return;
                pop.classList.remove('open');
            }, true);
        }
    }

    /* ---- Inline edit: same endpoint/payload/validation as before — native
       date/time inputs in a proper grid (no overlap), converted to the
       server's expected d/m/Y before sending. Only one row editable at a
       time; Update rebuilds the row via the same tsRowInnerHTML() the
       initial render uses, so display logic can't drift out of sync. ---- */
    function tsIsoToDmy(iso) {
        if (!iso) return '';
        var p = iso.split('-');
        return (p.length === 3) ? (p[2] + '/' + p[1] + '/' + p[0]) : '';
    }
    function tsTimeToDisplay(raw) {
        if (!raw) return '';
        var parts = raw.split(':');
        var h = parseInt(parts[0], 10);
        var m = (parts[1] || '00').padStart(2, '0');
        if (isNaN(h)) return raw;
        var period = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12; if (h12 === 0) h12 = 12;
        return String(h12).padStart(2, '0') + ':' + m + ' ' + period;
    }

    function tsBindEdit() {
        document.querySelectorAll('.ts-edit-btn').forEach(function (btn) {
            if (btn._tsBound) return;
            btn._tsBound = true;
            btn.addEventListener('click', function () { tsEnterEdit(btn); });
        });
    }

    // Only one row editable at a time.
    var tsEditingId = null;

    function tsEditRowHTML(s, scheduleId) {
        return '<div class="editbox">' +
            '<div class="edit-title">' + s.name + '</div>' +
            '<div class="edit-grid">' +
                '<div class="ef"><span>From date</span><input type="text" autocomplete="off" id="ts-edit-sd-' + scheduleId + '" value="' + (s.startIso || '') + '"></div>' +
                '<div class="ef"><span>To date</span><input type="text" autocomplete="off" id="ts-edit-ed-' + scheduleId + '" value="' + (s.endIso || '') + '"></div>' +
                '<div class="ef"><span>Start time</span><input type="text" autocomplete="off" id="ts-edit-st-' + scheduleId + '" value="' + (s.startRaw || '').slice(0, 5) + '"></div>' +
                '<div class="ef"><span>End time</span><input type="text" autocomplete="off" id="ts-edit-et-' + scheduleId + '" value="' + (s.endRaw || '').slice(0, 5) + '"></div>' +
            '</div>' +
        '</div>' +
        '<div class="edit-actions">' +
            '<button type="button" class="btn-secondary ts-cancel-btn" data-schedule-id="' + scheduleId + '">Cancel</button>' +
            '<button type="button" class="btn-primary ts-update-btn" data-schedule-id="' + scheduleId + '">Update</button>' +
        '</div>';
    }

    function tsEnterEdit(btn) {
        var scheduleId = btn.dataset.scheduleId;
        var s = tsRowsById[scheduleId];
        if (!s) return;
        if (tsEditingId && tsEditingId !== scheduleId) tsCancelEditById(tsEditingId);

        var $row = $(btn).closest('.tlr');
        $row.data('original-html', $row.html());
        $row.addClass('editing').html(tsEditRowHTML(s, scheduleId));
        tsEditingId = scheduleId;
        tsBindEdit();

        // Same themed flatpickr popover used app-wide, not the browser's
        // native date picker — appendTo:body so it isn't clipped by the row.
        // altInput shows dd-mmm-yy (e.g. 25-May-26); dateFormat stays 'Y-m-d' so
        // .val() on the real #ts-edit-sd-/#ts-edit-ed- inputs is untouched — the
        // update handler below still reads ISO and converts to d/m/Y for the
        // endpoint exactly as before.
        flatpickr('#ts-edit-sd-' + scheduleId, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd-M-y', defaultDate: s.startIso || null, allowInput: true, appendTo: document.body });
        flatpickr('#ts-edit-ed-' + scheduleId, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd-M-y', defaultDate: s.endIso || null, allowInput: true, appendTo: document.body });

        // Same sibling field this used to be a native <input type="time"> on —
        // altInput shows 12h AM/PM, dateFormat stays 24h 'H:i' so .val() below
        // (and the update handler's .length===5 check) is unchanged.
        // developer.min.css force-hides .flatpickr-am-pm below 424px (a mobile
        // tweak for the Duty Roster time pickers elsewhere in the app) — undo
        // it just for these instances so the AM/PM toggle stays visible here.
        var tsTimeOnReady = function (selectedDates, dateStr, instance) {
            instance.amPM.style.setProperty('display', 'inline-block', 'important');
        };
        var tsTimeOpts = { enableTime: true, noCalendar: true, dateFormat: 'H:i', altInput: true, altFormat: 'h:i K', time_24hr: false, minuteIncrement: 1, allowInput: true, appendTo: document.body, onReady: tsTimeOnReady };
        flatpickr('#ts-edit-st-' + scheduleId, Object.assign({ defaultDate: (s.startRaw || '').slice(0, 5) || null }, tsTimeOpts));
        flatpickr('#ts-edit-et-' + scheduleId, Object.assign({ defaultDate: (s.endRaw || '').slice(0, 5) || null }, tsTimeOpts));
    }

    function tsExitEdit($row) {
        $row.removeClass('editing');
        tsEditingId = null;
        tsBindEdit();
    }

    function tsCancelEditById(scheduleId) {
        var $row = $('.tlr[data-schedule-id="' + scheduleId + '"]');
        var original = $row.data('original-html');
        if (original) $row.html(original);
        tsExitEdit($row);
    }

    $(document).on('click', '.ts-cancel-btn', function () {
        tsCancelEditById($(this).data('schedule-id'));
    });

    $(document).on('click', '.ts-update-btn', function () {
        var $row = $(this).closest('.tlr');
        var scheduleId = $(this).data('schedule-id');
        var s = tsRowsById[scheduleId];

        var startDateIso = $('#ts-edit-sd-' + scheduleId).val();
        var endDateIso = $('#ts-edit-ed-' + scheduleId).val();
        var startTimeVal = $('#ts-edit-st-' + scheduleId).val();
        var endTimeVal = $('#ts-edit-et-' + scheduleId).val();

        // Endpoint validates date_format:d/m/Y — flatpickr is configured with
        // dateFormat:'Y-m-d' so .val() reads ISO, so convert before sending; nothing else about the
        // save contract changes.
        var startDateVal = startDateIso ? tsIsoToDmy(startDateIso) : '';
        var endDateVal = endDateIso ? tsIsoToDmy(endDateIso) : '';

        var data = { _token: '{{ csrf_token() }}', id: scheduleId };
        if (startDateVal) data.start_date = startDateVal;
        if (endDateVal) data.end_date = endDateVal;
        if (startTimeVal) data.start_time = startTimeVal;
        if (endTimeVal) data.end_time = endTimeVal;

        $.ajax({
            url: '{{ route("learning.schedule.update") }}',
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    toastr.success('Schedule updated successfully!', 'Success', { positionClass: 'toast-bottom-right' });
                    s.startIso = startDateIso || s.startIso;
                    s.endIso = endDateIso || s.endIso;
                    s.startRaw = startTimeVal ? (startTimeVal.length === 5 ? startTimeVal + ':00' : startTimeVal) : s.startRaw;
                    s.endRaw = endTimeVal ? (endTimeVal.length === 5 ? endTimeVal + ':00' : endTimeVal) : s.endRaw;
                    s.startDisplay = tsTimeToDisplay(s.startRaw);
                    s.endDisplay = tsTimeToDisplay(s.endRaw);
                    var d = new Date(s.startIso + 'T00:00:00');
                    s.day = d.getDate(); s.wd = d.toLocaleDateString('en-US', { weekday: 'short' });
                    s.year = d.getFullYear(); s.month = d.getMonth();
                    s.monAbbr = TS_MONTHNAMES[s.month].slice(0, 3);
                    s.multiDay = s.startIso && s.endIso && s.startIso !== s.endIso;
                    s.endDay = s.endIso ? new Date(s.endIso + 'T00:00:00').getDate() : null;

                    $row.removeClass('editing').html(tsRowInnerHTML(s));
                    tsExitEdit($row);
                } else {
                    toastr.error(response.message || 'Failed to update. Try again!', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error updating the schedule.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    /* ---- Load + wire everything ---- */
    var tsRowsById = {};

    function tsLoad() {
        document.getElementById('ts-timeline').innerHTML = '<div class="ts-empty">Loading sessions&hellip;</div>';
        var params = {
            searchTerm: $('#searchInput').val(),
            type: $('#typeFilter').val()
        };
        var qs = new URLSearchParams(window.location.search);
        if (qs.get('status')) params.status = qs.get('status');

        $.ajax({
            url: '{{ route("learning.schedule.list") }}',
            type: 'GET',
            data: params,
            success: function (response) {
                var rows = (response && response.data) ? response.data : [];
                var mapped = rows.map(tsMapRow);
                tsRowsById = {};
                mapped.forEach(function (s) { tsRowsById[s.id] = s; });
                tsMonths = tsGroupByMonth(mapped);
                tsTotalSessions = mapped.length;
                tsVisibleMonths = 3;
                tsExpanded = {};
                tsRender();
                tsRenderJumpMenu();
            },
            error: function () {
                document.getElementById('ts-timeline').innerHTML = '<div class="ts-empty">Failed to load training sessions.</div>';
            }
        });
    }

    $(document).ready(function () {
        tsLoad();
        $('#searchInput, #typeFilter').on('keyup change', function () { tsLoad(); });
    });
</script>
@endsection
