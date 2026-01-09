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
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-lg-3 col-sm-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Total Incidents</p>
                                <strong>{{$total_incidents ?? 0}}</strong>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Pending</p>
                                <strong>{{$pending_incidents ?? 0}}</strong>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif">
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
                <div class="col-lg-3 col-sm-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Resolved</p>
                                <strong>{{$resolvedCount ?? 0}}</strong>
                            </div>
                            <a href="{{route('incident.index')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
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
                    <div class="card h-auto" id="card-incidentHOD">
                        <div class="card-title">
                            <h3>Incident</h3>
                        </div>
                        <div class="incident-chart mb-3">
                            <canvas id="incidentChart" width="328" height="328"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center ">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Resolved
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblue"></span>Under Investigation
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeWarning"></span>Pending
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card card-todoIncidentHOD" id="card-todoIncidentHOD">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Incident List</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('incident.index')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main" id="incidentTodoList">
                            <!-- Dynamic content will be injected here -->
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
                                    <div class="form-group">
                                        <!-- <select class="form-select" aria-label="Default select example">
                                            <option selected="">Select duration</option>
                                            <option value="1">AAA</option>
                                            <option value="2">AAA</option>
                                        </select> -->
                                    </div>
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

                <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif ">
                    <div class="card">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Incident Trends</h3>
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
 
                <div class="col-lg-6 @if(Common::checkRouteWisePermission('incident.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-preventiveIncident">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Preventive</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('incident.hod.preventive') }}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main" id="preventive-list">
                            <!-- Dynamic items will load here -->
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
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
        const incidentDetailBaseUrl = "{{ route('incident.view', ['id' => 'INCIDENT_ID']) }}";
        let incidentChart;

        $(document).ready(function () {
           getIncidentChart();
           loadIncidentTodoList();
           fetchIncidentTrends();
           loadResolutionTimelineStats();
           loadPreventiveActions();
        });

        function getIncidentChart() {
            $.ajax({
                url: '{{ route("incident.getIncident.chartdata") }}', // Adjust this to your route
                type: 'GET',
                dataType: 'json',
                success: function (chartData) {
                    const ctx = document.getElementById('incidentChart').getContext('2d');

                    const pieLabelsInside = {
                        id: 'pieLabelsInside',
                        afterDraw(chart) {
                            const ctx = chart.ctx;
                            const dataset = chart.data.datasets[0];
                            const meta = chart.getDatasetMeta(0);
                            const total = dataset.data.reduce((a, b) => a + b, 0);

                            meta.data.forEach((element, index) => {
                                const value = dataset.data[index];
                                const percent = ((value / total) * 100).toFixed(0) + '%';
                                const pos = element.tooltipPosition();

                                ctx.fillStyle = '#fff';
                                ctx.font = 'bold 16px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(percent, pos.x, pos.y);
                            });
                        }
                    };

                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                data: chartData.data,
                                backgroundColor: ['#014653', '#2EACB3', '#EFB408'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                pieLabelsInside: true,
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function (ctx) {
                                            return `${ctx.label}: ${ctx.raw}`;
                                        }
                                    }
                                }
                            },
                            layout: {
                                padding: { top: 10, bottom: 10 }
                            }
                        },
                        plugins: [pieLabelsInside]
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error loading chart data:', error);
                }
            });
        }

        function loadIncidentTodoList() {
            $.ajax({
                url: '{{ route("incident.todoList") }}',
                method: 'GET',
                success: function (data) {
                    let html = '';

                    if (data.length === 0) {
                        html = `<div class="text-center py-3">No incidents found.</div>`;
                    } else {
                        data.forEach(incident => {
                            html += `
                            <div class="leaveUser-block">
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <h6>${incident.title}</h6>
                                        <span class="badge badge-themeNew1 border-0">${incident.time_ago}</span>
                                    </div>
                                    <p>${incident.description}</p>
                                    <div>
                                        <a href="${incidentDetailBaseUrl.replace('INCIDENT_ID', btoa(incident.id))}" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }

                    $('#incidentTodoList').html(html);
                },
                error: function (err) {
                    console.error('Failed to load incident to-do list:', err);
                    $('#incidentTodoList').html(`<div class="text-danger py-3 text-center">Error loading data.</div>`);
                }
            });
        }

        function fetchIncidentTrends(year) {
            $.ajax({
                url: '{{route("incident.hod-chart.getTrends")}}',
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
                url: '{{route("incident.hod.getResolutionTimelineStats")}}',
                method: 'GET',
                success: function (res) {
                    $('#casesNearingDeadline').text(res.nearingDeadline);
                    $('#breachedTimelines').text(res.breachedTimelines);
                    $('#resolvedCases').text(res.resolvedPercentage + '%');
                    $('#openInvestigations').text(res.openInvestigations);
                }
            });
        }

        function loadPreventiveActions() {
            $.ajax({
                url: '{{ route("incident.preventive.hodlist") }}',
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
            equalizeHeights('card-incidentHOD', ['card-todoIncidentHOD']);
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