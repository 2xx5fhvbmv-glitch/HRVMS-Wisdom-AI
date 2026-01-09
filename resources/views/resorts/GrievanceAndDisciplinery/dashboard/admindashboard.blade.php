@extends('resorts.layouts.app')
@section('page_tab_title' ,"People RelationDashboard")

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
                        <span>People Relation</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                        <option value="1">bbb</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Open Cases</p>
                            <strong>48</strong>
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
                            <p class="mb-0  fw-500">Pending Cases</p>
                            <strong>26</strong>
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
                            <p class="mb-0  fw-500">Closed Cases</p>
                            <strong>21</strong>
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
                            <p class="mb-0  fw-500">Expired Offense</p>
                            <strong>5</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card card-resolutionRate">
                    <div class="card-title mb-lg-4">
                        <h3>Resolution Rate</h3>
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
                            <h5>95%</h5>
                            <p>GRIEVANCES RESOLVED</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <p>Average Resolution Time:</p>
                        <p>24 HRS</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="row g-3 g-xxl-4">
                    <div class="col-12">
                        <div class="card peopleRelation-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <p>Delegated Cases:</p>
                                <strong>32</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card peopleRelation-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <p>Pending Approvals:</p>
                                <strong>14</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card card-confiCases">
                            <div class="card-title mb-lg-3">
                                <h3>Confidential Cases:</h3>
                            </div>
                            <div class="d-flex">
                                <div class="progress progress-custom progress-themeskyblue">
                                    <div class="progress-bar" role="progressbar" style="width: 80%"
                                        aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">80%</div>
                                </div>
                                <span>Resolved</span>
                            </div>
                            <div class="d-flex mb-lg-4 mb-md-3">
                                <div class="progress progress-custom progress-themeskyblue">
                                    <div class="progress-bar" role="progressbar" style="width: 20%"
                                        aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">20%</div>
                                </div>
                                <span>Unresolved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-appealsSection">
                    <div class="card-title">
                        <h3>Appeals Section</h3>
                    </div>
                    <p>Total Appeals Submitted: 26 | Average Resolution Time: 24 Hrs</p>
                    <div class="row g-3 g-xxl-4">
                        <div class="col-md-6  col-sm-7">
                            <div class="bg-themeGrayLight">
                                <h6>Appeals by category</h6>
                                <canvas id="appealsByCategory" width="349" height="199"></canvas>
                                <!-- <canvas id="appealsByCategory"></canvas> -->
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-5">
                            <div class="bg-themeGrayLight text-center text-md-start">
                                <h6>Pending vs. resolved Hearings</h6>
                                <div class="row gx-2 gy-0 align-items-center">
                                    <div class=" col-xxl-8 col-xl-12 col-md-8">
                                        <div class="payrollDistr-chart">
                                            <canvas id="myDoughnutChartPeopleRelation"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-xl-12 col-md-4">
                                        <div class="row g-2 justify-content-center">
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-theme"></span>Pending <br>142
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeLightBlue"></span>Resolved
                                                    <br>35
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 ">
                <div class="card">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Grievances</h3>
                            </div>
                            <div class="col-auto"><a href="#" class="a-link">View All</a> </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <p class="mb-0">Attendance issues</p>
                        <p>50</p>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <p class="mb-0">Performance Problems</p>
                        <p>12</p>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <p class="mb-0">Misconduct</p>
                        <p>03</p>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <p class="mb-0">Policy Violations</p>
                        <p>17</p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p class="mb-0">Safety/Compliance Issues</p>
                        <p>12</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 order-sm-2 order-xl-0">
                <div class="card">
                    <div class="card-title mb-md-3">
                        <h3>Case Timelines</h3>
                    </div>
                    <div class="caseTimelines-block">
                        <p>Safety/Compliance Issues</p>
                        <div class="progress progress-custom progress-customDot progress-themeGreen">
                            <div class="progress-bar" role="progressbar" style="width: 50%" aria-valuenow="50"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div>
                            <div>
                                <p>Filled date:</p><span>28/10/2024</span>
                            </div>
                            <div>
                                <p>Deadline:</p><span>25/11/2024</span>
                            </div>
                        </div>
                    </div>
                    <div class="caseTimelines-block">
                        <p>Performance Problems</p>
                        <div class="progress progress-custom progress-customDot progress-themeWarning">
                            <div class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div>
                            <div>
                                <p>Filled date:</p><span>28/10/2024</span>
                            </div>
                            <div>
                                <p>Deadline:</p><span>25/11/2024</span>
                            </div>
                        </div>
                    </div>
                    <div class="caseTimelines-block">
                        <p>Attendance Issues</p>
                        <div class="progress progress-custom progress-customDot progress-themeRed">
                            <div class="progress-bar" role="progressbar" style="width: 80%" aria-valuenow="80"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div>
                            <div>
                                <p>Filled date:</p><span>28/10/2024</span>
                            </div>
                            <div>
                                <p>Deadline:</p><span>25/11/2024</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 order-sm-1 order-xl-0">
                <div class="row g-3 g-xxl-4">
                    <div class="col-12">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0  fw-600">Retaliation Reports Filed</p>
                                <strong>32</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card card-reportsResolved">
                            <div class="card-title ">
                                <h3>Reports Marked As Resolved</h3>
                            </div>
                            <div class="progress-block">
                                <div class="progress-container blue " data-progress="70" data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" title="Male Staff Occupied 90%">
                                    <svg class="progress-circle" viewBox="0 0 120 120">
                                        <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                        <circle class="progress" cx="60" cy="60" r="54"></circle>
                                    </svg>
                                </div>
                                <div class="text">
                                    <h5>70%</h5>
                                    <p>GRIEVANCES RESOLVED</p>
                                </div>
                            </div>
                            <div class="d-flex">
                                <p>Average Resolution Time:</p>
                                <p>24 HRS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 order-sm-3 order-xl-0">
                <div class="card h-auto" id="card-breakdownCases">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Breakdown Of Cases</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select" aria-label="Default select example">
                                        <option selected="">By Category</option>
                                        <option value="1">AAA</option>
                                        <option value="2">AAA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <canvas id="breakdownCases" width="797" height="293"></canvas> -->
                    <canvas id="breakdownCases"></canvas>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 order-sm-4 order-xl-0">
                <div class="card card-offenseNearingExpiry" id="card-offenseNearingExpiry">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Offense Nearing To Expiry</h3>
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
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                    <a href="#" class="a-link">Extend</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">

                            <div>
                                <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                    <a href="#" class="a-link">Extend</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">

                            <div>
                                <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                    <a href="#" class="a-link">Extend</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">

                            <div>
                                <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme me-1 me-md-2">Close</a>
                                    <a href="#" class="a-link">Extend</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 order-sm-5 order-xl-0">
                <div class="card card-wiINsightPayroll card-wiINsightperforHod" id="card-wiINsight">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">WI Insight's</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>typesetting industry Lorem typesetting industry ipsum.</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the
                                    typesetting industry
                                    Lorem typesetting industry ipsum.
                                </p>
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

    //    equal heigth js 
    function equalizeHeights() {
        // Get the elements
        const block1 = document.getElementById('card-breakdownCases');
        const block2_1 = document.getElementById('card-offenseNearingExpiry');
        const block2_2 = document.getElementById('card-wiINsight');

        // Check if elements exist
        if (block1 && block2_1 && block2_2) {
            // Get the height of block1
            const block1Height = block1.offsetHeight;

            // Set the height of block2 elements to match block1's height
            block2_1.style.height = block1Height + 'px';
            block2_2.style.height = block1Height + 'px';
        }
    }

    window.onload = equalizeHeights; // Initial height adjustment

    // Adjust heights on window resize
    window.onresize = equalizeHeights;


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
    });
