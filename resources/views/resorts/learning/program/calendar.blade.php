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
                            <a href="{{route('learning.schedule')}}" class="btn btn-theme">Add Learning Schedule</a>
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
                    <div class="leaveUser-main" id="calsidebar">
                       
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
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
                // Calendar shows two layers:
                //   1. Real training_schedules (from get.learning.sessions)
                //   2. The auth user's personal compulsory / probationary windows
                //      (from learning.my.compulsory.events) — these are window
                //      ranges, not real schedules, so they only paint dots on the
                //      calendar. The sidebar's "Upcoming Learning Sessions" still
                //      uses sessions only, keeping that panel clean.
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

                var sessionsXhr = $.ajax({
                    url: "{{route('get.learning.sessions')}}",
                    type: "GET",
                    data: {
                        start_date: start.format('YYYY-MM-DD'),
                        end_date: end.format('YYYY-MM-DD')
                    },
                    dataType: "json"
                });
                var compulsoryXhr = $.ajax({
                    url: "{{route('learning.my.compulsory.events')}}",
                    type: "GET",
                    dataType: "json"
                });

                $.when(sessionsXhr).then(function (sessRes) {
                    var sessEvents = ((sessRes && sessRes.data) || []).map(toEvent);
                    compulsoryXhr.done(function (compRes) {
                        var compEvents = ((compRes && compRes.data) || []).map(toEvent);
                        callback(sessEvents.concat(compEvents));
                    }).fail(function () {
                        // If compulsory fetch fails, still render real sessions.
                        callback(sessEvents);
                    });
                }, function () {
                    callback([]);
                });
            },
            viewRender: function (view) {
                let startDate = view.start.format('YYYY-MM-DD');
                let endDate = view.end.format('YYYY-MM-DD');
                loadLearningSessions(startDate, endDate); // Load sidebar when month changes
            }
        });
    });

</script>
@endsection