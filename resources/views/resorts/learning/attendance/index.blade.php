@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@php
    // Client-side needs to tell "real photo" from "no photo on file" so it can
    // draw an initials avatar instead of the generic default image — Common::
    // getResortUserPicture() always returns a URL, falling back to this exact
    // one when the employee has none.
    $ma_defaultPic = url(config('settings.default_picture'));
@endphp

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card ma-panel">
                <div class="card-header">
                    <!-- session header — populated live once data loads -->
                    <div class="ma-hero" id="maHero">
                        <div class="ma-htop">
                            <div>
                                <div class="ma-cat">Mark Attendance</div>
                                <h1 class="ma-h1" id="maTitle">Loading&hellip;</h1>
                            </div>
                            <div class="ma-summary" id="maSummary" style="display:none">
                                <div class="ma-ring">
                                    <svg width="64" height="64"><circle cx="32" cy="32" r="27" fill="none" stroke="var(--line-2, #EEF4F4)" stroke-width="6"/><circle id="maRingArc" cx="32" cy="32" r="27" fill="none" stroke="var(--positive)" stroke-width="6" stroke-linecap="round" stroke-dasharray="169.6" stroke-dashoffset="169.6"/></svg>
                                    <div class="ma-ring-c" id="maRingNum">0/0</div>
                                </div>
                                <div class="ma-lbl"><b id="maPresentCount">0 present</b> marked so far</div>
                            </div>
                        </div>
                        <div class="ma-metastrip" id="maMetastrip" style="display:none">
                            <div class="mi"><div class="ml">Type</div><div class="mv" id="maMetaType">&mdash;</div></div>
                            <div class="mi"><div class="ml">Date</div><div class="mv tnum" id="maMetaDate">&mdash;</div></div>
                            <div class="mi"><div class="ml">Time</div><div class="mv tnum" id="maMetaTime">&mdash;</div></div>
                            <div class="mi"><div class="ml">Trainer</div><div class="mv" id="maMetaTrainer">&mdash;</div></div>
                        </div>
                    </div>

                    <!-- search + actions, one row — search scoped to this one session
                         (schedule_id in the URL), so it lives next to the actions it
                         filters rather than its own separate row above the hero -->
                    <div class="ma-toolbar-actions">
                        <div class="input-group ma-search">
                            <input type="search" class="form-control" id="searchInput" placeholder="Search employees" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <div class="ma-actions-row">
                            <button type="button" class="ma-btn-secondary" id="maAllPresent">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                Mark all present
                            </button>
                            <button type="button" class="ma-btn-primary" id="maSave">Save attendance</button>
                        </div>
                    </div>
                </div>

                <div id="ma-roster"></div>

                <div class="ma-footer">
                    <span class="ma-sum" id="maFooterSum"></span>
                    <div class="ma-pager" id="maPager"></div>
                </div>
            </div>
        </div>
    </div>
