@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #learning-calendar-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #learning-calendar-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="learning-calendar-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Learning & Development</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        @php
                            // Add Learning Schedule — only HR / GM / L&D Manager.
                            $_curUser = Auth::guard('resort-admin')->user();
                            $_curEmp = $_curUser->GetEmployee ?? null;
                            $_curRank = (int) (optional($_curEmp)->rank ?? 0);
                            $_curPos = optional(optional($_curEmp)->position)->position_title;
                            $_ldTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
                            $_isAdmin = (($_curUser->type ?? null) === 'super') || ($_curUser->is_master_admin ?? 0);
                            // Rank 3 = HR, Rank 8 = GM (see config/settings.php Position_Rank).
                            $_canAddSchedule = $_isAdmin
                                || in_array($_curRank, [3, 8], true)
                                || in_array($_curPos, $_ldTitles, true);
                        @endphp
                        @if($_canAddSchedule)
                            <a href="{{route('learning.schedule')}}" class="btn lnd-btn-accent">Add Learning Schedule</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card calendar-card calendarLD-card">
            <div class="row g-4">
                <div class="col-xxl-9 col-lg-8 ">
                    <div id="calendar" class="calendar-event"></div>
                </div>
                <div class="col-xxl-3 col-lg-4 ">
                    {{-- The session-list panel scrolls internally. JS below
                         keeps its max-height in sync with the calendar's
                         actual rendered height so it never extends past
                         the calendar or leaves a short stub on a tall view. --}}
                    <div class="leaveUser-main" id="calsidebar"
                         style="max-height: 640px; overflow-y: auto; padding-right: 6px;">
                    </div>
                </div>

            </div>
        </div>

        {{-- Compact month-view day popover — one shared element, filled/positioned on hover/click. --}}
        <div class="ld-day-pop" id="ldDayPop">
            <div class="ld-pop-k"><span class="ld-pop-dot"></span>Programs</div>
            <div class="ld-pop-title" id="ldPopTitle"></div>
            <div class="ld-pop-frame">
                <div class="ld-pop-cap" id="ldPopCap"></div>
                <div class="ld-pop-rows" id="ldPopRows"></div>
            </div>
        </div>
    </div>
