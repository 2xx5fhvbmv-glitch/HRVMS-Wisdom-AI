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
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card calendar-card calendarIncident-card">
                <div class="row g-4">
                    <div class="col-xxl-9 col-lg-8 ">
                        <div id="calendar" class="calendar-event"></div>
                    </div>
                    <div class="col-xxl-3 col-lg-4">
                        <div class="leaveUser-main" id="calsidebar">
                            <div class="upcoming">Scheduled Meetings</div>
                            <!-- JavaScript will inject meetings here -->
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
<script>
$(document).ready(function () {

    // Initialize FullCalendar once the meetings are fetched
    function renderCalendar(meetings) {
        const calendarEvents = meetings.map(m => ({
            title: m.title,
            start: m.date,
            backgroundColor: '#2EACB336',
            textColor: '#2EACB3'
        }));

        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next',
                center: 'title',
                right: 'month,basicWeek,basicDay'
            },
            defaultDate: new Date().toISOString().split('T')[0],
            navLinks: true,
            editable: false,
            eventLimit: true,
            events: calendarEvents,
            viewRender: function (view) {
                const start = view.start.format('YYYY-MM-DD');
                const end = view.end.format('YYYY-MM-DD');
                fetchSidebarMeetings(start, end);
            }
        });
    }

    // Fetch meetings from backend (optionally by date range)
    function fetchMeetings(callback) {
        $.ajax({
            url: "{{ route('incident.calendar.get-meetings') }}",
            method: "GET",
            success: callback
        });
    }

    // Sidebar renderer
    function fetchSidebarMeetings(startDate, endDate) {
        $.ajax({
            url: "{{ route('incident.calendar.get-meetings') }}",
            method: "GET",
            data: {
                start: startDate,
                end: endDate
            },
            success: function (meetings) {
                let sidebarHTML = '<div class="upcoming">Scheduled Meetings</div>';

                meetings.forEach(meeting => {
                    const dateObj = new Date(meeting.date);
                    const month = dateObj.toLocaleString('default', { month: 'short' }).toUpperCase();
                    const day = dateObj.getDate();
                    const weekday = dateObj.toLocaleString('default', { weekday: 'short' }).toUpperCase();

                    let avatars = '';
                    const total = meeting.participants.length;
                    const max = 6;

                    meeting.participants.slice(0, max).forEach(p => {
                        avatars += `<div class="img-circle"><img src="${p.avatar}" alt="image"></div>`;
                    });
                    if (total > max) {
                        avatars += `<div class="num">+${total - max}</div>`;
                    }

                    sidebarHTML += `
                        <div class="d-flex mb-3">
                            <div class="date-block bg">${month} <h5>${day}</h5> ${weekday}</div>
                            <div>
                                <div class="leaveUser-bgBlock success">
                                    <h6>${meeting.title}</h6>
                                </div>
                                <div class="leaveUser-block">
                                    <p>${meeting.location}</p>
                                    <div class="time"><i class="fa-regular fa-clock"></i> ${meeting.time}</div>
                                    <div class="user-ovImg">${avatars}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#calsidebar').html(sidebarHTML);
                equalizeHeights();
            }
        });
    }

    function equalizeHeights() {
        setTimeout(() => {
            const block1 = document.getElementById('calendar');
            const block2 = document.getElementById('calsidebar');
            if (block1 && block2) {
                block2.style.height = block1.offsetHeight + 'px';
            }
        }, 100);
    }

    // Initial load
    fetchMeetings(function (meetings) {
        renderCalendar(meetings);
        fetchSidebarMeetings(
            moment().startOf('month').format('YYYY-MM-DD'),
            moment().endOf('month').format('YYYY-MM-DD')
        );
    });

    // Resize on window
    window.onresize = equalizeHeights;
});
</script>
@endsection
