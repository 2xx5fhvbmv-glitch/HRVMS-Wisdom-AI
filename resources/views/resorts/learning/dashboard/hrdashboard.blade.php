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
                {{-- Compulsory % is a static placeholder; no destination route — drop the arrow --}}
                <div class="col-lg-3 col-sm-6 ">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Completed Compulsory Learning</p>
                                <strong>70%</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 ">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Scheduled Learning</p>
                                <strong>{{$scheduled_trainings_count ?? 0}}</strong>
                            </div>
                            <a href="{{route('learning.schedule.index')}}?status=Scheduled">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 ">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Completed Learning Programs</p>
                                <strong>{{$completed_trainings_count ?? 0}}</strong>
                            </div>
                            <a href="{{route('training.history')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 ">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Pending Learning Programs</p>
                                <strong>{{$pending_trainings_count ?? 0}}</strong>
                            </div>
                            <a href="{{route('learning.request.index')}}?status=Pending">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Pending Actions (wide) + Calendar (narrow) — same layout pattern as the manager dashboard --}}
                <div class="col-xl-9 col-12 @if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false) d-none @endif">
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
                                @foreach($pending_learning_request->take(4) as $request)
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

                <div class="col-xl-3 col-md-6 @if(Common::checkRouteWisePermission('learning.calendar.index',config('settings.resort_permissions.view')) == false) d-none @endif" id="right-ldDash">
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

                {{-- Row 3: three donut/score cards (Feedback · Onboarding Progress · Learning Attendance) --}}
                <div class="col-xl-4 col-md-6">
                    <div class="card card-feedbackEvaluation h-100" id="card-feedbackEvaluationHR">
                        <div class="card-title">
                            <h3>Feedback and Evaluation</h3>
                        </div>
                        <div class="progress-block">
                            <div class="progress-container blue" data-progress="{{ $feedbackAvgScore ?? 0 }}" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Average Trainer Performance Score">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                            </div>
                            <div class="text">
                                <h5>{{ is_null($feedbackAvgScore) ? '—' : ($feedbackAvgScore . '%') }}</h5>
                                <p>AVERAGE FEEDBACK SCORES</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <p>Over Time:</p>
                            <p class="fw-500">Trainer Performance</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card card-feedbackEvaluation h-100">
                        <div class="card-title">
                            <h3>Onboarding Learning Progress</h3>
                        </div>
                        <div class="progress-block">
                            <div class="progress-container blue" data-progress="{{ $onboardingProgress ?? 0 }}" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="New Hires Completing Compulsory Training">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                            </div>
                            <div class="text">
                                <h5>{{ is_null($onboardingProgress) ? '—' : ($onboardingProgress . '%') }}</h5>
                                <p>NEW HIRES COMPLETING THEIR COMPULSORY TRAINING</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-12 @if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card h-100">
                        <div class="card-title">
                            <h3>Learning Attendance</h3>
                        </div>
                        <p id="lateAttendanceText" class="small mb-2">Late Attendance: --%</p>

                        <div class="trainingAttendance-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        @php
                            $attBreakdown = $learningAttendance ?? [];
                            $attLegend = [
                                'Present' => 'bg-theme',
                                'Late'    => 'bg-themeWarning',
                                'Absent'  => 'bg-themeRed',
                            ];
                        @endphp
                        <div class="row g-2 justify-content-center">
                            @forelse($attLegend as $label => $cls)
                                @if(($attBreakdown[$label] ?? 0) > 0)
                                    <div class="col-auto">
                                        <div class="doughnut-label" title="{{ $attBreakdown[$label] }} records">
                                            <span class="{{ $cls }}"></span>{{ $label }} ({{ $attBreakdown[$label] }})
                                        </div>
                                    </div>
                                @endif
                            @empty
                            @endforelse
                            @if(empty(array_filter($attBreakdown)))
                                <div class="col-auto text-muted small">No attendance recorded yet.</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Row 4: Learning Hours bar chart (wide) + Learning Completion Rates (narrow) --}}
                <div class="col-xl-8 @if(Common::checkRouteWisePermission('learning.programs.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card card-participation h-100">
                        <div class="card-title mb-md-3">
                            <h3>Learning Hours</h3>
                        </div>
                        @php
                            $hasLearningHours = ($learningHoursByProg ?? collect())->count() > 0;
                            $learningHoursHeight = max(220, (($learningHoursByProg ?? collect())->count()) * 36);
                        @endphp
                        @if($hasLearningHours)
                            <div class="chart-flex-wrap" style="height: {{ $learningHoursHeight }}px;">
                                <canvas id="myStackedBarChart"></canvas>
                            </div>
                        @else
                            <p class="text-muted small mb-0">No training programs scheduled yet.</p>
                        @endif
                    </div>
                </div>

                <div class="col-xl-4 @if(Common::checkRouteWisePermission('learning.request.add',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card h-100">
                        <div class="card-title mb-md-4">
                            <h3>Learning Completion Rates</h3>
                        </div>

                        <div class="three-progressbar mb-md-4 mb-3">
                            @if($completionData)
                                @foreach($completionData as $data)
                                    <div class="progress-container blue" data-progress="{{ $data['completion_rate'] }}" data-bs-toggle="tooltip"
                                        data-bs-placement="bottom" title="{{ $data['training_name'] }} - {{ $data['completion_rate'] }}">
                                        <svg class="progress-circle" viewBox="0 0 120 120">
                                            <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                            <circle class="progress" cx="60" cy="60" r="54"></circle>
                                        </svg>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="row g-2 justify-content-center doughnut-labelTop">
                            @forelse ($completionData as $data)
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span></span>
                                        {{ $data['training_name'] }} <br>{{ $data['completion_rate'] }}
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted small">No completion data yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Row 5: Learning History full width --}}
                <div class="col-12 @if(Common::checkRouteWisePermission('learning.schedule',config('settings.resort_permissions.view')) == false) d-none @endif">
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
                        <div class="leaveUser-main row g-3">
                            @if($trainings->isEmpty())
                                <div class="col-12"><p class="text-muted small mb-0">No training history available.</p></div>
                            @else
                                @foreach ($trainings->take(5) as $training)
                                    @php
                                        $totalTrainingDays = \Carbon\Carbon::parse($training->start_date)->diffInDays(\Carbon\Carbon::parse($training->end_date)) + 1;
                                        $totalParticipants = $training->participants->count();
                                        $totalExpectedAttendance = $totalTrainingDays * $totalParticipants;
                                        $actualAttendance = $training->trainingAttendances->where('status', 'Present')->count();
                                        $attendancePercentage = ($totalExpectedAttendance > 0)
                                            ? round(($actualAttendance / $totalExpectedAttendance) * 100, 2)
                                            : 0;
                                    @endphp
                                    <div class="col-xl-4 col-md-6">
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
    /* Chart wrapper used by Learning Hours bar chart — fixed-height container so
       the canvas fills it via responsive: true / maintainAspectRatio: false. */
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
                // Disable nav links — clicking a date should only refresh the side
                // panel via dayClick, not switch FullCalendar into a day view.
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
                            window._learningSessions = response.data || [];
                            callback([]); // No events displayed, just dots
                            // FullCalendar v3 may build day cells AFTER the events callback
                            // resolves — defer one tick so the addClass lands on real cells.
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
                    // Re-apply dots once the new month's day cells exist.
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

        // Paint a marker dot on every day each cached training session covers.
        // Called after the events: callback resolves AND on every viewRender, so
        // the dots survive month-to-month navigation.
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

            // (Duplicate fullCalendar() init removed — it re-initialised #calendar
            //  without the events / dayClick handlers configured above and reverted
            //  navLinks back to defaults, which is what produced the empty
            //  "April 15, 2026 / Wednesday" day-view header on date click.)
        });
    </script>
    <script type="module">
        // Learning Hours — horizontal bar per program. Program name on the y-axis,
        // hours on the x-axis. No duplicate legend below the chart.
        var learningHoursPalette = ['#014653','#2EACB3','#FED049','#8DC9C9','#333333','#7AD45A','#FF4B4B','#F5738D','#53CAFF'];
        var learningHoursRows = @json($learningHoursByProg ?? []);
        var learningHoursCanvas = document.getElementById('myStackedBarChart');

        if (learningHoursCanvas && learningHoursRows.length) {
            var learningHoursLabels = learningHoursRows.map(function (r) { return r.name || 'Untitled'; });
            var learningHoursData   = learningHoursRows.map(function (r) { return parseFloat(r.total_hours || 0); });
            var learningHoursColors = learningHoursRows.map(function (_r, i) { return learningHoursPalette[i % learningHoursPalette.length]; });
            var learningHoursMeta   = learningHoursRows.map(function (r) {
                return { sessions: r.session_count || 0, hours: parseFloat(r.total_hours || 0) };
            });

            new Chart(learningHoursCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: learningHoursLabels,
                    datasets: [{
                        label: 'Hours',
                        data: learningHoursData,
                        backgroundColor: learningHoursColors,
                        borderColor: '#fff',
                        borderWidth: 1,
                        borderRadius: 6,
                    }]
                },
                options: {
                    indexAxis: 'y',                 // horizontal bars: names on Y, hours on X
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }, // single dataset, no need for a legend
                        tooltip: {
                            callbacks: {
                                title: function (items) { return items[0].label; }, // full name on hover
                                label: function (item)  {
                                    var meta = learningHoursMeta[item.dataIndex] || {};
                                    return ' ' + item.formattedValue + ' hrs · ' + (meta.sessions || 0) + ' sessions';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: '#f1f3f5' },
                            ticks: { precision: 0 },
                            title: { display: true, text: 'Hours' }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
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

      

        var cty = document.getElementById('onboardingChart').getContext('2d');
        var onboardingChart = new Chart(cty, {
            type: 'bar',
            data: {
                labels: ['Learning 1', 'Learning 2', 'Learning 3', 'Learning 4', 'Learning 5', 'Learning 6'],
                datasets: [
                    {
                        label: 'Department  1',
                        data: [8, 20, 25, 10, 10, 20, 10],
                        backgroundColor: '#014653',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Department  2',
                        data: [5, 10, 4, 20, 2, 5, 10],
                        backgroundColor: '#2EACB3',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Department  3',
                        data: [20, 5, 20, 40, 22, 5, 20],
                        backgroundColor: '#FED049',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Department  4',
                        data: [5, 20, 15, 5, 5, 5, 10],
                        backgroundColor: '#8DC9C9',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Department  5',
                        data: [5, 7, 4, 4, 2, 5, 5],
                        backgroundColor: '#333333',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    }
                ]
            },
            options: {
                plugins: {
                    legend: {
                        display: false // Hide legend
                    },
                    layout: {
                        padding: 0 // Remove padding
                    },
                    tooltip: {
                        enabled: false // Disable tooltips
                    }
                },
                hover: {
                    mode: null // Disable hover effects
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false // Hide x-axis grid lines
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            display: false // Hide y-axis grid lines
                        },
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });

    </script>
@endsection