@include('resorts.learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    :root {
        --ma-g2: var(--muted, #6B7378);
        --ma-g3: var(--faint, #99A1A5);
        --ma-g4: var(--line, #E2EBEC);
    }

    /* session header — first thing in the card again now that search moved
       down next to the actions row; the 16px top margin (not full-bleed)
       still leaves the card's own rounded top corner showing above it. */
    .ma-hero { margin: 16px -20px 16px; padding: 24px 26px; background: linear-gradient(180deg, var(--teal-soft), #fff); border-top: 1px solid var(--ma-g4); border-bottom: 1px solid var(--ma-g4); }
    .ma-htop { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
    .ma-cat { font-size: 11px; font-weight: 600; letter-spacing: .7px; text-transform: uppercase; color: var(--teal); }
    .ma-h1 { font-size: 20px; font-weight: 500; letter-spacing: -.01em; margin-top: 6px; color: var(--ink); }

    /* segmented meta band — same shape/values as the Learning Program detail hero */
    .ma-metastrip { display: flex; flex-wrap: wrap; gap: 0; margin-top: 20px; border: 1px solid var(--ma-g4); border-radius: 14px; background: rgba(255,255,255,.6); overflow: hidden; }
    .ma-metastrip .mi { flex: 1; min-width: 120px; padding: 13px 16px; border-right: 1px solid var(--ma-g4); }
    .ma-metastrip .mi:last-child { border-right: none; }
    .ma-metastrip .mi .ml { font-size: 9.5px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--ma-g3); margin-bottom: 4px; }
    .ma-metastrip .mi .mv { font-size: 12.5px; color: var(--ink); font-weight: 400; display: flex; align-items: center; gap: 6px; }
    .ma-mode { display: inline-flex; align-items: center; gap: 6px; }
    .ma-mode .d { width: 7px; height: 7px; border-radius: 50%; }
    .ma-mode.face .d { background: var(--teal); }
    .ma-mode.hybrid .d { background: var(--teal-bright, #2EACB3); }
    .ma-mode.online .d { background: var(--positive); }

    /* attendance summary ring */
    .ma-summary { flex: none; display: flex; align-items: center; gap: 14px; }
    .ma-ring { position: relative; width: 64px; height: 64px; }
    .ma-ring svg { transform: rotate(-90deg); }
    .ma-ring-c { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600; color: var(--ink); font-variant-numeric: tabular-nums; }
    .ma-lbl { font-size: 11px; color: var(--ma-g2); font-weight: 400; }
    .ma-lbl b { display: block; font-size: 13px; color: var(--ink); font-weight: 600; }

    /* search + actions share one row, so the card isn't carrying an extra
       row's height just for the search box. */
    .ma-toolbar-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 4px; }
    .ma-search { flex: 1 1 auto; min-width: 200px; max-width: 320px; }
    .ma-search .form-control { height: 40px; padding: 0 40px 0 14px; }
    .ma-search.input-group > i { top: 50%; transform: translateY(-50%); font-size: 15px; }
    .ma-actions-row { display: flex; gap: 12px; flex: none; }

    /* self-contained buttons — not relying on .form-select working on a
       <button>, that silently loses to native button chrome. */
    .ma-btn-secondary { height: 40px; padding: 0 16px; border: 1px solid var(--ma-g4); border-radius: 12px; background: #fff; color: var(--ink); font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; transition: .15s; display: flex; align-items: center; gap: 7px; appearance: none; -webkit-appearance: none; }
    .ma-btn-secondary:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-soft); }
    .ma-btn-secondary:disabled { opacity: .5; cursor: not-allowed; }
    .ma-btn-primary { height: 40px; padding: 0 20px; border: none; border-radius: 12px; background: var(--teal); color: #fff; font-family: inherit; font-size: 13px; font-weight: 500; cursor: pointer; transition: .15s; appearance: none; -webkit-appearance: none; }
    .ma-btn-primary:hover { background: var(--teal-2); }
    .ma-btn-primary:disabled { opacity: .5; cursor: not-allowed; background: var(--ma-g4); }

    /* roster */
    #ma-roster { margin: 0 -20px; }
    .ma-remp { display: flex; align-items: center; gap: 16px; padding: 14px 20px; border-bottom: 1px solid var(--line-2, #EEF4F4); }
    .ma-remp:last-child { border-bottom: none; }
    .ma-remp:hover { background: var(--teal-soft); }
    .ma-remp .av { flex: none; width: 38px; height: 38px; border-radius: 50%; object-fit: cover; background: var(--ma-g4); }
    .ma-remp .av-initials { flex: none; width: 38px; height: 38px; border-radius: 50%; background: var(--teal-3); color: var(--teal); display: grid; place-items: center; font-size: 13px; font-weight: 600; }
    .ma-remp .who { flex: 1; min-width: 0; }
    .ma-remp .nm { font-size: 13.5px; font-weight: 500; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ma-remp .meta { font-size: 11.5px; color: var(--ma-g2); margin-top: 2px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .ma-remp .meta .sep { width: 3px; height: 3px; border-radius: 50%; background: var(--ma-g4); flex: none; }

    /* present/absent segmented toggle */
    .ma-toggle { flex: none; display: flex; border: 1px solid var(--ma-g4); border-radius: 20px; overflow: hidden; background: #fff; }
    .ma-toggle button { border: none; background: none; font-family: inherit; font-size: 12px; font-weight: 500; color: var(--ma-g2); padding: 7px 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: .15s; appearance: none; -webkit-appearance: none; }
    .ma-toggle button + button { border-left: 1px solid var(--ma-g4); }
    .ma-toggle button:hover { background: var(--line-2, #EEF4F4); }
    .ma-toggle button.on-present { background: var(--positive-bg); color: var(--positive); }
    .ma-toggle button.on-absent { background: var(--critical-bg); color: var(--critical); }
    .ma-toggle button .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .5; }
    .ma-toggle button.on-present .dot, .ma-toggle button.on-absent .dot { opacity: 1; }

    .ma-empty { padding: 40px 20px; text-align: center; color: var(--ma-g3); font-size: 13px; }

    .ma-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; padding: 0 4px; flex-wrap: wrap; gap: 10px; }
    .ma-sum { font-size: 12px; color: var(--ma-g3); }
    .ma-pager { display: flex; gap: 4px; }
    .ma-pager button { min-width: 30px; height: 30px; border: 1px solid var(--ma-g4); background: #fff; border-radius: 8px; font-family: inherit; font-size: 12px; color: var(--ma-g2); cursor: pointer; font-weight: 500; appearance: none; -webkit-appearance: none; }
    .ma-pager button:hover { border-color: var(--teal); color: var(--teal); }
    .ma-pager button.on { background: var(--teal); color: #fff; border-color: var(--teal); }

    @media(max-width: 900px) {
        .ma-remp { flex-wrap: wrap; }
    }
</style>
@endsection

@section('import-scripts')
<script>
    var MA_DEFAULT_PIC = @json($ma_defaultPic);
    // The page is always opened for exactly one session (schedule_id in the
    // URL) — the only link to this page anywhere in the app always passes it.
    var MA_SCHEDULE_ID = @json($scheduleId ?: null);
    var MA_PAGE_SIZE = 8;
    var maRows = [];   // all loaded rows (search scoped)
    var maState = {};  // employee_id -> 'Present' | 'Absent'
    var maPage = 1;

    function maAttr(html, attr) {
        var m = html && html.match(new RegExp(attr + '="([^"]*)"'));
        return m ? m[1] : '';
    }
    function maImgSrc(html) {
        var m = html && html.match(/<img src="([^"]*)"/);
        return m ? m[1] : '';
    }
    function maText(html, selector) {
        if (!html) return '';
        var m = html.match(/<span class="userReviewTasks-btn">([^<]*)<\/span>/);
        return m ? m[1].trim() : '';
    }
    function maInitials(name) {
        var parts = (name || '').trim().split(/\s+/);
        var a = parts[0] ? parts[0][0] : '';
        var b = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (a + b).toUpperCase() || '?';
    }
    function maAvatarNode(name, url) {
        if (url && url !== MA_DEFAULT_PIC) {
            return '<img class="av" src="' + url + '" alt="">';
        }
        return '<span class="av-initials">' + maInitials(name) + '</span>';
    }

    function maMapRow(row) {
        return {
            id: row.id,
            empId: row.Emp_ID,
            name: maText(row.employee_name) || 'Unknown',
            avatar: maImgSrc(row.employee_name),
            position: row.position,
            trainingName: row.training_name,
            trainingType: row.training_type,
            startDate: row.start_date,
            endDate: row.end_date,
            startTime: row.start_time,
            endTime: row.end_time,
            trainer: row.trainer || ''
        };
    }

    function maTimeDisplay(raw) {
        if (!raw) return '';
        var parts = String(raw).split(':');
        var h = parseInt(parts[0], 10);
        var m = (parts[1] || '00').padStart(2, '0');
        if (isNaN(h)) return raw;
        var period = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12; if (h12 === 0) h12 = 12;
        return String(h12).padStart(2, '0') + ':' + m + ' ' + period;
    }

    var MC = { 'face-to-face': 'face', 'hybrid': 'hybrid', 'online': 'online' };
    var ML = { 'face-to-face': 'Face-to-face', 'hybrid': 'Hybrid', 'online': 'Online' };

    function maRenderHero() {
        var ready = !!MA_SCHEDULE_ID && maRows.length > 0;
        document.getElementById('maSummary').style.display = ready ? 'flex' : 'none';
        document.getElementById('maMetastrip').style.display = ready ? 'flex' : 'none';

        if (ready) {
            var r = maRows[0];
            document.getElementById('maTitle').textContent = r.trainingName || 'Session';
            document.getElementById('maMetaType').innerHTML = '<span class="ma-mode ' + (MC[r.trainingType] || 'face') + '"><span class="d"></span>' + (ML[r.trainingType] || r.trainingType || '-') + '</span>';
            document.getElementById('maMetaDate').textContent = r.startDate === r.endDate ? r.startDate : (r.startDate + ' – ' + r.endDate);
            document.getElementById('maMetaTime').textContent = maTimeDisplay(r.startTime) + ' – ' + maTimeDisplay(r.endTime);
            document.getElementById('maMetaTrainer').textContent = r.trainer || '-';
        } else if (!MA_SCHEDULE_ID) {
            document.getElementById('maTitle').textContent = 'No session selected';
        } else {
            document.getElementById('maTitle').textContent = 'No employees found';
        }
    }

    function maUpdateSummary() {
        if (!MA_SCHEDULE_ID) return;
        var total = maRows.length;
        var present = maRows.filter(function (r) { return maState[r.id] === 'Present'; }).length;
        document.getElementById('maRingNum').textContent = present + '/' + total;
        document.getElementById('maPresentCount').textContent = present + ' present';
        var circ = 2 * Math.PI * 27;
        var offset = total > 0 ? circ * (1 - present / total) : circ;
        document.getElementById('maRingArc').setAttribute('stroke-dashoffset', offset);
    }

    function maRowHTML(r) {
        var s = maState[r.id];
        return '<div class="ma-remp" data-id="' + r.id + '">' +
            maAvatarNode(r.name, r.avatar) +
            '<div class="who"><div class="nm">' + r.name + '</div>' +
                '<div class="meta"><span class="tnum">' + (r.empId || '') + '</span><span class="sep"></span><span>' + (r.position || '') + '</span></div></div>' +
            '<div class="ma-toggle">' +
                '<button type="button" class="' + (s === 'Present' ? 'on-present' : '') + '" data-v="Present"><span class="dot"></span>Present</button>' +
                '<button type="button" class="' + (s === 'Absent' ? 'on-absent' : '') + '" data-v="Absent"><span class="dot"></span>Absent</button>' +
            '</div>' +
        '</div>';
    }

    function maRender() {
        maRenderHero();
        var el = document.getElementById('ma-roster');
        if (!maRows.length) {
            el.innerHTML = '<div class="ma-empty">No employees found.</div>';
        } else {
            var start = (maPage - 1) * MA_PAGE_SIZE;
            var pageRows = maRows.slice(start, start + MA_PAGE_SIZE);
            el.innerHTML = pageRows.map(maRowHTML).join('');
        }
        document.getElementById('maFooterSum').textContent = maRows.length + ' employee' + (maRows.length === 1 ? '' : 's') + ' enrolled';
        maRenderPager();
        maUpdateSummary();
        maBindRows();
    }

    function maRenderPager() {
        var totalPages = Math.max(1, Math.ceil(maRows.length / MA_PAGE_SIZE));
        var pager = document.getElementById('maPager');
        if (totalPages <= 1) { pager.innerHTML = ''; return; }
        var html = '<button type="button" data-p="1">&laquo;</button><button type="button" data-p="' + Math.max(1, maPage - 1) + '">&lsaquo;</button>';
        for (var p = 1; p <= totalPages; p++) {
            html += '<button type="button" class="' + (p === maPage ? 'on' : '') + '" data-p="' + p + '">' + p + '</button>';
        }
        html += '<button type="button" data-p="' + Math.min(totalPages, maPage + 1) + '">&rsaquo;</button><button type="button" data-p="' + totalPages + '">&raquo;</button>';
        pager.innerHTML = html;
        pager.querySelectorAll('button').forEach(function (b) {
            b.addEventListener('click', function () { maPage = +b.dataset.p; maRender(); });
        });
    }

    function maBindRows() {
        document.querySelectorAll('.ma-remp .ma-toggle button').forEach(function (b) {
            b.addEventListener('click', function () {
                var id = b.closest('.ma-remp').dataset.id;
                var v = b.dataset.v;
                maState[id] = (maState[id] === v) ? undefined : v;
                maRender();
            });
        });
    }

    document.getElementById('maAllPresent').addEventListener('click', function () {
        if (!MA_SCHEDULE_ID) return;
        maRows.forEach(function (r) { maState[r.id] = 'Present'; });
        maRender();
    });

    document.getElementById('maSave').addEventListener('click', function () {
        if (!MA_SCHEDULE_ID) return;
        var employees = [];
        maRows.forEach(function (r) {
            if (maState[r.id]) employees.push({ employee_id: r.id, status: maState[r.id] });
        });
        if (!employees.length) {
            toastr.error('Mark at least one employee before saving.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        var $btn = $('#maSave');
        var originalText = $btn.text();
        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ route("attendance.mark") }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: JSON.stringify({ training_schedule_id: MA_SCHEDULE_ID, employees: employees }),
            contentType: 'application/json',
            success: function (response) {
                if (response.success === false) {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
                toastr.success(response.message || 'Attendance updated successfully', 'Success', { positionClass: 'toast-bottom-right' });
                $btn.text('Saved · ' + employees.filter(function (e) { return e.status === 'Present'; }).length + ' present');
                setTimeout(function () { $btn.text(originalText); }, 1600);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'An unexpected error occurred. Please try again.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    function maLoad() {
        document.getElementById('ma-roster').innerHTML = '<div class="ma-empty">Loading employees&hellip;</div>';
        maState = {};
        maPage = 1;

        $.ajax({
            url: '{{ route("learning.schedule.attendance.list") }}',
            type: 'GET',
            data: { searchTerm: $('#searchInput').val(), schedule_id: MA_SCHEDULE_ID },
            success: function (response) {
                var data = (response && response.data) ? response.data : [];
                maRows = data.map(maMapRow);
                maRender();
            },
            error: function () {
                document.getElementById('ma-roster').innerHTML = '<div class="ma-empty">Failed to load employees.</div>';
            }
        });
    }

    $(document).ready(function () {
        document.getElementById('maAllPresent').disabled = !MA_SCHEDULE_ID;
        document.getElementById('maSave').disabled = !MA_SCHEDULE_ID;
        maLoad();
        $('#searchInput').on('keyup', function () { maLoad(); });
    });
</script>
@endsection
