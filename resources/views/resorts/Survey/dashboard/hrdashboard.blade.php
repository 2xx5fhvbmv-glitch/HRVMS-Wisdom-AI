@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Survey</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <!-- <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                        <option value="1">bbb</option>
                    </select>
                </div> -->
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth ">
            <div class="col-lg-3 col-sm-6 ">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Surveys</p>
                            <strong>{{ $total_Survey_count }}</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Open Surveys</p>
                            <strong>{{ $OngoingSurvey_count }}</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Pending Surveys</p>
                            <strong>{{ $PublishSurvey_count }}</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Complete Surveys</p>
                            <strong>{{ $CompleteSurvey_count }}</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card  h-auto" id="card-surveyStatus">
                    <div class="card-title">
                        <h3>Survey Status</h3>
                    </div>
                    @foreach($OngoingSurvey as $survey)
                        @php
                            $progress = ($survey->total_count > 0) ? round(($survey->completed_count / $survey->total_count) * 100) : 0;
                        @endphp
                        <div class="surveyStatus-block bg-themeGrayLight">
                            <div class="head">
                                <div>
                                    <h6>{{ $survey->title }}</h6>
                                    <p>Creation Date: {{ \Carbon\Carbon::parse($survey->Start_date)->format('d M Y') }} | 
                                        Closing Date: {{ \Carbon\Carbon::parse($survey->End_date)->format('d M Y') }}</p>                                </div>
                                <span class="badge badge-green">
                                    {{ $survey->Status }}
                                </span>
                            </div>
                            <div class="body">
                                <div class="d-flex">
                                    <span>Participation Rate</span>
                                    <div class="progress progress-custom progress-themeskyblue">
                                        <div class="progress-bar" role="progressbar"   style="width: {{ $progress }}%;" 
                                        aria-valuenow="{{ $progress }}"  aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div>{{ $progress }}%</div>
                                </div>
                                @php
                                    $id = base64_encode($survey->id);
                                    $view = route('Survey.view',$id);
                                @endphp     
                                <div class="d-flex align-items-center">
                                    <a target="_blank" href="{{ $view}}" class="btn-tableIcon btnIcon-skyblue"><i
                                            class="fa-regular fa-eye"></i></a>
                                    <a href="javascript:void(0)" data-id="{{$id}}" class="SendNotifcation btn-tableIcon btnIcon-yellow"><i
                                            class="fa-regular fa-bell"></i></a>
                                    {{-- <a href="#" class="btn-tableIcon btnIcon-blue"><i class="fa-regular fa-pen"></i></a> --}}
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                  

                </div>
            </div>
            @php
                $firstSurvey = isset($ParticipationRate[0]) ? $ParticipationRate[0] : '';
                $participationRate = $firstSurvey ? $firstSurvey->participation_rate : 0; // Default to 0 if no data
            @endphp

            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-participationRate">
                    <div class="card-title mb-md-4">
                        <h3>Participation Rate</h3>
                    </div>
                    <div class="progressOneCenText-block mb-0">
                        <div class="progress-container blue" 
                            data-progress="{{ $participationRate }}" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            title="Participation Rate {{ $participationRate }}%">
                            <svg class="progress-circle" viewBox="0 0 120 120">
                                <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                <circle class="progress" cx="60" cy="60" r="54"></circle>
                            </svg>
                        </div>
                        <div class="text">
                            <h5>{{ $participationRate }}%</h5>
                            <p>PARTICIPATION</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-surveysDeadline" id="card-surveysDeadline">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Surveys Nearing Deadline </h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('Survey.Getneartodeadlinesurvey')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">

                        @if($NearingDeadline->isNotEmpty())
                            @foreach ($NearingDeadline as $n)
                            @php
                                $progress = ($n->total_count > 0) ? round(($n->completed_count / $n->total_count) * 100) : 0;
                            @endphp
                            <div class="leaveUser-block">
                                <div>
                                    <div class="date"><i class="fa-regular fa-calendar"></i>{{ $n->startDate }} - <span
                                            class="text-danger">{{ $n->endDate }}</span>
                                    </div>
                                    <h6>{{ $n->title }}</h6>
                                    <span>{{ $progress }}%</span>
                                    <a href="javascript:void(0)" id="PendingParticipants" class="a-link " data-id="{{ $n->Newid }}">View Pending Participants</a>
                                </div>
                            </div>
                            @endforeach
                        @endif
                        
                    </div>
                </div>
            </div>
            <div class="col-xl-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class=" card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Recent Survey Results</h3>
                            </div>
                            <div class="col-auto">
                                <a href="" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-lableNew table-recentSurvey w-100">
                            <tr>
                                <th>Survey Name</th>
                                <th>No. of participants</th>
                                {{-- <th>Positive</th>
                                <th>Negative</th> --}}
                                <th>Action</th>
                            </tr>

                        @if($RecentSurveyResults->isNotEmpty())
                           @foreach ($RecentSurveyResults as $r)
                                <tr>
                                    <td>{{ ucfirst($r->title) }}</td>
                                    <td>{{ $r->count }}</td>
                                    <td><a href="{{ route('Survey.GetSurveyResults',  base64_encode($r->id)) }}" class="a-linkTheme">View Details</a></td>
                                </tr>
                            @endforeach
                          @endif
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Survey-wise Participation Rates</h3>
                    </div>
                    <canvas id="myAttendance" width="363" height="298"></canvas>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Department-wise Participation</h3>
                    </div>
                    <div class="departmentPart-chart mb-3">
                        <canvas id="myDoughnutChart"></canvas>
                    </div>
                    <div class="row g-2 justify-content-center ">

                        @if($departmentWise->isNotEmpty())
                            @foreach($departmentWise as  $d)
                                
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span style="background-color: {{ $d->color }}"></span>{{ $d->Department_name }}</span>
                                </div>
                            </div>
                            @endforeach
                        @endif
                   
                    </div>
                </div>
            </div>
            <div class="col-xl-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-comParticipation h-auto" id="card-comParticipation">
                    <div class="card-title mb-md-3">
                        <h3>Comparison Of Participation In Different Types Of Surveys</h3>
                    </div>
                    <div class="row g-md-4 g-2 align-items-center">
                        <div class="col-xxl-9 col-xl-12 col-md-9"> <canvas id="myStackedBarChart" width="544"
                                height="326"></canvas></div>
                        <div class="col-xxl-3 col-xl-auto col-lg-2 col-md-3 offset-lg-1 offset-xl-0 ">
                            <div class="row g-2 doughnut-labelTop">
                                @if($SurveyComparison->isNotEmpty())
                                    @foreach ($SurveyComparison as $com)
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span style="background-color: {{ $com->color }}"></span>{{ $com->title }}</span>

                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class=" card " id="card-draftedSurveys">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center  g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Draft Surveys</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('Survey.DarftSurvey')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        @if($SaveAsDraft->isNotEmpty())
                            @foreach ($SaveAsDraft as $s)
                            <div class="leaveUser-block">
                                <div>
                                    <h6>{{ $s->Surevey_title }}</h6>
                                    <p>From :- {{ $s->Start_date }}  To :- {{ $s->End_date }}</p>
                                    <div>
                                        <a target="_blank" href="{{ $s->route }}" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                       @endif
                    </div>
                </div>
            </div>
            <!-- <div class="col-xl-3 col-sm-6">
                <div class="card" id="card-wiInsightsSurvey">
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
            </div> -->
        </div>
    </div>
