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
                        <a href="{{route('learning.schedule')}}" class="btn btn-theme @if(Common::checkRouteWisePermission('learning.calendar.index',config('settings.resort_permissions.view')) == false) d-none @endif">Add Learning Schedule</a>
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

                        // Generate Attendee Images
                        let attendeeHtml = "";
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
                $.ajax({
                    url: "{{route('get.learning.sessions')}}",
                    type: "GET",
                    data: {
                        start_date: start.format('YYYY-MM-DD'),
                        end_date: end.format('YYYY-MM-DD')
                    },
                    dataType: "json",
                    success: function (response) {
                        let events = response.data.map(session => ({
                            title: session.title,
                            start: session.session_date,
                            backgroundColor: session.color,
                            textColor: "#fff"
                        }));

                        callback(events);
                    }
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