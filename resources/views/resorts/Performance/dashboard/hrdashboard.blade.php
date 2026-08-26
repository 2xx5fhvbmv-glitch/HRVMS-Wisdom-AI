@extends('resorts.layouts.app')
@section('page_tab_title' ,"Performance Dashboard")

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    /* WAI Insights — same gradient-header treatment as the other modules'
       WAI Insights cards (Time and Attendance / Payroll / Talent Acquisition
       / Leave). These are 3 narrative performance insights (title +
       descriptive body + optional recommendation), not pass/fail counts,
       so there's no hero/count figure — icon is amber when a recommendation
       is present, teal tick otherwise. Card height matches its row neighbour
       (Appraisal Pending Departments), which opts out of the row's default
       stretch via .perf-appraisal-table-col so it doesn't get force-stretched
       to this taller card's height. */
    #card-wiINsightPerformance {
        height: 450px !important;
        max-height: 450px !important;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
        border-radius: 16px;
    }
    #card-wiINsightPerformance .leaveUser-main { overflow-y: auto; flex: 1 1 auto; min-height: 0; }

    .wai-narrative .wai-head { position: relative; overflow: hidden; padding: 17px 18px; flex-shrink: 0; }
    .wai-narrative .wai-head::before {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, #014653 0%, #0e8a9e 40%, #7fa61e 70%, #e0ff02 100%);
    }
    .wai-narrative .wai-head::after {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, rgba(1,40,48,.35), transparent 55%);
    }
    .wai-narrative .wai-head h2 { position: relative; color: #fff; font-size: 15px; font-weight: 800; margin: 0; }
    .wai-narrative .wai-head-meta { position: relative; margin-top: 4px; font-size: 11.5px; color: rgba(255,255,255,.75); display: flex; gap: 6px; }
    .wai-narrative .wai-head-meta a { color: #fff; font-weight: 600; text-decoration: underline; }

    .wai-narrative-body { padding: 16px; }
    .wai-narrative .wai-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px 2px; border-bottom: 1px solid #F2F6F6; }
    .wai-narrative .wai-row:last-child { border-bottom: none; }
    .wai-narrative .wai-row-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; margin-top: 2px; }
    .wai-narrative .wai-row-icon.is-ok { background: #E9F7F0; color: #1F9D6B; }
    .wai-narrative .wai-row-icon.is-flagged { background: #FBF0DC; color: #D98A00; }
    .wai-narrative .wai-row-body { flex: 1 1 auto; min-width: 0; }
    .wai-narrative .wai-row-body h6 { margin: 0 0 4px; font-size: 13.5px; font-weight: 700; color: #14232A; }
    .wai-narrative .wai-row-text { margin: 0 0 4px; font-size: 12.5px; color: #5D6F75; line-height: 1.5; }
    .wai-narrative .wai-row-link { display: inline-block; margin-top: 2px; font-size: 12px; font-weight: 600; color: #014653; }
</style>
@include('resorts.Performance._performance_buttons_v2_styles')
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
            <div class="col-lg-3 col-sm-6 perf-checkins-col">
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
            <div class="col-lg-6 perf-appraisal-table-col">
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
                            @forelse($appraisalDepartments ?? [] as $dept)
                                <tr>
                                    <td>{{ $dept->name }} <span class="badge badge-themeLight">{{ $dept->code }}</span></td>
                                    <td>{{ $dept->emp_count }}</td>
                                    <td>{{ $dept->pending_count }}</td>
                                    <td>{{ $dept->completed_count }}</td>
                                    <td>
                                        @if($dept->in_cycle_total == 0)
                                            <span class="badge badge-themeLight">No Cycle</span>
                                        @elseif($dept->pending_count == 0)
                                            <span class="badge badge-themeSuccess">Done</span>
                                        @else
                                            <span class="badge badge-themeYellow">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No departments to display</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                @php
                    $pi = $performanceInsights ?? [];
                    $piMeta = $pi['_meta'] ?? null;
                    $piCards = [
                        ['key' => 'completion', 'fallback' => 'Appraisal Completion Outlook',      'modal' => 'perfInsightCompletionModal'],
                        ['key' => 'risk',       'fallback' => 'Performance Risk & PIP Watch',       'modal' => 'perfInsightRiskModal'],
                        ['key' => 'throughput', 'fallback' => 'Self vs Manager Review Throughput',  'modal' => 'perfInsightThroughputModal'],
                    ];
                @endphp
                <div class="card wai-narrative" id="card-wiINsightPerformance">
                    <div class="wai-head">
                        <h2>WAI Insights</h2>
                        @if($piMeta)
                            <div class="wai-head-meta">
                                <span>Updated {{ $piMeta['generated_at']->diffForHumans() }}</span>
                                @if($piMeta['can_regenerate'])
                                    <a href="?regenerate_insights=1">Regenerate</a>
                                @else
                                    <span title="{{ $piMeta['next_available']->format('d M Y, H:i') }}">&middot; Regenerate {{ $piMeta['next_available']->diffForHumans() }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="leaveUser-main wai-narrative-body">
                        @foreach($piCards as $card)
                            @php $c = $pi[$card['key']] ?? []; $hasRecommendation = !empty($c['recommendation']); @endphp
                            <div class="wai-row">
                                <div class="wai-row-icon {{ $hasRecommendation ? 'is-flagged' : 'is-ok' }}">
                                    <i class="fa-solid {{ $hasRecommendation ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                                </div>
                                <div class="wai-row-body">
                                    <h6>{{ $c['title'] ?? $card['fallback'] }}</h6>
                                    <p class="wai-row-text">{{ $c['body'] ?? '' }}</p>
                                    <div class="lnkrow">
                                        @if($hasRecommendation)
                                            <button type="button" class="lnk-rec"
                                                data-title="{{ $c['title'] ?? $card['fallback'] }}"
                                                data-rec="{{ $c['recommendation'] }}"
                                                data-details="{{ $card['modal'] }}">View recommendation &rarr;</button>
                                            <span class="sep"></span>
                                        @endif
                                        <a href="#" class="lnk" data-details="{{ $card['modal'] }}">View details &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
                                <a href="{{ route('Performance.create') }}" class="btn perf-btn-hero btn-sm">
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
                    <input type="text" id="holiday_ot" class="form-control" value="$142.00 (70 Hrs)">
                </div>

            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn perf-btn-neutral ms-auto">Cancel</a>
                <a href="#" class="btn perf-btn-primary">Submit</a>
            </div>
        </div>
    </div>
</div>

@include('resorts.Performance.dashboard._insight_modals')
@includeWhen(isset($pi), 'partials._wai_insight_modals')
@endsection

@section('import-css')
<style>
    /* .card-heigth (on the row above) stretches every nested .card to
       height:100% of its flex item by default, so a short card sitting
       next to a much taller one (a compact KPI card next to the donut
       chart; a 2-row table next to the multi-item WAI Insights list) was
       being force-stretched into a tall box with a lot of empty space at
       the bottom instead of sizing to its own content. align-self:
       flex-start opts just these two columns out of that stretch. */
    .perf-checkins-col,
    .perf-appraisal-table-col {
        align-self: flex-start;
    }
</style>
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