</script>
<script type="module">

    const cty = document.getElementById('appealsByCategory').getContext('2d');
    const appealsByCategory = new Chart(cty, {
        type: 'bar',
        data: {
            labels: ['Category 1', 'Category 2', 'Category 3', 'Category 4',],
            datasets: [
                {
                    // label: 'Preplannned OT',
                    data: [12, 17, 8, 14],
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
                            return ` $${value}`; // Custom tooltip format
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
                        stepSize: 5,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });


    var ctx = document.getElementById('myDoughnutChartPeopleRelation').getContext('2d');

    // Custom plugin only registered for this chart
    const doughnutLabelsInside = {
        id: 'doughnutLabelsInside',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var label = chart.data.labels[index];

                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = ((dataValue / total) * 100) + '%';

                        var position = element.tooltipPosition();

                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold 16px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };

    var myDoughnutChartPeopleRelation = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Resolved'],
            datasets: [{
                data: [70, 30],
                backgroundColor: ['#014653', '#2EACB3'], borderWidth: 0 // Removes the border
            }]
        },
        options: {
            responsive: true,
            plugins: {
                doughnutLabelsInside: true, // Enable the custom plugin
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
            },
            hover: {
                onHover: function (event, activeElements) {
                    if (activeElements.length > 0) {
                        const chartSegment = activeElements[0];
                        chartSegment.element.options.hoverOffset = 100;
                    } else {
                        myDoughnutChartPeopleRelation.data.datasets[0].hoverOffset = 10;
                    }
                    myDoughnutChartPeopleRelation.update();
                }
            },
            hoverOffset: 30
        },
        plugins: [doughnutLabelsInside] // Attach the plugin to this chart only
    });

    const ctz = document.getElementById('breakdownCases').getContext('2d');
    const breakdownCases = new Chart(ctz, {
        type: 'bar',
        data: {
            labels: ['Category 1', 'Category 2', 'Category 3', 'Category 4', 'Category 5', 'Category 6', 'Category 7',],
            datasets: [
                {
                    // label: 'Preplannned OT',
                    data: [80, 70, 90, 76, 96, 62, 80, 90, 74, 80, 90, 60],
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
                            return ` $${value}`; // Custom tooltip format
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
                        stepSize: 20,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });
</script>
@endsection

