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
                    <form method="GET" action="{{ url()->current() }}" id="yearFilterForm">
                        <select class="form-select select2t-none" id="select-year" name="year"
                                onchange="document.getElementById('yearFilterForm').submit();">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ (int)$selectedYear === (int)$year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </form>
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
                            <strong>{{ $Employee_count ?? 0 }}</strong>
                        </div>
                        <a href="{{ route('resort.employeelist') }}">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Appraisal Pending</p>
                            <strong>{{ $appraisal_pending ?? 0 }} <small>/{{ $appraisal_total ?? 0 }}</small></strong>
                        </div>
                        <a href="{{ route('Performance.cycle') }}">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Employees in PIP</p>
                            <strong>{{ $pip_count ?? 0 }}</strong>
                        </div>
                        <a href="{{ route('Performance.pip.index') }}">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Employees in PDP</p>
                            <strong>{{ $pdp_count ?? 0 }}</strong>
                        </div>
                        <a href="{{ route('Performance.pdp.index') }}">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0 fw-500">Approved Monthly Check-Ins</p>
                            <strong>{{ $approved_checkins_count ?? 0 }}</strong>
                        </div>
                        <a href="{{ route('Performance.MonltyCheckIn.history') }}">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-serviceCharges">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Department Wise Distribution</h3>
                            </div>
                        </div>
                    </div>
                    @php
                        $deptColors = ['#014653', '#2EACB3', '#53CAFF', '#333333', '#EFB408', '#8DC9C9', '#d9534f', '#5cb85c', '#f0ad4e', '#5bc0de'];
                        $deptTotal = $department_data->sum('count');
                    @endphp
                    @if($deptTotal > 0)
                        <div class="row g-4 align-items-center">
                            <div class="col-md-6">
                                <div class="chart-department"> <canvas id="myDoughnutChart"></canvas></div>
                            </div>
                            <div class="col-md-6">
                                <div class="row g-2 ">
                                    @foreach($department_data as $idx => $dept)
                                        @php $pct = round(($dept->count / $deptTotal) * 100); @endphp
                                        <div class="col-6">
                                            <div class="doughnut-label">
                                                <span style="background:{{ $deptColors[$idx % count($deptColors)] }};"></span>{{ $dept->name }} <br>{{ $pct }}%
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fa-regular fa-chart-bar" style="font-size:40px;"></i>
                            <p class="mt-2">No department data available</p>
                        </div>
                    @endif
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
                                <a href="{{ route('Performance.cycle') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table data-Table w-100">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employees</th>
                                <th>Pending</th>
                                <th>Completed</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $departments = \App\Models\ResortDepartment::where('resort_id', Auth::guard('resort-admin')->user()->resort_id)->get();
                                $activeCycleIds = \DB::table('performance_cycles')
                                    ->where('resort_id', Auth::guard('resort-admin')->user()->resort_id)
                                    ->where('status', 'OnGoing')
                                    ->pluck('id');
                            @endphp
                            @foreach($departments as $dept)
                                @php
                                    $deptEmpIds = \App\Models\Employee::where('resort_id', Auth::guard('resort-admin')->user()->resort_id)
                                        ->where('Dept_id', $dept->id)
                                        ->where('status', 'Active')
                                        ->pluck('id');
                                    $totalInCycle = \DB::table('performa_child_cycles')
                                        ->whereIn('Parent_cycle_id', $activeCycleIds)
                                        ->whereIn('Emp_main_id', $deptEmpIds)
                                        ->count();
                                    $pendingCount = \DB::table('performa_child_cycles')
                                        ->whereIn('Parent_cycle_id', $activeCycleIds)
                                        ->whereIn('Emp_main_id', $deptEmpIds)
                                        ->whereNull('Manager_review_date')
                                        ->count();
                                    $completedCount = $totalInCycle - $pendingCount;
                                @endphp
                                <tr>
                                    <td>{{ $dept->name }} <span class="badge badge-themeLight">{{ $dept->code }}</span></td>
                                    <td>{{ $deptEmpIds->count() }}</td>
                                    <td>{{ $pendingCount }}</td>
                                    <td>{{ $completedCount }}</td>
                                    <td>
                                        @if($totalInCycle == 0)
                                            <span class="badge badge-themeLight">No Cycle</span>
                                        @elseif($pendingCount == 0)
                                            <span class="badge badge-themeSuccess">Done</span>
                                        @else
                                            <span class="badge badge-themeYellow">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
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
                                <a href="{{ route('Performance.cycle') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="PerformanceCyc-main">
                        @forelse($performance_cycles as $cycle)
                            @php
                                $statusBadge = 'badge-themeWarning';
                                if ($cycle->status === 'OnGoing') $statusBadge = 'badge-success';
                                elseif ($cycle->status === 'Close') $statusBadge = 'badge-themeLight';
                            @endphp
                            <div class="PerformanceCyc-block bg-themeGrayLight">
                                <div class="PerformanceCyc-head">
                                    <div class="">
                                        <h5>{{ $cycle->Cycle_Name }}
                                            <span class="badge {{ $statusBadge }}">{{ $cycle->status }}</span>
                                        </h5>
                                        <p><i class="fa-regular fa-user"></i> {{ $cycle->total_employees }} {{ $cycle->total_employees == 1 ? 'Employee' : 'Employees' }}</p>
                                        <p class="mb-0" style="font-size:12px;color:#666;">
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($cycle->Start_Date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($cycle->End_Date)->format('d M Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="row gx-md-4 g-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="d-flex bg-white">
                                            <p>Self Reviews</p>
                                            <h3>{{ $cycle->self_completed }}<small class="text-muted" style="font-size:12px;">/{{ $cycle->total_employees }}</small></h3>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="d-flex bg-white">
                                            <p>Manager Reviews</p>
                                            <h3>{{ $cycle->manager_completed }}<small class="text-muted" style="font-size:12px;">/{{ $cycle->total_employees }}</small></h3>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="d-flex bg-white">
                                            <p>Pending</p>
                                            <h3>{{ $cycle->self_pending + $cycle->manager_pending }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="fa-regular fa-calendar-xmark" style="font-size:40px;"></i>
                                <p class="mt-2">No performance cycles created yet</p>
                                <a href="{{ route('Performance.create') }}" class="btn btn-themeSkyblue btn-sm">
                                    <i class="fa-solid fa-plus me-1"></i> Create Your First Cycle
                                </a>
                            </div>
                        @endforelse
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
    var doughnutCanvas = document.getElementById('myDoughnutChart');
    if (doughnutCanvas) {
    var ctz = doughnutCanvas.getContext('2d');

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

    var deptLabels = @json($department_data->pluck('name'));
    var deptCounts = @json($department_data->pluck('count'));
    var deptBgColors = ['#014653', '#2EACB3', '#53CAFF', '#333333', '#EFB408', '#8DC9C9', '#d9534f', '#5cb85c', '#f0ad4e', '#5bc0de'];

    var myDoughnutChart = new Chart(ctz, {
        type: 'doughnut',
        data: {
            labels: deptLabels,
            datasets: [{
                data: deptCounts,
                backgroundColor: deptLabels.map(function(_, i) { return deptBgColors[i % deptBgColors.length]; }),
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
    }
</script>
@endsection

