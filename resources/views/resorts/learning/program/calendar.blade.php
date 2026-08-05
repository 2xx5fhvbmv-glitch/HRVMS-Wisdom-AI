@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
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
    </div>
</div>
@include('resorts.Learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
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
                    var sessEvents = ((sessRes && sessRes.data) || []).map(toEvent);
                    callback(sessEvents);
                }).fail(function () {
                    callback([]);
                });
            },
            viewRender: function (view) {
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
    });

</script>
@endsection