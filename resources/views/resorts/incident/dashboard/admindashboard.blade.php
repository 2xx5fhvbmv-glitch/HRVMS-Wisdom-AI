@extends('resorts.layouts.app')
@section('page_tab_title' ,"Incident Dashboard")

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
                            <span>Incident</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-auto ms-auto">
                        <select class="form-select select2t-none" id="select-budgeted"
                            aria-label="Default select example">
                            <option selected>Monthly</option>
                            <option value="1">bbb</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth card-incidentHr">
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Total Incidents</p>
                                <strong>{{$total_incidents ?? 0}}</strong>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Open Incidents</p>
                                <strong>{{$open_incidents ?? 0}}</strong>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Under Investigation</p>
                                <strong>{{$under_investigation_incidents ?? 0}}</strong>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Average Resolution Time</p>
                                <strong>{{$averageResolutionDays ?? 0}}</strong>
                                <span>Business Days</span>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class=" card">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Delegated Cases to Committee</h3>
                                </div>
                                <!-- <div class="col-auto">
                                    <a href="#" class="a-link">View All</a>
                                </div> -->
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table-lableNew table-incidentDelegated w-100">
                                <tr>
                                    <th>Committee Name</th>
                                    <th>Open Cases</th>
                                    <th>Status</th>
                                </tr>
                                @foreach($committeeSummary as $item)
                                    <tr>
                                        <td>{{ $item['name'] }}</td>
                                        <td>{{ $item['open'] }}</td>
                                        <td>
                                            @php
                                                $status = $item['status'];
                                                if ($status == 'Assigned To' || $status == 'Acknowledged') {
                                                    $badge = 'badge-themeYellow';
                                                } elseif (in_array($status, ['Investigation In Progress', 'Findings Submitted', 'Resolution Suggested', 'Under Review'])) {
                                                    $badge = 'badge-themeSuccess';
                                                } elseif ($status == 'Approval Pending') {
                                                    $badge = 'badge-themeDangerNew';
                                                } else {
                                                    $badge = 'badge-secondary';
                                                }
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ $status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card card-incidentSeverity">
                        <div class="card-title">
                            <h3>Incident Severity</h3>
                        </div>
                        <div class="leaveUser-main">
                            <div class="leaveUser-bgBlock">
                                <h6>Minor</h6>
                                <strong>{{ $severityCounts['Minor'] ?? 0 }}</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Moderate</h6>
                                <strong>{{ $severityCounts['Moderate'] ?? 0 }}</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Severe</h6>
                                <strong>{{ $severityCounts['Severe'] ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-title">
                            <h3>Incident</h3>
                        </div>
                        <div class="incident-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center ">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Resolved
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblue"></span>Unresolved
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 ">
                    <div class="card">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Incidents by Category</h3>
                                </div>
                                <div class="col-auto">
                                    <!-- <div class="form-group">
                                        <select class="form-select" aria-label="Default select example">
                                            <option selected="">Select duration</option>
                                            <option value="1">AAA</option>
                                            <option value="2">AAA</option>
                                        </select>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                        <div class="row g-md-4 g-2 align-items-center">
                            <div class="col-sm-6">
                                <div class="categoryIncidents-chart mb-3">
                                    <!-- <canvas id="categoryIncidentsChart" width="599" height="293"></canvas> -->
                                    <canvas id="categoryIncidentsChart"></canvas>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="row g-2 doughnut-labelTop">
                                @foreach($categoryLabels as $index => $label)
                                    <div class="col-sm-6 col-auto">
                                        <div class="doughnut-label">
                                            <span style="background-color: {{ ['#014653', '#53CAFF', '#EFB408', '#2EACB3', '#333333', '#8DC9C9'][$index % 6] }}"></span>
                                            {{ $label }} <br>{{ $categoryData[$index] }}
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-12">
                                    <p class="fw-500">Total: {{ $totalIncidents }} Incidents</p>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card card-comParticipation h-auto" id="card-comParticipation">
                        <div class="card-title mb-md-3">
                            <h3>Department-wise Participation</h3>
                        </div>
                        <div class="row g-md-4 g-2 align-items-center">
                            <div class="col-xxl-9 col-xl-12 col-md-9"> 
                                <canvas id="myStackedBarChart" width="544" height="326"></canvas></div>
                            <div class="col-xxl-3 col-xl-auto col-md-3">
                                <div class="row g-2 doughnut-labelTop" id="deptWiseParticipation">
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeGray"></span>Department 1
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeGreenLight"></span>Department 2
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeRed"></span>Department 3
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeRedLight"></span>Department 4
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeSkyblueLightNew"></span>Department 5
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Incidents by Category</h3>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group">
                                        <select class="form-select" id="incidentYearSelect">
                                            <!-- dynamically filled by JS -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <canvas id="incidentTrendChart" width="365" height="326"></canvas>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card h-auto card-incidentResolTime" id="card-incidentResolTime">
                        <div class="card-title">
                            <h3>Resolution Timelines</h3>
                        </div>
                        <div class="leaveUser-main" id="resolution-timeline">
                            <div class="leaveUser-bgBlock">
                                <h6>Cases Nearing Deadline</h6>
                                <strong id="casesNearingDeadline">0</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Breached Timelines</h6>
                                <strong id="breachedTimelines">0</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Resolved Cases</h6>
                                <strong id="resolvedCases">0%</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Open Investigations</h6>
                                <strong id="openInvestigations">0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-md-6">
                    <div class="card card-upMeetIncident" id="card-upMeetIncident">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Upcoming Meetings</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('incident.meeting')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main" id="upcoming-meetings"></div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card card-preventiveIncident">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Preventive</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('incident.preventive') }}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main" id="preventive-list">
                            <!-- Dynamic items will load here -->
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card card-resolutionApp">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Pending Resolution Approval</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('incident.pending-approvals') }}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main" id="pending-resolution-list">
                            <!-- Dynamic content will be injected here -->
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card card-wiInsightsIncident">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">WI Insights</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main">
                            <div class="leaveUser-block">
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                        typesetting
                                        industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                        industry
                                        Lorem typesetting industry ipsum.</p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-block">
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                        typesetting
                                        industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                        industry
                                        Lorem typesetting industry ipsum.</p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-block">
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                        typesetting
                                        industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                        industry
                                        Lorem typesetting industry ipsum.</p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-block">
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                        typesetting
                                        industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                        industry
                                        Lorem typesetting industry ipsum.</p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
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
        let myStackedBarChartInstance = null;
        let incidentChart;
        const meetingDetailBaseUrl = "{{ route('incident.meeting.detail', ['id' => 'MEETING_ID']) }}";
                          
        $(document).ready(function () {
            loadParticipationChart();
            loadResolutionTimelineStats();
            loadUpcomingMeetings();
            loadPreventiveActions();
            loadPendingResolutions();

            const yearSelect = $('#incidentYearSelect');
            const currentYear = new Date().getFullYear();

            for (let y = currentYear; y >= currentYear - 5; y--) {
                yearSelect.append(`<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`);
            }

            fetchIncidentTrends(currentYear);

            yearSelect.on('change', function () {
                fetchIncidentTrends($(this).val());
            });
        });

        function loadParticipationChart() {
            $.ajax({
                url: '{{route("incident.chart.getDepartmentWiseParticipation")}}', // Adjust this to your route
                type: 'GET',
                dataType: 'json',
                success: function (chartData) {
                    const ctx = document.getElementById('myStackedBarChart').getContext('2d');

                    // ✅ Destroy the old chart if it exists
                    if (myStackedBarChartInstance) {
                        myStackedBarChartInstance.destroy();
                    }

                    // ✅ Create the new chart
                    myStackedBarChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
                        },
                        options: {
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false },
                                layout: { padding: 0 }
                            },
                            hover: { mode: null },
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: { display: false }
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    grid: { display: false },
                                    ticks: { stepSize: 20 }
                                }
                            }
                        }
                    });

                    // Update legend dynamically (optional)
                    const legendContainer = $('#deptWiseParticipation');
                    legendContainer.empty();
                    chartData.datasets.forEach(dataset => {
                        legendContainer.append(`
                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                <div class="doughnut-label">
                                    <span style="background-color: ${dataset.backgroundColor}"></span>${dataset.label}
                                </div>
                            </div>
                        `);
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error loading chart data:', error);
                }
            });
        }

        function fetchIncidentTrends(year) {
            $.ajax({
                url: '{{route("incident.chart.getTrends")}}',
                method: 'GET',
                data: { year: year },
                success: function (res) {
                    if (incidentChart) {
                        incidentChart.destroy();
                    }

                    const ctx = document.getElementById('incidentTrendChart').getContext('2d');
                    incidentChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: res.labels,
                            datasets: [{
                                label: 'Incidents',
                                data: res.data,
                                borderColor: '#014653',
                                backgroundColor: '#014653',
                                borderWidth: 1,
                                fill: false,
                                tension: 0.4,
                                pointRadius: 0
                            }]
                        },
                        options: {
                            plugins: { legend: { display: false }},
                            scales: {
                                x: { grid: { display: false }},
                                y: {
                                    beginAtZero: true,
                                    grid: { display: false },
                                    ticks: { stepSize: 5 }
                                }
                            }
                        }
                    });
                }
            });
        }

        function loadResolutionTimelineStats() {
            $.ajax({
                url: '{{route("incident.getResolutionTimelineStats")}}',
                method: 'GET',
                success: function (res) {
                    $('#casesNearingDeadline').text(res.nearingDeadline);
                    $('#breachedTimelines').text(res.breachedTimelines);
                    $('#resolvedCases').text(res.resolvedPercentage + '%');
                    $('#openInvestigations').text(res.openInvestigations);
                }
            });
        }

        function loadUpcomingMeetings() {
            $.ajax({
                url: '{{ route("incident.getUpcomingMeetings") }}',
                method: 'GET',
                success: function (meetings) {
                    let container = $('#upcoming-meetings');
                    container.empty();

                    if (meetings.length === 0) {
                        container.append('<div class="text-muted px-3 py-2">No upcoming meetings.</div>');
                        return;
                    }

                    meetings.forEach(meeting => {
                        container.append(`
                            <div class="leaveUser-block">
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <h6>${meeting.title}</h6>
                                        <span class="badge badge-themeNew1 border-0">${meeting.day_label}, ${meeting.scheduled_time}</span>
                                    </div>
                                    <p>${meeting.description}</p>
                                    <div>
                                        <a href="${meetingDetailBaseUrl.replace('MEETING_ID', meeting.id)}" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                }
            });
        }

        function loadPreventiveActions() {
            $.ajax({
                url: '{{ route("incident.preventive.list") }}',
                method: 'GET',
                success: function (actions) {
                    let container = $('#preventive-list');
                    container.empty();

                    if (actions.length === 0) {
                        container.append('<div class="text-muted px-3 py-2">No preventive actions found.</div>');
                    }

                    actions.forEach(action => {
                        container.append(`
                            <div class="leaveUser-block">
                                <div>
                                    <h6>${action.title}</h6>
                                    <p>${action.description}</p>
                                </div>
                            </div>
                        `);
                    });
                }
            });
        }

        function loadPendingResolutions() {
            $.ajax({
                url: '{{ route("incident.pendingResolutions") }}',
                method: 'GET',
                success: function (data) {
                    let container = $('#pending-resolution-list');
                    container.empty();

                    if (data.length === 0) {
                        container.append('<div class="text-muted px-3 py-2">No pending resolutions.</div>');
                    }

                    data.forEach(item => {
                        container.append(`
                            <div class="leaveUser-block">
                                <div>
                                    <h6>${item.incident_name}</h6>
                                    <p>${item.investigation_findings ?? 'No findings yet.'}</p>
                                    <p>${item.outcome_type ?? 'No Outcome type yet.'}</p>
                                    <p>${item.action_taken ?? 'No Action taken yet.'}</p>

                                </div>
                            </div>
                        `);
                    });
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
            equalizeHeights('card-incidentResolTime', ['card-upMeetIncident']);
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
    </script>

    <script type="module">
        let myStackedBarChartInstance; // Keep a global reference

       var ctx = document.getElementById('myDoughnutChart').getContext('2d');

        const doughnutLabelsInside = {
            id: 'doughnutLabelsInside',
            afterDraw: function (chart) {
                var ctx = chart.ctx;
                chart.data.datasets.forEach(function (dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach(function (element, index) {
                            var dataValue = dataset.data[index];
                            var total = dataset.data.reduce(function (acc, val) {
                                return acc + val;
                            }, 0);
                            var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                            var position = element.tooltipPosition();

                            ctx.fillStyle = '#fff';
                            ctx.font = 'normal 14px Poppins';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.fillText(percentage, position.x, position.y);
                        });
                    }
                });
            }
        };

        var myDoughnutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Unresolved', 'Resolved'],
                datasets: [{
                    data: [{{ $unresolvedCount }}, {{ $resolvedCount }}],
                    backgroundColor: ['#2EACB3', '#014653'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    doughnutLabelsInside: true,
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
            plugins: [doughnutLabelsInside]
        });

        var cty = document.getElementById('categoryIncidentsChart').getContext('2d');

        const doughnutLabelsInsideN = {
            id: 'doughnutLabelsInsideN',
            afterDraw: function (chart) {
                var ctx = chart.ctx;
                chart.data.datasets.forEach(function (dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach(function (element, index) {
                            var dataValue = dataset.data[index];
                            var total = dataset.data.reduce(function (acc, val) {
                                return acc + val;
                            }, 0);
                            var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                            var position = element.tooltipPosition();

                            ctx.fillStyle = '#fff';
                            ctx.font = 'medium 18px Poppins';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.fillText(percentage, position.x, position.y);
                        });
                    }
                });
            }
        };

        const categoryLabels = {!! json_encode($categoryLabels) !!};
        const categoryData = {!! json_encode($categoryData) !!};
        const categoryColors = ['#014653', '#53CAFF', '#EFB408', '#2EACB3', '#333333', '#8DC9C9'];

        var categoryIncidentsChart = new Chart(cty, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: categoryColors.slice(0, categoryData.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    doughnutLabelsInsideN: true,
                    legend: {
                        display: false
                    }
                },
                layout: {
                    padding: { top: 10, bottom: 10, left: 0, right: 0 }
                }
            },
            plugins: [doughnutLabelsInsideN]
        });

    </script>
@endsection