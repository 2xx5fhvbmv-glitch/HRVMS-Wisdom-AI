@extends('resorts.layouts.app')
@section('page_tab_title' ,"Performance Dashboard")

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
                        <span>Performance</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-xl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>Past 90 Days</option>
                        <option value="1">bbb</option>
                    </select>
                </div>
                <!-- <div class="col-auto"><a href="#" class="btn btn-theme">Notify HOD</a></div> -->
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Employee</p>
                            <strong>{{$Employee_count ?? 0 }}</strong>
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
                            <p class="mb-0  fw-500">Appraisal Pending</p>
                            <strong>12 <small>/15</small></strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <!-- <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Employees in PIP </p>
                            <strong>64</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div> -->
            <!-- <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Employees in PDP</p>
                            <strong>&nbsp;</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div> -->
            <div class="col-xl-6">
                <div class="card card-serviceCharges">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Department Wise Performance</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select" aria-label="Default select example">
                                        <option selected="">Select Division</option>
                                        <option value="1">AAA</option>
                                        <option value="2">AAA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 align-items-center">
                        <div class="col-md-6">
                            <div class="chart-department"> <canvas id="myDoughnutChart"></canvas></div>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-2 ">
                                <div class="col-6">
                                    <div class="doughnut-label">
                                        <span class="bg-theme"></span>Management <br>40%
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblue"></span>Human Resources <br>15%
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblueLightNew"></span>Accounting <br>30%
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="doughnut-label">
                                        <span class="bg-themeGray"></span>Purchasing And Receiving <br>10%
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="doughnut-label">
                                        <span class="bg-themeWarning"></span>General Support <br>10%
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblueLight"></span>Security <br>30%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card ">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-2 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Appraisal Pending Departments</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table data-Table w-100">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Appraisal Time</th>
                                <th>Employees</th>
                                <th>Last Appraisal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                <td>6 month</td>
                                <td>15</td>
                                <td>1 oct 2019</td>
                                <td><span class="badge badge-themeYellow">Pending</span></td>
                            </tr>
                            <tr>
                                <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                <td>6 month</td>
                                <td>10</td>
                                <td>5 July 2020</td>
                                <td><span class="badge badge-themeSuccess">Done</span></td>
                            </tr>
                            <tr>
                                <td>Front Office <span class="badge badge-themeLight">F-845</span></td>
                                <td>6 month</td>
                                <td>50</td>
                                <td>20 Jun 2018</td>
                                <td><span class="badge badge-themeSuccess">Done</span></td>
                            </tr>
                            <tr>
                                <td>Housekeeping <span class="badge badge-themeLight">H-451</span></td>
                                <td>6 month</td>
                                <td>26</td>
                                <td>3 Aug 2022</td>
                                <td><span class="badge badge-themeSuccess">Done</span></td>
                            </tr>
                            <tr>
                                <td>Management <span class="badge badge-themeLight">M-515</span></td>
                                <td>6 month</td>
                                <td>18</td>
                                <td>7 Jan 2023</td>
                                <td><span class="badge badge-themeSuccess">Done</span></td>
                            </tr>
                            <tr>
                                <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                <td>6 month</td>
                                <td>15</td>
                                <td>1 Mar 2022</td>
                                <td><span class="badge badge-themeSuccess">Done</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-wiINsightPayroll card-wiINsightperformance">
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
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting industry ipsum.
                                </P>
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
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting industry ipsum.
                                </P>
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
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting industry ipsum.
                                </P>
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
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting industry ipsum.
                                </P>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- <div class="col-xl-3 col-md-6">
                <div class="card card-qualityMetrics">
                    <div class=" card-title">
                        <h3>Quality Metrics</h3>
                    </div>
                    <div class="qualityMetrics-block">
                        <div>
                            <p>Guest Satisfaction</p>
                            <span class="text-successTheme">Target Achieved</span>
                        </div>
                        <div>
                            <span>4.8/5</span>
                            <span>Target: 4.5/5</span>
                        </div>
                        <div class="progress progress-custom progress-themeGreen">
                            <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="qualityMetrics-block">
                        <div>
                            <p>Service Accuracy</p>
                           <span class="text-successTheme">Target Achieved</span> -
                        </div>
                        <div>
                            <span>87%</span>
                            <span>Target: 95%</span>
                        </div>
                        <div class="progress progress-custom progress-themeWarning">
                            <div class="progress-bar" role="progressbar" style="width: 87%" aria-valuenow="87"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="qualityMetrics-block">
                        <div>
                            <p>Lorem Ipsum</p>
                             <span class="text-successTheme">Target Achieved</span>
                        </div>
                        <div>
                            <span>20%</span>
                            <span>Target: 95%</span>
                        </div>
                        <div class="progress progress-custom progress-themeRed">
                            <div class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="qualityMetrics-block">
                        <div>
                            <p>Lorem Ipsum</p>
                            <span class="text-successTheme">Target Achieved</span>
                        </div>
                        <div>
                            <span>4.8/5</span>
                            <span>Target: 4.5/5</span>
                        </div>
                        <div class="progress progress-custom progress-themeGreen">
                            <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div> 
            <div class="col-xl-3 col-md-6">
                <div class="card cart-kpiAlert">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">KPI Alerts</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select" aria-label="Default select example">
                                        <option selected="">Select Department</option>
                                        <option value="1">AAA</option>
                                        <option value="2">AAA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-auto pe-1">
                        <div class="alert-custom alert-themeDanger" role="alert">
                            <i class="fa-regular fa-triangle-exclamation"></i>Quality score below target for
                            Engineering
                            team
                        </div>
                        <div class="alert-custom alert-themeSuccess" role="alert">
                            <i class="fa-regular fa-triangle-exclamation"></i>Sales team exceeded monthly targets by
                            15%
                            team
                        </div>
                        <div class="alert-custom alert-themePrimary" role="alert">
                            <i class="fa-regular fa-triangle-exclamation"></i>Support response time showing
                            improving
                            trend
                            team
                        </div>
                        <div class="alert-custom alert-themeDanger" role="alert">
                            <i class="fa-regular fa-triangle-exclamation"></i>Quality score below target for
                            Engineering
                            team
                        </div>
                    </div>
                </div>
            </div>-->

            <div class="col-12 @if(App\Helpers\Common::checkRouteWisePermission('Performance.cycle',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-PerformanceCyc">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Performance Cycles</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="PerformanceCyc-main">
                        <div class="PerformanceCyc-block bg-themeGrayLight">
                            <div class="PerformanceCyc-head">
                                <div class="">
                                    <h5>Lorem ipsum is simply dummy text <span
                                            class="badge badge-success">Ongoing</span>
                                    </h5>
                                    <p><img src="assets/images/users.svg" alt="icon"> 142 Employees</p>
                                </div>
                                <div>
                                    <a href="#" class="btn btn-themeBlue btn-xsmall">Duplicate</a>
                                    <a href="#" class="btn-tableIcon btnIcon-danger"><i
                                            class="fa-regular fa-trash-can"></i></a>
                                </div>
                            </div>
                            <div class="row gx-md-4 g-3">
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Manager Reviews</p>
                                        <h3>46</h3>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Self Reviews</p>
                                        <h3>142</h3>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Peer Reviews</p>
                                        <h3>79</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="PerformanceCyc-block bg-themeGrayLight">
                            <div class="PerformanceCyc-head">
                                <div class="">
                                    <h5>Lorem ipsum is simply dummy text <span
                                            class="badge badge-success">Ongoing</span>
                                    </h5>
                                    <p><img src="assets/images/users.svg" alt="icon"> 142 Employees</p>
                                </div>
                                <div>
                                    <a href="#" class="btn btn-themeBlue btn-xsmall">Duplicate</a>
                                    <a href="#" class="btn-tableIcon btnIcon-danger"><i
                                            class="fa-regular fa-trash-can"></i></a>
                                </div>
                            </div>
                            <div class="row gx-md-4 g-3">
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Manager Reviews</p>
                                        <h3>46</h3>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Self Reviews</p>
                                        <h3>142</h3>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <div class="d-flex bg-white">
                                        <p>Peer Reviews</p>
                                        <h3>79</h3>
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
<div class="modal fade" id="assign-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Payroll Components</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="basic_salary" class="form-label">Total Basic Salary</label>
                    <input type="text" id="basic_salary" class="form-control" value="54,415.20">
                </div>
                <div class="mb-3">
                    <label for="service_charge" class="form-label">Service Charge Values</label>
                    <input type="text" id="service_charge" class="form-control" value="145.00">
                </div>
                <div class="mb-3">
                    <label for="normal_ot" class="form-label">Normal OT</label>
                    <input type="text" id="normal_ot" class="form-control" value="$1,110.00 (120 Hrs)">
                </div>
                <div>
                    <label for="holiday_ot" class="form-label">Holiday OT</label>
                    <input type="text" id="holiday_ot" class="form-control" value="$142.00 (70Hrs)">
                </div>

            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="module">
    var ctz = document.getElementById('myDoughnutChart').getContext('2d');

    // Custom plugin only registered for this chart
    const doughnutLabelsInsideN = {
        id: 'doughnutLabelsInsideN',
        afterDraw: function (chart) {
            var ctx = chart.ctx; // Corrected
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
                        ctx.font = 'normal 18px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };

    var myDoughnutChart = new Chart(ctz, {
        type: 'doughnut',
        data: {
            labels: ['Management', 'Human Resources', 'Accounting', 'Purchasing And Receiving', 'General Support', 'Security'],
            datasets: [{
                data: [40, 15, 30, 10, 10, 30],
                backgroundColor: ['#014653', '#2EACB3', '#53CAFF', '#333333', '#EFB408', '#8DC9C9'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                doughnutLabelsInsideN: true, // Enable the custom plugin
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
            // hoverOffset: 30
        },
        plugins: [doughnutLabelsInsideN] // Attach the plugin to this chart only
    });
</script>
@endsection

