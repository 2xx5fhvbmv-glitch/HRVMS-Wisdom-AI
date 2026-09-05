@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Ongoing Learning Programs</p>
                                <strong>{{$ongoing_trainings_count ?? 0}}</strong>
                            </div>
                            <a href="{{ route('learning.schedule.index') }}?status=Ongoing">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Completed Learning Programs</p>
                                <strong>{{$completed_trainings_count ?? 0}}</strong>
                            </div>
                            <a href="{{ route('training.history') }}?status=Completed">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Pending Learning Programs</p>
                                <strong>{{$pending_trainings_count ?? 0}}</strong>
                            </div>
                            <a href="{{ route('learning.schedule.index') }}?status=Scheduled">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                {{-- Compulsory % is informational; no canonical filtered destination — drop the arrow. --}}
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Completed Compulsory Learning</p>
                                <strong>{{ is_null($compulsoryPercent ?? null) ? '—' : ($compulsoryPercent . '%') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Row 2: My Compulsory (full width when on probation) + Calendar.
                     If the user isn't on probation, Pending Actions takes the wide slot. --}}
                @if(!empty($myCompulsoryPrograms))
                    <div class="col-xl-9 col-12">
                        <div class="card h-100">
                            <div class="card-title">
                                <h3>My Compulsory Learning Programs</h3>
                                <small class="text-muted">You're on probation — these are the trainings you need to complete.</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableNew table-empLDHod w-100">
                                    <tr>
                                        <th>Program</th>
                                        <th>Complete Within</th>
                                        <th>Due By</th>
                                        <th>Status</th>
                                    </tr>
                                    @foreach($myCompulsoryPrograms as $p)
                                        <tr>
                                            <td>{{ $p->program_name }}</td>
                                            <td>{{ $p->completion_days ? $p->completion_days . ' days' : '—' }}</td>
                                            <td>{{ $p->due_on ? \Carbon\Carbon::parse($p->due_on)->format('d M Y') : '—' }}</td>
                                            <td>
                                                @if($p->is_completed)
                                                    <span class="badge badge-themeSuccess"><i class="fa-solid fa-check me-1"></i>Completed</span>
                                                @elseif($p->is_overdue)
                                                    <span class="badge badge-themeDanger">Overdue</span>
                                                @else
                                                    <span class="badge badge-themeWarning">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-xl-9 col-12 @if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-100" id="card-pendingActionsHOD">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-md-3 g-1">
                                    <div class="col"><h3 class="text-nowrap">Pending Actions</h3></div>
                                    <div class="col-auto"><a href="#" class="a-link">View All</a></div>
                                </div>
                            </div>
                            <div class="leaveUser-main">
                                @if(count($pending_learning_request)>0)
                                    @foreach($pending_learning_request as $request)
                                        <div class="leaveUser-block">
                                            <div>
                                                <h6>{{$request->learning->name}}</h6>
                                                <p>{{ \Illuminate\Support\Str::words($request->learning->description, 30, '…') }}</p>
                                                <div><a href="{{ route('learning.request.details', ['id' => $request->id]) }}" class="a-linkTheme">View Details</a></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted small mb-0">No pending requests.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-xl-3 col-md-6" id="right-ldDash">
                    <div class="card calendar-card calendarLD-card h-100">
                        <div class="ldDash-block">
                            <div class="mb-4 overflow-hidden">
                                <div id="calendar"></div>
                            </div>
                            <div class="card-title">
                                <h3>Upcoming Learning Sessions</h3>
                            </div>
                            <div class="leaveUser-main" id="leaveUser-main">
                                <!-- Dynamic content will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 3: Overdue + Top Performers (paired tables) --}}
                <div class="col-xl-6 col-md-12">
                    <div class="card h-100">
                        <div class="card-title"><h3>Employees With Overdue Learning Programs</h3></div>
                        <div class="table-responsive">
                            <table class="table-lableNew table-empLDHod w-100">
                                <tr><th>Employee Name</th><th>Learning</th><th>Date</th></tr>
                                @forelse(($overdueEmployees ?? []) as $att)
                                    @php
                                        $emp = $att->employee ?? null;
                                        $ra  = $emp ? $emp->resortAdmin : null;
                                        $prog = optional(optional($att->schedule)->learningProgram);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="tableUser-block">
                                                <div class="img-circle"><img src="{{ Common::getResortUserPicture(optional($ra)->id) }}" alt="user"></div>
                                                <span class="userApplicants-btn">{{ trim((optional($ra)->first_name ?? '').' '.(optional($ra)->last_name ?? '')) ?: '-' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $prog->learning_program_title ?? $prog->name ?? '-' }}</td>
                                        <td>{{ $att->attendance_date ? \Carbon\Carbon::parse($att->attendance_date)->format('d M Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No overdue learners</td></tr>
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-12">
                    <div class="card h-100">
                        <div class="card-title"><h3>Employees Who Excelled In Learning Evaluations</h3></div>
                        <div class="table-responsive">
                            <table class="table-lableNew table-empLDHod w-100">
                                <tr><th>Employee Name</th><th>Sessions Attended</th><th>Last Session</th></tr>
                                @forelse(($topPerformers ?? []) as $row)
                                    @php
                                        $emp = $row->employee ?? null;
                                        $ra  = $emp ? $emp->resortAdmin : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="tableUser-block">
                                                <div class="img-circle"><img src="{{ Common::getResortUserPicture(optional($ra)->id) }}" alt="user"></div>
                                                <span class="userApplicants-btn">{{ trim((optional($ra)->first_name ?? '').' '.(optional($ra)->last_name ?? '')) ?: '-' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $row->present_count }}</td>
                                        <td>{{ $row->last_date ? \Carbon\Carbon::parse($row->last_date)->format('d M Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No completed sessions yet</td></tr>
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Row 4: Learning Attendance · Pending Actions (when probationary card already shows above) · Learning History --}}
                <div class="col-xl-4 col-md-6 @if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card h-100" id="card-trainingAttendanceHOD">
                        <div class="card-title"><h3>Learning Attendance</h3></div>
                        <div class="trainingAttendance-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center">
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-theme"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeSkyblueLightNew"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeWarning"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeSkyblue"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeGray"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeSkyblueLight"></span>Learning 1</div></div>
                        </div>
                    </div>
                </div>

                @if(!empty($myCompulsoryPrograms))
                    <div class="col-xl-4 col-md-6 @if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-100" id="card-pendingActionsHOD">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-md-3 g-1">
                                    <div class="col"><h3 class="text-nowrap">Pending Actions</h3></div>
                                    <div class="col-auto"><a href="#" class="a-link">View All</a></div>
                                </div>
                            </div>
                            <div class="leaveUser-main">
                                @if(count($pending_learning_request)>0)
                                    @foreach($pending_learning_request as $request)
                                        <div class="leaveUser-block">
                                            <div>
                                                <h6>{{$request->learning->name}}</h6>
                                                <p>{{ \Illuminate\Support\Str::words($request->learning->description, 25, '…') }}</p>
                                                <div><a href="{{ route('learning.request.details', ['id' => $request->id]) }}" class="a-linkTheme">View Details</a></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted small mb-0">No pending requests.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-xl-4 col-md-12 @if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card card-trainingHistory h-100" id="card-trainingHistory">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col"><h3 class="text-nowrap">Learning History</h3></div>
                                <div class="col-auto"><a href="{{ route('training.history') }}" class="a-link">View All</a></div>
                            </div>
                        </div>
                        <div class="leaveUser-main">
                            @if($trainings->isEmpty())
                                <p class="text-muted small mb-0">No training history available.</p>
                            @else
                                @foreach ($trainings as $training)
                                    @php
                                        $totalTrainingDays = \Carbon\Carbon::parse($training->start_date)->diffInDays(\Carbon\Carbon::parse($training->end_date)) + 1;
                                        $totalParticipants = $training->participants->count();
                                        $totalExpectedAttendance = $totalTrainingDays * $totalParticipants;
                                        $actualAttendance = $training->trainingAttendances->where('status', 'Present')->count();
                                        $attendancePercentage = ($totalExpectedAttendance > 0)
                                            ? round(($actualAttendance / $totalExpectedAttendance) * 100, 2)
                                            : 0;
                                    @endphp
                                    <div class="leaveUser-block">
                                        <div>
                                            <div class="date"><i class="fa-regular fa-calendar"></i>
                                                {{ date('d M Y', strtotime($training->start_date)) . ' - ' . date('d M Y', strtotime($training->end_date)) }}
                                            </div>
                                            <h6>{{ $training->learningProgram->name ?? 'Learning Program' }}</h6>
                                            <p>{{ \Illuminate\Support\Str::words($training->description, 25, '…') }}</p>
                                            <span>Attendance: {{ $attendancePercentage }}%</span>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .fc-day.custom-dot::after {
        content: '';
        position: absolute;
        left: 75%!important;
        bottom: 10%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: var(--aqua);
        border-radius: 50%;
    }
</style>
@endsection

@section('import-scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('.data-Table').dataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": false,
                "bAutoWidth": false,
                scrollX: true,
                "iDisplayLength": 10,
            });

            fetchUpcomingSessions();
            fetchTrainingAttendance();
        });

        $(function () {
            var todayDate = moment().startOf('day');
            var YM = todayDate.format('YYYY-MM');
            var TODAY = todayDate.format('YYYY-MM-DD');

            var cal = $('#calendar').fullCalendar({
                header: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                editable: false,
                eventLimit: 0, // No extra "more" link
                // Disable nav links so date click only refreshes the side panel
                // (no v3 day-view header artifact in the empty space).
                navLinks: false,
                // Avoid the default 213px inner scroller on the day grid.
                contentHeight: 'auto',

                events: function(start, end, timezone, callback) {
                    $.ajax({
                        url: "{{ route('get.learning.sessions') }}",
                        type: "GET",
                        data: {
                            start_date: start.format('YYYY-MM-DD'),
                            end_date: end.format('YYYY-MM-DD')
                        },
                        success: function(response) {
                            window._learningSessions = response.data || [];
                            callback([]); // No events displayed, just dots
                            // Defer one tick so the day cells are guaranteed in DOM.
                            setTimeout(paintLearningDots, 0);
                        },
                        error: function(xhr) {
                            console.error("Error fetching training sessions", xhr);
                        }
                    });
                },
                viewRender: function (view) {
                    let startDate = view.start.format('YYYY-MM-DD');
                    let endDate = view.end.format('YYYY-MM-DD');
                    fetchUpcomingSessions(startDate, endDate); // Load sidebar when month changes
                    // Re-apply dots once the new month's grid is rendered.
                    setTimeout(paintLearningDots, 0);
                },
                dayClick: function(date, jsEvent, view) {
                    $.ajax({
                        url: "{{ route('get.learning.sessions') }}",
                        type: "GET",
                        data: {
                            start_date: date.format('YYYY-MM-DD'),
                            end_date: date.format('YYYY-MM-DD')
                        },
                        success: function(response) {
                            let trainingHtml = '';
                            console.log(response);
                            if (response.data.length > 0) {
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

                                    trainingHtml += `
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
                            } else {
                                trainingHtml = `<p class="text-center">No training sessions on this date.</p>`;
                            }

                            $("#leaveUser-main").html(trainingHtml);
                        },
                        error: function(xhr) {
                            console.error("Error fetching training sessions", xhr);
                        }
                    });
                }
            });
        });


        // Paint a dot on every day each cached training session covers (start..end).
        // Called after events: resolves AND on every viewRender so the dots survive
        // month navigation. moment 2.9.0 lacks isSameOrBefore — use !isAfter instead.
        function paintLearningDots() {
            $('.fc-day').removeClass('custom-dot');
            var sessions = window._learningSessions || [];
            sessions.forEach(function (session) {
                var startStr = session.start_date || session.session_date;
                var endStr   = session.end_date   || session.session_date;
                if (!startStr) return;
                var startDate = moment(startStr);
                var endDate   = endStr ? moment(endStr) : startDate.clone();
                if (!startDate.isValid()) return;
                if (!endDate.isValid() || endDate.isBefore(startDate, 'day')) endDate = startDate.clone();
                var cursor = startDate.clone();
                while (!cursor.isAfter(endDate, 'day')) {
                    var dayCell = $('.fc-day[data-date="' + cursor.format('YYYY-MM-DD') + '"]');
                    if (dayCell.length) dayCell.addClass('custom-dot');
                    cursor.add(1, 'day');
                }
            });
        }

        function fetchUpcomingSessions() {
            $.ajax({
                url: '{{ route("get.learning.sessions") }}', // Adjust the route accordingly
                type: 'GET',
                data: {
                    start_date: new Date().toISOString().split('T')[0], // Today
                    end_date: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0] // Next 30 days
                },
                success: function(response) {
                    let sessionsHtml = '';
                    if (response.data.length > 0) {
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

                                    sessionsHtml += `
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
                    } else {
                        sessionsHtml = `<p class="text-center">No upcoming training sessions.</p>`;
                    }

                    $('#leaveUser-main').html(sessionsHtml);
                },
                error: function(error) {
                    console.error('Error fetching training sessions:', error);
                }
            });
        }


        function fetchTrainingAttendance() {
            $.ajax({
                url: "{{ route('learning.attendance.chart-data') }}", // Backend route
                type: "GET",
                success: function (response) {
                    console.log(response);
                    if (response.success) {
                        updateDoughnutChart(response.data);
                        
                        // ✅ Update Late Attendance Percentage
                        $("#lateAttendanceText").text(`Late Attendance: ${response.data.late_percentage}%`);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function () {
                    toastr.error("Failed to fetch attendance data.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        }

        function updateDoughnutChart(chartData) {
            var ctz = document.getElementById('myDoughnutChart').getContext('2d');

            // ✅ Check if the chart already exists and destroy it before creating a new one
            if (window.myDoughnutChart instanceof Chart) {
                window.myDoughnutChart.destroy();
            }

            const doughnutLabelsInsideN = {
                id: 'doughnutLabelsInsideN',
                afterDraw: function (chart) {
                    var ctx = chart.ctx;
                   
                    chart.data.datasets.forEach(function (dataset, i) {
                        var meta = chart.getDatasetMeta(i);
                        if (!meta.hidden) {
                            meta.data.forEach(function (element, index) {
                                var dataValue = dataset.data[index] + '%';
                                var total = dataset.data.reduce((acc, val) => acc + val, 0);
                                var percentage = ((dataValue / total) * 100).toFixed(0) + '%';
                                var position = element.tooltipPosition();
                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 14px Poppins';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(dataValue, position.x, position.y); // Show percentage inside
                            });
                        }
                    });
                }
            };

            // Create new chart
            window.myDoughnutChart = new Chart(ctz, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,  // Dynamically assigned labels
                    datasets: [{
                        data: chartData.values,  // Dynamically assigned values
                        backgroundColor: chartData.colors,  // Dynamically assigned colors
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10,
                            left: 0,
                            right: 0
                        }
                    }
                },
                plugins: [doughnutLabelsInsideN]
            });
            // backgroundColor (chartData.colors) is server-supplied — out of scope.
            if (window.WaiChart) window.WaiChart.registerForTheme(window.myDoughnutChart);

            // ✅ Update the legend after the chart is created
            updateLegend(chartData.labels, chartData.colors);
        }

        function updateLegend(labels, colors) {
            let legendContainer = $(".row.g-2.justify-content-center"); // Ensure this selector is correct
            legendContainer.empty(); // Clear existing legend items

            labels.forEach((label, index) => {
                let legendItem = `
                    <div class="col-auto">
                        <div class="doughnut-label" style="display: flex; align-items: center;">
                            <span style="background-color: ${colors[index]}; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span>
                            <span>${label}</span>
                        </div>
                    </div>`;
                legendContainer.append(legendItem);
            });
        }

        function updateLegend(labels, colors) {
            let legendContainer = $(".row.g-2.justify-content-center");
            legendContainer.empty();

            labels.forEach((label, index) => {
                let legendItem = `
                    <div class="col-auto">
                        <div class="doughnut-label">
                            <span style="background-color: ${colors[index]}; width: 12px; height: 12px; display: inline-block;"></span> ${label}
                        </div>
                    </div>`;
                legendContainer.append(legendItem);
            });
        }

        // Generic function to equalize heights of two or more elements based on a reference element
        function equalizeHeights(referenceId, targetIds) {
            // Get the reference element
            const reference = document.getElementById(referenceId);

            // Check if the reference element exists
            if (reference) {
                // Get the height of the reference element
                const referenceHeight = reference.offsetHeight;

                // Loop through target element IDs and set their height
                targetIds.forEach(targetId => {
                    const target = document.getElementById(targetId);
                    if (target) {
                        target.style.height = referenceHeight + 'px';
                    }
                });
            }
        }

        // h-100 + Bootstrap rows now handle equal heights — no-op stub kept so
        // any external reference to adjustHeights() doesn't blow up.
        function adjustHeights() {}


        // progress 
        const radius = 54; // Circle radius
        const circumference = 2 * Math.PI * radius; // The circumference of the circle
        // Select all progress containers
        const progressContainers = document.querySelectorAll('.progress-container');

        progressContainers.forEach(container => {
            const progressCircle = container.querySelector('.progress');
            // const progressText = container.querySelector('.progress-text');
            const progressValue = container.getAttribute('data-progress'); // Get the progress value from the container's data attribute
            const offset = circumference - (progressValue / 100 * circumference); // Calculate the offset

            // Set the initial stroke-dashoffset to the full circumference
            progressCircle.style.strokeDashoffset = circumference;

            // Use a small timeout to allow the browser to render the initial state before applying the offset (to trigger the animation)
            setTimeout(() => {
                // Apply the calculated offset to the progress bar with animation
                progressCircle.style.strokeDashoffset = offset;

                // Update the text inside the circle
                // progressText.textContent = `${progressValue}%`;
            }, 100); // A small delay to trigger the animation smoothly
        });

        document.addEventListener("DOMContentLoaded", function () {
            const progressBars = document.querySelectorAll('.progress.progress-custom .progress-bar'); // Ensure parent has progress-custom class

            progressBars.forEach((progressBar) => {
                const valueNow = parseInt(progressBar.getAttribute('aria-valuenow'), 10);
                const parentProgress = progressBar.closest('.progress'); // Get the parent .progress element

                // Add specific classes to the parent based on aria-valuenow
                if (valueNow === 100) {
                    parentProgress.classList.add('value-100');
                } else if (valueNow === 0) {
                    parentProgress.classList.add('value-0');
                }
            });

            // (Duplicate fullCalendar() init removed — it re-initialised #calendar
            //  without the events: / dayClick handlers above and reverted navLinks
            //  to defaults, breaking the side panel + dot painting.)
        });
    </script>

@endsection