</div>
<div class="modal fade show" id="Surveyparticipant" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true" role="dialog" >
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">


                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Pending  Participant  in survey</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="employee-name-content">
                        <div class="row g-3 AppendinRow">

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts') <script type="text/javascript">
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
    });

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
        equalizeHeights('card-surveyStatus', ['card-surveysDeadline']);
        equalizeHeights('card-comParticipation', ['card-draftedSurveys', 'card-wiInsightsSurvey']);
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
 
   // Fetch data from Laravel (passed from controller)
   var surveyData = @json($SurveyComparison);
   function getLastThreeMonths() {
        let months = [];
        let date = new Date();
        
        for (let i = 2; i >= 0; i--) {
            let d = new Date(date.getFullYear(), date.getMonth() - i, 1);
            let monthYear = d.toLocaleString('default', { month: 'short' }) + " " + d.getFullYear();
            months.push(monthYear);
        }
        
        return months;
    }

// Dynamically generate last two months and current month labels
var labels = getLastThreeMonths();


var groupedData = {};
var surveyColors = {}; // Store survey type and corresponding color

// Group data by survey type
surveyData.forEach(s => {
    if (!groupedData[s.survey_type]) {
        groupedData[s.survey_type] = Array(labels.length).fill(0);
        surveyColors[s.survey_type] = s.color; // Store assigned color
    }
    let index = labels.indexOf(s.survey_month);
    if (index !== -1) {
        groupedData[s.survey_type][index] = s.completed_count; // Use completed_count for stacking
    }
});