</div>
@include('resorts.Learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    /* Compact month-view: one capsule per day, replacing the old per-program
       event bars (which stacked unreadably on busy weeks). Right-aligned to
       sit under the day number, which this calendar skin (.calendar-event)
       also right-aligns (see default.css). Week/Day views are untouched —
       see eventRender's view-name check. */
    .ld-daycell-extra { margin-top: 30px; position: relative; z-index: 2; text-align: right; }
    /* Month view only, gated on `ld-cal-month` (toggled in viewRender since
       month/basicWeek both carry `.fc-basic-view` — `.ld-cal-month` is the
       unambiguous hook, matching the view.name check eventRender uses).
       The capsule lives in the `.fc-bg` background cell, but FullCalendar's
       `.fc-content-skeleton` overlay (day-number + event layer) sits on top
       of it and swallows every mouse event over the cell — hover/click on
       the capsule never fired even though it was visibly showing through.
       Re-enabling pointer-events on the whole `.fc-day-top` cell (instead of
       just the `.fc-day-number` link inside it) was tried and still blocked
       the capsule: `.fc-day-top` is a table cell whose box extends well
       below the visible date number, overlapping the capsule underneath it. */
    #calendar.ld-cal-month .fc-content-skeleton { pointer-events: none; }
    #calendar.ld-cal-month .fc-content-skeleton .fc-day-number { pointer-events: auto; }
    .ld-daycount { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 20px; background: var(--teal-soft, #f1f7f7); color: var(--teal, #014653); font-size: 10.5px; font-weight: 600; cursor: pointer; transition: background .15s; }
    .ld-daycount::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--teal, #014653); }
    .ld-daycount:hover { background: var(--teal-3, #e6f0f1); }

    /* Frosted day popover — same material as the L&D "View details" modal. */
    .ld-day-pop { position: fixed; z-index: 1070; width: 340px; border-radius: 22px; padding: 18px;
        background: rgba(255,255,255,.82); backdrop-filter: blur(28px) saturate(160%); -webkit-backdrop-filter: blur(28px) saturate(160%);
        border: 1px solid rgba(255,255,255,.7); box-shadow: 0 24px 70px rgba(var(--teal-rgb),.20);
        opacity: 0; transform: translateY(8px) scale(.985); pointer-events: none;
        transition: opacity .2s, transform .24s cubic-bezier(.16,1,.3,1); font-family: 'Poppins', sans-serif; }
    .ld-day-pop.open { opacity: 1; transform: none; pointer-events: auto; }
    .ld-pop-k { display: flex; align-items: center; gap: 7px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: var(--muted, #6B7378); }
    .ld-pop-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--teal, #014653); }
    .ld-pop-title { font-size: 15px; font-weight: 600; color: var(--ink, #14232A); margin: 6px 0 12px; }
    .ld-pop-frame { border: 1px solid var(--line, #E2EBEC); border-radius: 14px; overflow: hidden; background: rgba(255,255,255,.55); }
    .ld-pop-cap { font-size: 9.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: var(--muted, #6B7378); padding: 9px 13px; border-bottom: 1px solid var(--line, #E2EBEC); }
    .ld-pop-rows { padding: 2px 13px; max-height: 230px; overflow-y: auto; }
    .ld-pop-row { display: flex; align-items: center; gap: 9px; padding: 8px 0; border-bottom: 1px solid var(--line-2, #EEF4F4); }
    .ld-pop-row:last-child { border-bottom: none; }
    .ld-pop-sw { width: 9px; height: 9px; border-radius: 3px; flex: none; }
    .ld-pop-rmeta { flex: 1; min-width: 0; }
    .ld-pop-nm { font-size: 12px; font-weight: 500; color: var(--ink, #14232A); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ld-pop-rg { font-size: 10px; color: var(--faint, #99A1A5); margin-top: 2px; }
    .ld-pop-tm { font-size: 10.5px; color: var(--faint, #99A1A5); font-variant-numeric: tabular-nums; flex: none; }
    .ld-pop-rows::-webkit-scrollbar { width: 5px; }
    .ld-pop-rows::-webkit-scrollbar-thumb { background: var(--line-3, #C7CDCF); border-radius: 3px; }

    /* Sidebar session cards: right-align the attendee avatar stack (shared
       .user-ovImg is left-aligned by default everywhere else). Scoped to
       #calsidebar only, not touching the shared class. */
    #calsidebar .user-ovImg { justify-content: flex-end; }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
    // new DataTable('#example');
    $(document).ready(function () {
        function loadLearningSessions(startDate, endDate) {
            $.ajax({
                url: "{{route('get.learning.sessions')}}",
                type: "GET",
                data: { start_date: startDate, end_date: endDate },
                dataType: "json",
                success: function (response) {
                    console.log(response);
                    let sidebarContent = "";

                    response.data.forEach(session => {
                        let sessionDate = new Date(session.session_date);
                        let day = sessionDate.getDate();
                        let month = sessionDate.toLocaleString('en-US', { month: 'short' }).toUpperCase();
                        let weekday = sessionDate.toLocaleString('en-US', { weekday: 'short' }).toUpperCase();
                        let bgColorClass = session.color || "success"; // Set color dynamically

                        // Generate Attendee Images and collect names
                        let attendeeHtml = "";
                        let participantNamesHtml = "";
                        if (session.participants && session.participants.length > 0) {
                            session.participants.forEach((attendee, index) => {
                                if (index < 5) { // Show only first 5 images
                                    attendeeHtml += `
                                        <div class="img-circle">
                                            <img src="${attendee.image}" alt="${attendee.name}">
                                        </div>
                                    `;
                                }
                            });

                            // Add remaining count if more than 5 attendees
                            if (session.participants.length > 5) {
                                let remainingCount = session.participants.length - 5;
                                attendeeHtml += `<div class="num">+${remainingCount}</div>`;
                            }

                            // Build comma-separated participant names list
                            let names = session.participants.map(p => p.name).filter(Boolean);
                            if (names.length > 0) {
                                participantNamesHtml = `<p style="font-size: inherit; margin-bottom: 4px;"><b>Attendees:</b> ${names.join(', ')}</p>`;
                            }
                        }

                        sidebarContent += `
                            <div class="d-flex">
                                <div class="date-block bg">${month} <h5>${day}</h5> ${weekday}</div>
                                <div>
                                    <div class="leaveUser-bgBlock ${bgColorClass}">
                                        <h6>${session.title}</h6>
                                    </div>
                                    <div class="leaveUser-block">
                                        <p>${session.description || "No description available"}</p>
                                        ${participantNamesHtml}
                                        <div class="time"><i class="fa-regular fa-clock"></i> ${session.start_time} to ${session.end_time}</div>
                                        <div class="user-ovImg">${attendeeHtml}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    console.log(sidebarContent);
                    $("#calsidebar").html(sidebarContent); // Update sidebar
                },
                error: function () {
                    alert("Failed to load learning sessions.");
                }
            });
        }


        // ── Compact month-view: capsule + colour dots instead of per-program
        //    bars. Injected into the day cells AFTER the events ajax resolves
        //    (not via dayRender, whose timing against the async fetch isn't
        //    guaranteed) — same proven approach as paintLearningDots() on the
        //    dashboard's mini calendar widget. Week/Day views are untouched:
        //    eventRender below only suppresses bars when view.name === 'month'.
        var LD_PALETTE = ['#014653', '#A8823F', '#4A7C64', '#8A5CF6', '#7C9DA3', '#2EACB3'];
        var ldColorMap = {};
        var ldColorSeq = 0;
        // Assigned up front (not further down, after fullCalendar() init) —
        // FullCalendar's viewRender fires synchronously during init and
        // calls closeLdPop(), which needs $ldPop already resolved.
        var $ldPop = $('#ldDayPop');
        var ldPinned = false, ldOverPop = false;
        function ldColorFor(title) {
            if (!ldColorMap[title]) {
                ldColorMap[title] = LD_PALETTE[ldColorSeq % LD_PALETTE.length];
                ldColorSeq++;
            }
            return ldColorMap[title];
        }
        var ldMonthByDate = {}; // 'YYYY-MM-DD' -> [{title,start_date,end_date,start_time,color}], for capsule + popover

        function clearCompactMonth() {
            $('#calendar .ld-daycell-extra').remove();
        }

        function renderCompactMonth(sessions) {
            clearCompactMonth();
            ldMonthByDate = {};
            sessions.forEach(function (s) {
                var startStr = s.start_date || s.session_date;
                var endStr = s.end_date || startStr;
                var cursor = moment(startStr);
                var last = moment(endStr);
                if (!cursor.isValid()) return;
                if (!last.isValid() || last.isBefore(cursor, 'day')) last = cursor.clone();
                var item = { title: s.title, start_date: startStr, end_date: endStr, start_time: s.start_time, color: ldColorFor(s.title) };
                while (!cursor.isAfter(last, 'day')) {
                    var key = cursor.format('YYYY-MM-DD');
                    (ldMonthByDate[key] = ldMonthByDate[key] || []).push(item);
                    cursor.add(1, 'day');
                }
            });
            Object.keys(ldMonthByDate).forEach(function (dateStr) {
                var list = ldMonthByDate[dateStr];
                var $cell = $('#calendar .fc-day[data-date="' + dateStr + '"]');
                if (!$cell.length) return;
                $cell.append(
                    '<div class="ld-daycell-extra">' +
                        '<div class="ld-daycount" data-date="' + dateStr + '">' + list.length + ' program' + (list.length > 1 ? 's' : '') + '</div>' +
                    '</div>'
                );
            });
        }

        // Initialize FullCalendar
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next',
                center: 'title',
                right: 'month,basicWeek,basicDay'
            },
            editable: true,
            navLinks: true,
            eventLimit: true,
            eventRender: function (event, element) {
                var view = $('#calendar').fullCalendar('getView');
                if (view && view.name === 'month') return false;
            },
            events: function (start, end, timezone, callback) {
                // Calendar shows ONLY real scheduled training sessions from
                // get.learning.sessions. The compulsory/probationary "due
                // window" overlay used to draw multi-month bars (joining
                // date → due date) for each mandatory program; that was
                // visually noisy because nothing is actually scheduled yet
                // — those windows are now surfaced on the dashboards and
                // /compulsory-pending instead, not on the calendar.
                var toEvent = function (session) {
                    var startStr = session.start_date || session.session_date;
                    var endStr   = session.end_date   || session.session_date || startStr;
                    // FullCalendar v3 treats `end` as EXCLUSIVE for all-day events.
                    var endExclusive = moment(endStr).add(1, 'day').format('YYYY-MM-DD');
                    return {
                        title: session.title,
                        start: startStr,
                        end: endExclusive,
                        allDay: true,
                        backgroundColor: session.color,
                        textColor: "#fff"
                    };
                };

                $.ajax({
                    url: "{{route('get.learning.sessions')}}",
                    type: "GET",
                    data: {
                        start_date: start.format('YYYY-MM-DD'),
                        end_date: end.format('YYYY-MM-DD')
                    },
                    dataType: "json"
                }).done(function (sessRes) {
                    var sessions = (sessRes && sessRes.data) || [];
                    callback(sessions.map(toEvent));
                    var curView = $('#calendar').fullCalendar('getView');
                    if (curView && curView.name === 'month') {
                        renderCompactMonth(sessions);
                    } else {
                        clearCompactMonth();
                    }
                }).fail(function () {
                    callback([]);
                });
            },
            viewRender: function (view) {
                // FullCalendar v3 has no `.fc-month-view` class — month and
                // basicWeek both render as `.fc-basic-view`. This marker is
                // the only reliable month-only hook, and is what scopes the
                // .fc-content-skeleton pointer-events fix below to month
                // view only, so Week view's real event bars stay clickable.
                $('#calendar').toggleClass('ld-cal-month', view.name === 'month');
                closeLdPop();
                // Sidebar mirrors the calendar's TRUE current month — not
                // the visible grid range. FullCalendar's view.start /
                // view.end include the trailing days of the previous month
                // and leading days of the next month that fill the grid
                // (e.g. May 2026 grid spans Apr 26 → Jun 6), which is why
                // April events showed up on the May view. intervalStart /
                // intervalEnd give the actual month boundaries (May 1 →
                // Jun 1, exclusive end).
                var startDate = view.intervalStart.format('YYYY-MM-DD');
                var endDate   = view.intervalEnd.clone().subtract(1, 'day').format('YYYY-MM-DD');
                loadLearningSessions(startDate, endDate);
                syncSidebarHeight();
            }
        });

        // ── Sidebar height syncs with the rendered calendar height ──
        function syncSidebarHeight() {
            var h = $('#calendar').outerHeight();
            if (h && h > 200) {
                $('#calsidebar').css('max-height', h + 'px');
            }
        }
        // Calendar takes a tick to render after FullCalendar init.
        setTimeout(syncSidebarHeight, 250);
        $(window).on('resize', syncSidebarHeight);

        // ── Frosted day popover (hover to preview, click to pin) ──
        // Delegated on document, not bound directly to .ld-daycount, since
        // FullCalendar destroys/recreates the day cells on every month nav.
        // ($ldPop/ldPinned/ldOverPop declared earlier, before fullCalendar() init.)
        function ldFillPop(dateStr) {
            var list = ldMonthByDate[dateStr] || [];
            $('#ldPopTitle').text(moment(dateStr).format('MMMM D'));
            $('#ldPopCap').text(list.length + ' program' + (list.length > 1 ? 's' : '') + ' running');
            $('#ldPopRows').html(list.map(function (p) {
                return '<div class="ld-pop-row">' +
                    '<span class="ld-pop-sw" style="background:' + p.color + '"></span>' +
                    '<div class="ld-pop-rmeta">' +
                        '<div class="ld-pop-nm">' + p.title + '</div>' +
                        '<div class="ld-pop-rg">' + moment(p.start_date).format('MMM D') + ' &ndash; ' + moment(p.end_date).format('MMM D') + '</div>' +
                    '</div>' +
                    '<span class="ld-pop-tm">' + (p.start_time || '') + '</span>' +
                '</div>';
            }).join(''));
        }

        function ldPlacePop($anchor) {
            var r = $anchor[0].getBoundingClientRect();
            $ldPop.css('visibility', 'hidden').addClass('open');
            var pw = $ldPop.outerWidth(), ph = $ldPop.outerHeight();
            var left = Math.max(12, Math.min(r.left, $(window).width() - pw - 12));
            var top = r.bottom + 8;
            if (top + ph > $(window).height() - 12) top = r.top - ph - 8;
            $ldPop.css({ left: left + 'px', top: top + 'px', visibility: '' });
        }

        function ldOpenPop(dateStr, $anchor) {
            ldFillPop(dateStr);
            ldPlacePop($anchor);
            $ldPop.addClass('open');
        }

        function closeLdPop() {
            ldPinned = false;
            ldOverPop = false;
            $ldPop.removeClass('open');
        }

        $(document).on('mouseenter', '.ld-daycount', function () {
            if (!ldPinned) ldOpenPop($(this).data('date'), $(this));
        });
        $(document).on('mouseleave', '.ld-daycount', function () {
            setTimeout(function () { if (!ldPinned && !ldOverPop) $ldPop.removeClass('open'); }, 120);
        });
        $(document).on('click', '.ld-daycount', function (e) {
            e.stopPropagation();
            ldPinned = true;
            ldOpenPop($(this).data('date'), $(this));
        });
        $ldPop.on('mouseenter', function () { ldOverPop = true; }).on('mouseleave', function () {
            ldOverPop = false;
            setTimeout(function () { if (!ldPinned && !ldOverPop) $ldPop.removeClass('open'); }, 60);
        });
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#ldDayPop').length && !$(e.target).closest('.ld-daycount').length) closeLdPop();
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeLdPop();
        });
    });

</script>
@endsection