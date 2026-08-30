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
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
<style>
/* Sidebar empty state only — calendar chrome/config untouched. Reuses the
   .upcoming/.leaveUser-* styling already in default.css as-is (no font-size
   changes there); this block only adds the new empty-state illustration,
   scoped to #calsidebar so nothing leaks elsewhere. Same lightweight
   transform/opacity-only float + prefers-reduced-motion pattern already
   used by the Notifications empty state (default.css .ntf-empty*). */
/* #calsidebar's own height is set by equalizeHeights() (unchanged JS) to
   match the calendar's height — flex-column here just stacks children the
   same way block layout already did (no visual change for the populated
   meeting-list case), and lets .cal-empty use flex:1 to fill and center
   within whatever's left below the header. */
#calsidebar{display:flex;flex-direction:column}
#calsidebar .cal-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:10px}
#calsidebar .cal-empty-scene{width:84px;height:84px;margin:0 auto;border-radius:50%;background:radial-gradient(circle,var(--teal-3) 0%,rgba(230,240,241,0) 72%);display:grid;place-items:center;animation:calEmptyFloat 6s ease-in-out infinite;will-change:transform}
@keyframes calEmptyFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@media (prefers-reduced-motion:reduce){#calsidebar .cal-empty-scene{animation:none}}
#calsidebar .cal-empty-title{font-size:15px;font-weight:600;color:var(--ink);margin-top:18px}
#calsidebar .cal-empty-sub{font-size:12.5px;color:var(--muted);margin-top:6px;line-height:1.55}
#calsidebar .cal-empty-btn{margin-top:18px}
</style>
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
                const monthLabel = moment(startDate).format('MMMM YYYY');
                let sidebarHTML = `<div class="upcoming">${monthLabel} <span class="text-muted">&middot; ${meetings.length}</span></div>`;

                if (!meetings.length) {
                    sidebarHTML += `
                        <div class="cal-empty">
                            <div class="cal-empty-scene">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#014653" stroke-width="1.6">
                                    <rect x="3" y="5" width="18" height="16" rx="3"/>
                                    <path d="M3 9h18M8 3v4M16 3v4"/>
                                    <circle cx="8" cy="14" r="1.1" fill="#014653" stroke="none"/>
                                    <circle cx="12" cy="14" r="1.1" fill="#014653" stroke="none"/>
                                    <circle cx="16" cy="14" r="1.1" fill="#014653" stroke="none"/>
                                </svg>
                            </div>
                            <div class="cal-empty-title">Nothing on the calendar yet</div>
                            <div class="cal-empty-sub">Meetings you schedule for incident reviews will appear here.</div>
                            <a href="{{ route('incident.meeting') }}" class="btn eb-btn-primary btn-sm cal-empty-btn">Schedule a meeting</a>
                        </div>
                    `;
                    $('#calsidebar').html(sidebarHTML);
                    equalizeHeights();
                    return;
                }

                meetings.forEach(meeting => {
                    const dateObj = new Date(meeting.date);
                    const month = dateObj.toLocaleString('default', { month: 'short' }).toUpperCase();
                    const day = dateObj.getDate();
                    const weekday = dateObj.toLocaleString('default', { weekday: 'short' }).toUpperCase();

                    let avatars = '';
                    const total = meeting.participants.length;
                    const max = 6;

                    meeting.participants.slice(0, max).forEach(p => {
                        const safeName = $('<div>').text(p.name || 'Unknown').html();
                        avatars += `<div class="img-circle" title="${safeName}"><img src="${p.avatar}" alt="${safeName}"></div>`;
                    });
                    if (total > max) {
                        avatars += `<div class="num">+${total - max}</div>`;
                    }

                    // Comma-separated participant names (caps to first 5 to keep
                    // the row visually short; extra count shown via the avatars).
                    const namesPreview = meeting.participants
                        .slice(0, 5)
                        .map(p => $('<div>').text(p.name || 'Unknown').html())
                        .join(', ');
                    const moreCount = total > 5 ? ` <span class="text-muted">+${total - 5} more</span>` : '';

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
                                    ${namesPreview ? `<div class="participant-names small text-muted mt-1">${namesPreview}${moreCount}</div>` : ''}
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