// Create datasets dynamically
var datasets = Object.keys(groupedData).map(type => ({
    label: type, // Use the survey title
    data: groupedData[type],
    backgroundColor: surveyColors[type], // Use the assigned color
    borderColor: '#fff',
    borderWidth: 2,
    borderRadius: 10,
}));

// Create Chart
var ctx = document.getElementById('myStackedBarChart').getContext('2d');
var myStackedBarChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels, // Last two months and current month dynamically
        datasets: datasets // Dynamic datasets with assigned colors
    },
    options: {
        plugins: {
            legend: {
                display: true // Show legend with correct titles
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        let index = tooltipItem.datasetIndex;
                        return datasets[index].label + ": " + tooltipItem.raw; // Show correct survey type in tooltip
                    }
                }
            }
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false }
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



    var departmentLabels = {!! json_encode($departmentWise->pluck('Department_name')) !!};
    var departmentData = {!! json_encode($departmentWise->pluck('completed_count')) !!};
    var departmentColors = {!! json_encode($departmentWise->pluck('color')) !!}; // Random colors

    var ctz = document.getElementById('myDoughnutChart').getContext('2d');

    const doughnutLabelsInsideN = {
        id: 'doughnutLabelsInsideN',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce((acc, val) => acc + val, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                        var position = element.tooltipPosition();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold 14px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percentage, position.x, position.y); // Show percentage inside
                    });
                }
            });
        }
    };


    var myDoughnutChart = new Chart(ctz, {
        type: 'doughnut',
        data: {
            labels: departmentLabels, // Department names (for hover only)
            datasets: [{
                data: departmentData, // Department-wise completion counts
                backgroundColor:departmentColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false // Hide legend labels
                },
                tooltip: {
                    enabled: true, // Show label names on hover
                    callbacks: {
                        label: function (tooltipItem) {
                            var label = departmentLabels[tooltipItem.dataIndex]; // Get department name
                            var value = departmentData[tooltipItem.dataIndex];
                            return `${label}: ${value}`;
                        }
                    }
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
        plugins: [doughnutLabelsInsideN] // Attach custom plugin
    });

    var surveyLabels = {!! json_encode($SurveyWiseParticipationRates->pluck('title')) !!}; // Survey titles
    var completedData = {!! json_encode($SurveyWiseParticipationRates->pluck('completed_count')) !!}; // Completed count


    const ctp = document.getElementById('myAttendance').getContext('2d');
    const myAttendance = new Chart(ctp, {
        type: 'bar',
        data: {
            labels: surveyLabels,
            datasets: [
                {
                    label: surveyLabels,
                    data: completedData,
                    backgroundColor: '#014653',
                    borderColor: '#014653',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 25
                },
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        left: 0,
                        right: 0
                    }
                },
                tooltip: {
                    enabled: true, // Enable tooltips
                    callbacks: {
                        label: function (tooltipItem) {
                            // const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                            return ` ${value}`; // Custom tooltip format
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Show the x-axis border
                    }
                },
                y: {
                    beginAtZero: true, // Do not start y-axis at zero
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    }, ticks: {
                        stepSize: 100,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });

    
    var participationRate = {!! json_encode($ParticipationRate->pluck('participation_rate')) !!}; // Participation rate %
    document.addEventListener("DOMContentLoaded", function() {
        var progressContainer = document.querySelector(".progress-container");
        var participationValue = progressContainer.getAttribute("data-progress") || 0;
        var progressCircle = document.querySelector(".progress");
        var radius = 54;
        var circumference = 2 * Math.PI * radius;
        var progress = participationValue / 100;
        var offset = circumference * (1 - progress);

        progressCircle.style.strokeDasharray = circumference;
        progressCircle.style.strokeDashoffset = offset;
    });
    
    $(document).on("click",".SendNotifcation",function(){
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('Survey.notifyToParticipants') }}", // Update with actual route
            type: "post",
            data: {"id":id,"_token":"{{ csrf_token() }}"},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } 
                else
                {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                toastr.error("An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
        
        
    });


    $(document).on("click","#PendingParticipants",function(){
    var id = $(this).data('id');
    $("#Surveyparticipant").modal('show');
    $('.AppendinRow').html('No Record Found.     ');
        $.ajax({
            url: "{{ route('Survey.getPendingParticipants') }}", // Update with actual route
            type: "get",
            data: {"id":id,"_token":"{{ csrf_token() }}"},
            success: function (response) {
              
                    $('.AppendinRow').html(response);

               
            },
            error: function (xhr) {
                toastr.error("An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });

</script>
@endsection

