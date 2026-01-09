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
                            <a href="#">
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
                            <a href="#">
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
                            <a href="#">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Completed Compulsory Learning</p>
                                <strong>{{$compulsory_completed_traing ?? 0}}</strong>
                            </div>
                            <a href="#">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-title mb-md-4">
                            <h3>Breakdown of Learning Programs</h3>
                        </div>

                        <div class="three-progressbar mb-md-4 mb-3">
                            @foreach ($categories as $category)
                                <div class="progress-container" 
                                    style="border-color: {{ $category->color }};" 
                                    data-progress="{{ $category->programs_count }}" 
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" 
                                    title="{{ $category->category }} - {{ $category->programs_count }}">

                                    <svg class="progress-circle" viewBox="0 0 120 120">
                                        <circle class="progress-background" cx="60" cy="60" r="54" style="stroke: #eee;"></circle>
                                        <circle class="progress" cx="60" cy="60" r="54" style="stroke: {{ $category->color }};"></circle>
                                    </svg>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-2 justify-content-center doughnut-labelTop">
                            @foreach ($categories as $category)
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span style="background-color: {{ $category->color }};"></span>
                                        {{ $category->category }} <br>{{ $category->programs_count }} 
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>

                <div class="col-xl-6 order-3 order-xxl-2">
                    <div class="card card-participation">
                        <div class="card-title mb-md-3">
                            <h3>Participation</h3>
                        </div>
                        <div class="row g-md-4 g-2">
                            <div class="col-xxl-9 col-xl-12 col-md-9"> <canvas id="myStackedBarChart" width="544"
                                    height="326"></canvas></div>
                           
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 order-2 order-xxl-3 ">
                    <div class="card">
                        <div class="card-title">
                            <h3>Learning Attendance</h3>
                        </div>
                        <p id="lateAttendanceText">Late Attendance: --%</p> <!-- Placeholder, will be updated dynamically -->

                        <div class="trainingAttendance-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center" id="doughnut-label">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Learning 1
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblueLightNew"></span>Learning 1
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeWarning"></span>Learning 1
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblue"></span>Learning 1
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeGray"></span>Learning 1
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblueLight"></span>Learning 1
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-9 col-xl-8 h-auto order-4 order-xxl-4" id="left-ldDash">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-xxl-4 col-sm-6 ">
                            <div class=" card">
                                <div class=" card-title">
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
                                        @if($absentees)
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
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-4 col-sm-6">
                            <div class="card card-feedbackEvaluation h-auto" id="card-feedbackEvaluation">
                                <div class="card-title">
                                    <h3>Feedback and Evaluation</h3>
                                </div>
                                <div class="progress-block">
                                    <div class="progress-container blue " data-progress="90" data-bs-toggle="tooltip"
                                        data-bs-placement="bottom" title="Male Staff Occupied 90%">
                                        <svg class="progress-circle" viewBox="0 0 120 120">
                                            <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                            <circle class="progress" cx="60" cy="60" r="54"></circle>
                                        </svg>
                                    </div>
                                    <div class="text">
                                        <h5>70%</h5>
                                        <p>AVERAGE FEEDBACK SCORES</p>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <p>Over Time:</p>
                                    <p class="fw-500">Trainer Performance</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-4 col-sm-6 ">
                            <div class=" card " id="card-pendingActions">
                                <div class=" card-title">
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
                                    @if($pending_learning_request)
                                        @foreach($pending_learning_request as $request)
                                            <div class="leaveUser-block">
                                                <div>
                                                    <h6>{{$request->learning->name}}</h6>
                                                    <p>{{$request->learning->description}}</p>
                                                    <div>
                                                        <a href="{{ route('learning.request.details', ['id' => $request->id]) }}" class="a-linkTheme">
                                                            View Details
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xxl-8 order-2 order-xxl-1">
                            <div class="card card-participation  h-auto" id="card-onboarding">
                                <div class="card-title mb-md-3">
                                    <h3>Onboarding Learning</h3>
                                </div>
                                <div class="row g-md-4 g-2">
                                    <div class="col-xxl-9 col-xl-12 col-md-9">
                                        <canvas id="onboardingChart" width="544" height="326"></canvas>
                                    </div>
                                    <div class="col-xxl-3 col-xl-auto col-lg-2 col-md-3 offset-lg-1 offset-xl-0 ">
                                        <div class="row g-2">
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-theme"></span>Department 1
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeLightBlue"></span>Department 2
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeYellow"></span>Department 3
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeSkyblueLight"></span>Department 4
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeGray"></span>Department 5
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-4 col-sm-6 order-1 order-xxl-2">
                            <div class="card card-trainingHistory" id="card-trainingHistory">
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
                                        <p>No training history available.</p>
                                    @else
                                        @foreach ($trainings as $training)
                                      
                                        @php
                                            // Calculate total training days
                                            $totalTrainingDays = \Carbon\Carbon::parse($training->start_date)->diffInDays(\Carbon\Carbon::parse($training->end_date)) + 1;
                                            
                                            // Get total participants
                                            $totalParticipants = $training->participants->count();
                                            
                                            // Expected attendance count
                                            $totalExpectedAttendance = $totalTrainingDays * $totalParticipants;
                                            
                                            // Count actual attendance (only 'Present' status)
                                            $actualAttendance = $training->trainingAttendances->where('status', 'Present')->count();
                                            
                                            // Calculate attendance percentage
                                            $attendancePercentage = ($totalExpectedAttendance > 0) 
                                            ? round(($actualAttendance / $totalExpectedAttendance) * 100, 2) 
                                            : 0;
                                            @endphp
                                            <div class="leaveUser-block">
                                                <div>
                                                    <div class="date"><i class="fa-regular fa-calendar"></i>
                                                        <?= date('d M Y', strtotime($training->start_date)) . ' - ' . date('d M Y', strtotime($training->end_date)) ?>
                                                    </div>
                                                    <h6><?= $training->learningProgram->name ?? 'Learning Program' ?></h6>
                                                    <p><?= $training->description ?></p>
                                                    <span>Attendance: <?= $attendancePercentage ?>%</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-xxl-3 col-xl-4 col-lg-6 order-5 order-xxl-5" id="right-ldDash">
                    <div class="card calendar-card calendarLD-card">
                        <div class="ldDash-block">
                            <div class="mb-4 overflow-hidden">
                                <div id="calendar"></div>
                            </div>
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>Upcoming Learning Sessions</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-main" id="leaveUser-main">
                                <!-- Dynamic content will be loaded here -->
                            </div>
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
                navLinks: true,

                events: function(start, end, timezone, callback) {
                    $.ajax({
                        url: "{{ route('get.learning.sessions') }}", // Adjusted for training sessions
                        type: "GET",
                        data: {
                            start_date: start.format('YYYY-MM-DD'),
                            end_date: end.format('YYYY-MM-DD')
                        },
                        success: function(response) {
                            $('.fc-day').removeClass('custom-dot'); // Remove previous dots

                            if (response.data.length > 0) {
                                response.data.forEach(function(session) {
                                    let formattedDate = moment(session.session_date).format('YYYY-MM-DD');
                                    console.log(formattedDate);
                                    let dayCell = $(`.fc-day[data-date="${formattedDate}"]`);
                                    
                                    if (dayCell.length) {
                                        dayCell.addClass('custom-dot'); // Add class to mark event
                                    }
                                });
                            }
                            callback([]); // No events displayed, just dots
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

        // Adjust heights on page load and window resize
        function adjustHeights() {
            equalizeHeights('card-feedbackEvaluation', ['card-pendingActions']);
            equalizeHeights('card-onboarding', ['card-trainingHistory']);
            equalizeHeights('left-ldDash', ['right-ldDash']);
        }

        window.onload = adjustHeights; // Initial height adjustment
        window.onresize = adjustHeights; // Adjust heights on window resize

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
                    eventLimit: 0, // allow "more" link when too many events
                    navLinks: true,
                    dayRender: function (a) {
                        //console.log(a)
                    }
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
                    plugins: {
                        legend: {
                            display: true // Show legend for departments
                        },
                        tooltip: {
                            enabled: true // Enable tooltips
                        }
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
                                stepSize: 5
                            }
                        }
                    }
                }
            });
        }
    </script>
    <script type="module">
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
        })
    </script>
@endsection