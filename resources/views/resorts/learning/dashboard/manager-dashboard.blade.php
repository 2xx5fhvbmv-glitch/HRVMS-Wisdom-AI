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
                            <a href="{{ route('training.history') }}">
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
                {{-- Compulsory % is currently a real count but only meaningful when mandatory schedules
                     exist; no canonical destination yet so the arrow is suppressed. --}}
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Completed Compulsory Learning</p>
                                <strong>{{$compulsory_completed_traing ?? 0}}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(!empty($teamCompulsoryPending))
                <div class="col-xl-9 col-12">
                    <div class="card compulsory-action-card h-100">
                        <div class="card-title mb-md-3">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap mb-1">Compulsory Trainings — Action Needed</h3>
                                    <p class="mb-0 small text-muted">Probationers with pending or overdue compulsory programs. Click <strong>Schedule</strong> to add a session.</p>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('learning.compulsory.pending') }}" class="a-link">View All ({{ $teamCompulsoryPendingCount }})</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-LearningProgram w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Position</th>
                                        <th>Program</th>
                                        <th>Due By</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($teamCompulsoryPending as $row)
                                        <tr>
                                            <td>{{ $row->employee_name ?: '—' }}</td>
                                            <td>{{ $row->department ?: '—' }}</td>
                                            <td>{{ $row->position ?: '—' }}</td>
                                            <td>{{ $row->program_name }}</td>
                                            <td>{{ $row->due_on ? $row->due_on->format('d M Y') : '—' }}</td>
                                            <td>
                                                @if($row->is_overdue)
                                                    <span class="badge badge-danger">Overdue</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('learning.schedule') }}?program_id={{ $row->program_id }}&employee_id={{ $row->employee_id }}"
                                                   class="btn btn-theme btn-sm">Schedule</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Calendar sits alongside the Compulsory Trainings table --}}
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

                {{-- Row 3: action panels (Pending Actions · Absentees · Feedback) --}}
                <div class="col-xl-4 col-md-6">
                    <div class="card h-100" id="card-pendingActions">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Pending Actions</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('learning.request.index')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main">
                            @if($pending_learning_request && count($pending_learning_request))
                                @foreach($pending_learning_request as $request)
                                    <div class="leaveUser-block">
                                        <div>
                                            <h6>{{$request->learning->name}}</h6>
                                            <p>{{ \Illuminate\Support\Str::words($request->learning->description, 30, '…') }}</p>
                                            <div>
                                                <a href="{{ route('learning.request.details', ['id' => $request->id]) }}" class="a-linkTheme">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted small mb-0">No pending requests.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">List of Absentees</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('learning.absentees.getall')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table-lableNew w-100">
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Learning</th>
                                </tr>
                                @if($absentees && count($absentees))
                                    @foreach($absentees as $absentee)
                                        <tr>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle">
                                                        <img src="{{Common::getResortUserPicture($absentee->employee->resortAdmin->id)}}" alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">{{$absentee->employee->resortAdmin->full_name}}</span>
                                                </div>
                                            </td>
                                            <td>{{$absentee->schedule->learningProgram->name}}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="2" class="text-center text-muted small">No recent absentees.</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-12">
                    <div class="card card-feedbackEvaluation h-100" id="card-feedbackEvaluation">
                        <div class="card-title">
                            <h3>Feedback and Evaluation</h3>
                        </div>
                        <div class="progress-block">
                            <div class="progress-container blue" data-progress="{{ $avgFeedbackScore }}" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Average Feedback Score {{ $avgFeedbackScore }}%">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                            </div>
                            <div class="text">
                                <h5>{{ $avgFeedbackScore }}%</h5>
                                <p>AVERAGE FEEDBACK SCORES</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <p>Top Trainer:</p>
                            <p class="fw-500">{{ $topTrainerName ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Row 4: paired bar charts --}}
                <div class="col-xl-6">
                    <div class="card card-participation h-100">
                        <div class="card-title mb-md-3">
                            <h3>Participation</h3>
                        </div>
                        <div class="chart-flex-wrap">
                            <canvas id="myStackedBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card card-participation h-100" id="card-onboarding">
                        <div class="card-title mb-md-3">
                            <h3>Onboarding Learning</h3>
                        </div>
                        <div class="chart-flex-wrap">
                            <canvas id="onboardingChart"></canvas>
                        </div>
                        <div class="row g-2 mt-2" id="onboardingChartLegend">
                            {{-- Legend rendered horizontally below the chart by fetchOnboardingChart() --}}
                        </div>
                    </div>
                </div>

                {{-- Row 5: doughnut/list cards (Breakdown · Learning Attendance · Learning History) --}}
                <div class="col-xl-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-title mb-md-3">
                            <h3>Breakdown of Learning Programs</h3>
                        </div>
                        @php
                            $breakdownRows = $categories->sortByDesc('programs_count')->values();
                            $breakdownLabels = $breakdownRows->pluck('category')->all();
                            $breakdownData   = $breakdownRows->pluck('programs_count')->all();
                            $breakdownColors = $breakdownRows->pluck('color')->all();
                        @endphp
                        <div class="chart-flex-wrap" style="height: {{ max(220, count($breakdownLabels) * 28) }}px;">
                            <canvas id="breakdownChart"
                                data-labels='@json($breakdownLabels)'
                                data-values='@json($breakdownData)'
                                data-colors='@json($breakdownColors)'></canvas>
                        </div>
                        @if(empty($breakdownData))
                            <p class="text-muted small mb-0 mt-2">No categories yet.</p>
                        @endif
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-title">
                            <h3>Learning Attendance</h3>
                        </div>
                        <p id="lateAttendanceText" class="small mb-2">Late Attendance: --%</p>
                        <div class="trainingAttendance-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center" id="doughnut-label">
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-theme"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeSkyblueLightNew"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeWarning"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeSkyblue"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeGray"></span>Learning 1</div></div>
                            <div class="col-auto"><div class="doughnut-label"><span class="bg-themeSkyblueLight"></span>Learning 1</div></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-12">
                    <div class="card card-trainingHistory h-100" id="card-trainingHistory">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Learning History</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('training.history') }}" class="a-link">View All</a>
                                </div>
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
    /* Chart cards size to their content (no h-100). The chart wrapper itself
       has a fixed height so the card stays compact regardless of what other
       cards in the row look like. */
    .chart-flex-wrap {
        position: relative;
        height: 320px;
        width: 100%;
    }
    .chart-flex-wrap canvas {
        position: absolute !important;
        inset: 0;
        width: 100% !important;
        height: 100% !important;
    }

    /* Compulsory Trainings — Action Needed table polish */
    .compulsory-action-card thead th {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #6c757d;
        background: #f7f9fb;
        border-bottom: 1px solid #e6e9ec;
        padding: 10px 12px;
    }
    .compulsory-action-card tbody td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f5;
    }
    .compulsory-action-card tbody tr:last-child td {
        border-bottom: 0;
    }
    .compulsory-action-card tbody tr:hover {
        background: #f8fafc;
    }
    .compulsory-action-card .badge {
        padding: 6px 10px;
        font-weight: 500;
        letter-spacing: 0.2px;
    }
    .compulsory-action-card .btn.btn-themeBlue {
        padding: 6px 14px;
    }

    .fc-day.custom-dot::after {
        content: '';
        position: absolute;
        left: 75%!important;
        bottom: 10%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: #2EACB3;
        border-radius: 50%;
    }
</style>
@endsection

@section('import-scripts')
    <script type="text/javascript">
        function truncateWords(text, wordLimit) {
            if (!text) return '';
            var words = String(text).trim().split(/\s+/);
            if (words.length <= wordLimit) return text;
            return words.slice(0, wordLimit).join(' ') + '…';
        }

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
            // Call function to fetch and render the chart
            fetchTrainingParticipation();
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
                // navLinks: true switches FullCalendar v3 into a day-view on date click
                // and renders an empty "April 8, Wednesday" header. We just want to update
                // the side panel via dayClick, so disable nav links.
                navLinks: false,
                // Let the day grid grow to its natural height instead of FullCalendar's
                // default 213px scroller, which forces an internal vertical scrollbar.
                contentHeight: 'auto',

                events: function(start, end, timezone, callback) {
                    $.ajax({
                        url: "{{ route('get.learning.sessions') }}", // Adjusted for training sessions
                        type: "GET",
                        data: {
                            start_date: start.format('YYYY-MM-DD'),
                            end_date: end.format('YYYY-MM-DD')
                        },
                        success: function(response) {
                            // Cache the latest payload so navigation re-paints dots without
                            // re-fetching, and so we can repaint after the day grid renders.
                            window._learningSessions = response.data || [];
                            callback([]); // No events displayed, just dots
                            // Defer to next tick: FullCalendar v3 sometimes builds .fc-day
                            // cells AFTER the events callback resolves, so applying the
                            // class synchronously can land on a torn-down grid.
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
                    // Re-paint dots once the new month's day cells exist.
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
                                                    <p>${truncateWords(session.description, 50) || "No description available"}</p>
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


        // Paint a marker dot on every day the cached training sessions cover.
        // Called after `events:` resolves AND after each `viewRender`, so the dots
        // survive month navigation even if the day grid is rebuilt.
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

                // moment 2.9.0 doesn't have isSameOrBefore — use !isAfter instead.
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
                    // Cap at 5 to keep the side panel free of an inner scrollbar.
                    let sessionsToShow = (response.data || []).slice(0, 5);
                    if (sessionsToShow.length > 0) {
                        sessionsToShow.forEach(session => {
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
                                                    <p>${truncateWords(session.description, 50) || "No description available"}</p>
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

        // h-100 + Bootstrap rows now handle equal heights — equalizeHeights() is
        // a no-op kept only so the function reference doesn't break anything else.
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

            // full-calendar   
            $(function () {

                var todayDate = moment().startOf('day');
                var YM = todayDate.format('YYYY-MM');
                var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
                var TODAY = todayDate.format('YYYY-MM-DD');
                var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

                var cal = $('#calendar').fullCalendar({
                    header: {
                        left: 'prev ',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0,
                    navLinks: false,
                    contentHeight: 'auto',
                    dayRender: function () {}
                    // (Duplicate init kept harmless — see above. Real config is in
                    // the first $('#calendar').fullCalendar({...}) call.)
                });

            });


        });

        function fetchTrainingAttendance() {
            $.ajax({
                url: "{{ route('learning.attendance.chart-data') }}", // Backend route
                type: "GET",
                success: function (response) {
                    // console.log(response);
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

            // ✅ Update the legend after the chart is created
            updateLegend(chartData.labels, chartData.colors);
        }

        // function updateLegend(labels, colors) {
        //     let legendContainer = $(".row.g-2.justify-content-center"); // Ensure this selector is correct
        //     legendContainer.empty(); // Clear existing legend items

        //     labels.forEach((label, index) => {
        //         let legendItem = `
        //             <div class="col-auto">
        //                 <div class="doughnut-label" style="display: flex; align-items: center;">
        //                     <span style="background-color: ${colors[index]}; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span>
        //                     <span>${label}</span>
        //                 </div>
        //             </div>`;
        //         legendContainer.append(legendItem);
        //     });
        // }

        function updateLegend(labels, colors) {
            let legendContainer = $("#doughnut-label");
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

        function fetchTrainingParticipation() {
            $.ajax({
                url: "{{ route('learning.participation.chart-data') }}", // Replace with your actual route
                type: "GET",
                success: function (response) {
                    if (response.success) {
                        updateStackedBarChart(response.data);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function () {
                    toastr.error("Failed to fetch participation data.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        }

        // Function to update the stacked bar chart dynamically
        function updateStackedBarChart(chartData) {
            var ctx = document.getElementById('myStackedBarChart').getContext('2d');

            // Ensure previous chart instance is destroyed properly
            if (window.myStackedBarChart && typeof window.myStackedBarChart.destroy === 'function') {
                window.myStackedBarChart.destroy();
            }

            window.myStackedBarChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels, // Learning names
                    datasets: chartData.datasets // Department-wise participation data
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                // Full untruncated label on hover
                                title: function (items) {
                                    return items[0].label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                // Show only the first ~12 chars; full label still in tooltip.
                                callback: function (value) {
                                    var label = this.getLabelForValue(value);
                                    return label && label.length > 12 ? label.slice(0, 12) + '…' : label;
                                },
                                autoSkip: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { stepSize: 5 }
                        }
                    }
                }
            });
        }
    </script>
    <script>
        $(document).ready(function () {
            fetchOnboardingChart();
            renderBreakdownChart();
        });

        function renderBreakdownChart() {
            var canvas = document.getElementById('breakdownChart');
            if (!canvas) return;
            var labels = JSON.parse(canvas.dataset.labels || '[]');
            var values = JSON.parse(canvas.dataset.values || '[]');
            var colors = JSON.parse(canvas.dataset.colors || '[]');
            if (!labels.length) return;

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Programs',
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#fff',
                        borderWidth: 1,
                        borderRadius: 6,
                    }]
                },
                options: {
                    indexAxis: 'y', // horizontal bars
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function (items) { return items[0].label; },
                                label:  function (item)  { return ' ' + item.formattedValue + ' programs'; }
                            }
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#f1f3f5' }, ticks: { precision: 0, stepSize: 1 } },
                        y: {
                            grid: { display: false },
                            ticks: {
                                // Truncate long category names; full name still in the tooltip.
                                callback: function (value) {
                                    var label = this.getLabelForValue(value);
                                    return label && label.length > 18 ? label.slice(0, 18) + '…' : label;
                                }
                            }
                        }
                    }
                }
            });
        }

        function fetchOnboardingChart() {
            $.ajax({
                url: "{{ route('learning.manager.onboarding.chart') }}",
                type: "GET",
                success: function (response) {
                    if (response.success) {
                        renderOnboardingChart(response.data);
                    }
                },
                error: function () {
                    renderOnboardingChart({ labels: [], datasets: [] });
                }
            });
        }

        function renderOnboardingChart(chartData) {
            var ctx = document.getElementById('onboardingChart').getContext('2d');

            if (window.onboardingChartInstance && typeof window.onboardingChartInstance.destroy === 'function') {
                window.onboardingChartInstance.destroy();
            }

            window.onboardingChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        layout: { padding: 0 },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                title: function (items) { return items[0].label; }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                callback: function (value) {
                                    var label = this.getLabelForValue(value);
                                    return label && label.length > 12 ? label.slice(0, 12) + '…' : label;
                                },
                                autoSkip: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { stepSize: 5 }
                        }
                    }
                }
            });

            renderOnboardingLegend(chartData.datasets);
        }

        function renderOnboardingLegend(datasets) {
            var $legend = $('#onboardingChartLegend');
            $legend.empty();
            if (!datasets || datasets.length === 0) {
                $legend.append('<div class="col-12"><p class="text-muted small mb-0">No scheduled learning yet.</p></div>');
                return;
            }
            datasets.forEach(function (ds) {
                // Inline pill — sits horizontally under the chart now (legend
                // row was moved out of the right-side empty col).
                $legend.append(
                    '<div class="col-auto">' +
                        '<div class="doughnut-label">' +
                            '<span style="background-color:' + ds.backgroundColor + ';"></span>' +
                            $('<div>').text(ds.label).html() +
                        '</div>' +
                    '</div>'
                );
            });
        }
    </script>
@endsection