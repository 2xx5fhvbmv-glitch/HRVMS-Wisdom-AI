@extends('resorts.layouts.app')
@section('page_tab_title' ," People Relation Dashboard")

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
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            @php
                // Arrow icon was loading from a relative path that broke on
                // any non-root URL — switch to the absolute asset() helper.
                $arrowIcon = asset('resorts_assets/images/arrow-right-circle.svg');
                $grievanceListUrl = route('GrievanceAndDisciplinery.grivance.GrivanceIndex');
                $disciplinaryListUrl = route('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex');
            @endphp
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Open Cases</p>
                            <strong class="d-block" style="font-size:14px;font-weight:600;">
                                <a href="{{ $grievanceListUrl }}" class="text-decoration-none">Grievance: {{ $openGrievance ?? 0 }}</a>
                                |
                                <a href="{{ $disciplinaryListUrl }}" class="text-decoration-none">Disciplinary: {{ $openDisciplinary ?? 0 }}</a>
                            </strong>
                        </div>
                        <a href="{{ $grievanceListUrl }}">
                            <img src="{{ $arrowIcon }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Pending Cases</p>
                            <strong class="d-block" style="font-size:14px;font-weight:600;">
                                <a href="{{ $grievanceListUrl }}" class="text-decoration-none">Grievance: {{ $pendingGrievance ?? 0 }}</a>
                                |
                                <a href="{{ $disciplinaryListUrl }}" class="text-decoration-none">Disciplinary: {{ $pendingDisciplinary ?? 0 }}</a>
                            </strong>
                        </div>
                        <a href="{{ $grievanceListUrl }}">
                            <img src="{{ $arrowIcon }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Closed Cases</p>
                            <strong class="d-block" style="font-size:14px;font-weight:600;">
                                <a href="{{ $grievanceListUrl }}" class="text-decoration-none">Grievance: {{ $closedGrievance ?? 0 }}</a>
                                |
                                <a href="{{ $disciplinaryListUrl }}" class="text-decoration-none">Disciplinary: {{ $closedDisciplinary ?? 0 }}</a>
                            </strong>
                        </div>
                        <a href="{{ $grievanceListUrl }}">
                            <img src="{{ $arrowIcon }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Expired Offense</p>
                            <strong>{{ $expiredOffense ?? 0 }}</strong>
                        </div>
                        <a href="{{ $disciplinaryListUrl }}">
                            <img src="{{ $arrowIcon }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            {{-- WAI Insights — AI-narrated grievance & disciplinary metrics (took the Resolution Rate slot) --}}
            <div class="col-xl-3 col-sm-6">
                <div class="card card-wiINsight card-wiINsightGriev h-100" id="card-wiINsightGriev">
                    @php $grMeta = $grievanceInsights['_meta'] ?? null; @endphp
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">WAI Insights</h3>
                            </div>
                            <div class="col-auto text-end" style="font-size:12px;line-height:1.3;">
                                @if($grMeta)
                                    <div class="text-muted">Updated {{ $grMeta['generated_at']->diffForHumans() }}</div>
                                    @if($grMeta['can_regenerate'])
                                        <a href="?regenerate_insights=1" class="a-link">Regenerate</a>
                                    @else
                                        <span class="text-muted" title="{{ $grMeta['next_available']->format('d M Y, H:i') }}">Regenerate {{ $grMeta['next_available']->diffForHumans() }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        @foreach([['key'=>'volume','modal'=>'grievInsightVolumeModal'],['key'=>'sla','modal'=>'grievInsightSlaModal'],['key'=>'hotspots','modal'=>'grievInsightHotspotsModal'],['key'=>'outcomes','modal'=>'grievInsightOutcomesModal']] as $gc)
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="{{ URL::asset('resorts_assets/images/wisdom-ai-small.svg') }}" alt="image">
                            </div>
                            <div>
                                <h6>{{ $grievanceInsights[$gc['key']]['title'] ?? '' }}</h6>
                                <p>{{ $grievanceInsights[$gc['key']]['body'] ?? '' }}</p>
                                @if(!empty($grievanceInsights[$gc['key']]['recommendation']))
                                    <p class="mb-2" style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $grievanceInsights[$gc['key']]['recommendation'] }}</p>
                                @endif
                                <div>
                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#{{ $gc['modal'] }}" class="a-link">View Details</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex flex-column">
                <div class="row g-3 g-xxl-4 flex-grow-1">
                    <div class="col-12">
                        <div class="card peopleRelation-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <p>Delegated Cases:</p>
                                <strong>{{$DelegatedCases ?? 0}}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card peopleRelation-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <p>Pending Approvals:</p>
                                <strong>{{$PendingApprovals ?? 0}}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 flex-grow-1">
                        <div class="card card-confiCases h-100">
                            <div class="card-title mb-lg-3">
                                <h3>Confidential Cases:</h3>
                            </div>
                            <div class="d-flex">
                                <div class="progress progress-custom progress-themeskyblue">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $confidentialResolvedPct ?? 0 }}%"
                                        aria-valuenow="{{ $confidentialResolvedPct ?? 0 }}" aria-valuemin="0" aria-valuemax="100">{{ $confidentialResolvedPct ?? 0 }}%</div>
                                </div>
                                <span>Resolved</span>
                            </div>
                            <div class="d-flex mb-lg-4 mb-md-3">
                                <div class="progress progress-custom progress-themeskyblue">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $confidentialUnresolvedPct ?? 0 }}%"
                                        aria-valuenow="{{ $confidentialUnresolvedPct ?? 0 }}" aria-valuemin="0" aria-valuemax="100">{{ $confidentialUnresolvedPct ?? 0 }}%</div>
                                </div>
                                <span>Unresolved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Appeals Section hidden per request — not producing useful data
                 yet (0 appeals filed on real resorts so far). Uncomment to
                 bring it back; $appealsSubmitted/$hearingsPending/
                 $hearingsResolved/$appealsByCategoryLabels/Data are still
                 computed and passed by the controller.
            <div class="col-xl-6 d-flex">
                <div class="card card-appealsSection h-100 w-100">
                    <div class="card-title">
                        <h3>Appeals Section</h3>
                    </div>
                    <p>Total Appeals Submitted: {{ $appealsSubmitted ?? 0 }} | Average Resolution Time: {{ $avgResolutionHours ?? 0 }} Hrs</p>
                    <div class="row g-3 g-xxl-4">
                        <div class="col-md-6  col-sm-7">
                            <div class="bg-themeGrayLight">
                                <h6>Appeals by category</h6>
                                <canvas id="appealsByCategory" width="349" height="199"></canvas>
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
                                                    <span class="bg-theme"></span>Pending <br>{{ $hearingsPending ?? 0 }}
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeLightBlue"></span>Resolved
                                                    <br>{{ $hearingsResolved ?? 0 }}
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
            --}}
            <div class="col-xl-3 col-sm-6 d-flex @if(Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-grievanceCategoryBreakdown h-100 w-100">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Grievances</h3>
                            </div>
                            <div class="col-auto"><a href="#" class="a-link">View All</a> </div>
                        </div>
                    </div>
                    <div class="card-grievanceCategoryBreakdown-list">
                    @foreach($grivanceCategoryWiseCount as $k=>$category)
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <p class="mb-0">{{$k}}</p>
                        <p>{{$category}}</p>
                    </div>
                    @endforeach
                    </div>
                </div>
            </div>
             <div class="col-xl-3 col-sm-6 order-sm-1 order-xl-0">
                <div class="row g-3 g-xxl-4">
                    {{-- Retaliation Reports Filed tile hidden per the latest
                         design — uncomment to bring it back without re-wiring
                         the controller (the $retaliationReports variable is
                         still passed to the view).
                    <div class="col-12">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0  fw-600">Retaliation Reports Filed</p>
                                <strong>{{ $retaliationReports ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>
                    --}}
                    <div class="col-12">
                        <div class="card card-reportsResolved">
                            <div class="card-title ">
                                <h3>Reports</h3>
                            </div>
                            <div class="progress-block">
                                <div class="progress-container blue" data-progress="{{ $totalPercengate ?? 0 }}" data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" title="Grievances Resolved: {{ $totalPercengate ?? 0 }}%">
                                    <svg class="progress-circle" viewBox="0 0 120 120">
                                        <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                        <circle class="progress" cx="60" cy="60" r="54"></circle>
                                    </svg>
                                </div>
                                <div class="text">
                                    <h5>{{ $totalPercengate ?? 0 }}%</h5>
                                    <p>GRIEVANCES RESOLVED</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <p class="mb-0">Average Resolution Time:</p>
                                <p class="mb-0"><strong>{{ $avgResolutionHours ?? 0 }} HRS</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 order-sm-2 order-xl-0">
                <div class="card">
                    <div class="card-title mb-md-3">
                        <h3>Case Timelines</h3>
                    </div>
                    @forelse(($caseTimelines ?? collect()) as $tl)
                        @php
                            // Color the progress bar by how close we are to the
                            // deadline: <40 % green, 40–70 % amber, >70 % red.
                            $pct = $tl['progress_pct'];
                            $color = $pct < 40 ? 'progress-themeGreen' : ($pct < 70 ? 'progress-themeWarning' : 'progress-themeRed');
                        @endphp
                        <div class="caseTimelines-block">
                            <p>{{ $tl['name'] }}</p>
                            <div class="progress progress-custom progress-customDot {{ $color }}">
                                <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"
                                    aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div>
                                <div>
                                    <p>Filled date:</p><span>{{ $tl['filed_date'] }}</span>
                                </div>
                                <div>
                                    <p>Deadline:</p><span>{{ $tl['deadline'] }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No active cases on the timeline.</p>
                    @endforelse
                </div>
            </div>
           
            <!-- <div class="col-xl-6 order-sm-3 order-xl-0">
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
                    <canvas id="breakdownCases"></canvas>
                </div>
            </div> -->
            <!-- <div class="col-xl-3 col-md-6 order-sm-4 order-xl-0">
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
            </div> -->

            {{-- Resolution Rate — moved here from the top row (gave its slot to WAI Insights) --}}
            <div class="col-xl-3 col-sm-6">
                <div class="card card-resolutionRate h-100">
                    <div class="card-title mb-lg-4">
                        <h3>Resolution Rate</h3>
                    </div>
                    <div class="progress-block">
                        <div class="progress-container blue " data-progress="{{ $totalPercengate ?? 0 }}" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Grievances Resolved: {{ $totalPercengate ?? 0 }}%">
                            <svg class="progress-circle" viewBox="0 0 120 120">
                                <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                <circle class="progress" cx="60" cy="60" r="54"></circle>
                            </svg>
                        </div>
                        <div class="text">
                            <h5>{{ $totalPercengate ?? 0 }}%</h5>
                            <p>GRIEVANCES RESOLVED</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p class="mb-0">Average Resolution Time:</p>
                        <p class="mb-0"><strong>{{ $avgResolutionHours ?? 0 }} HRS</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@includeWhen(isset($grievanceInsights), 'resorts.GrievanceAndDisciplinery.dashboard._insight_modals')
@endsection

@section('import-css')
<style>
    /* WAI Insights — grievance card in the Resolution Rate slot. Fixed height
       with the insight list scrolling inside the narrow column. */
    .card-wiINsightGriev {
        height: 100% !important;
        max-height: 420px !important;
        display: flex;
        flex-direction: column;
    }
    .card-wiINsightGriev .leaveUser-main {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    /* Confidential Cases / Appeals Section / Grievances breakdown row —
       these three columns sit side by side but had unequal card heights
       since a plain .card hugs its own content instead of stretching to
       match its tallest sibling. Cards fill their (already row-stretched)
       column, and the one list that can grow unbounded scrolls internally
       instead of pushing the row taller. */
    .card-appealsSection,
    .card-grievanceCategoryBreakdown {
        display: flex;
        flex-direction: column;
    }
    .card-grievanceCategoryBreakdown-list {
        flex: 1 1 auto;
        min-height: 0;
        max-height: 380px;
        overflow-y: auto;
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
    // The canvases referenced below (appealsByCategory, myDoughnutChartPeopleRelation,
    // breakdownCases) live inside HTML blocks that are currently commented out.
    // Guard each Chart init so getElementById(...).getContext() doesn't crash
    // and prevent the rest of the page JS from running.
    const _appealsByCategoryEl = document.getElementById('appealsByCategory');
    if (_appealsByCategoryEl) {
    const cty = _appealsByCategoryEl.getContext('2d');
    // Wired to live data from the controller. Falls back to a single
    // empty bucket so the canvas still renders if there are no appeals.
    const _appealsLabels = @json($appealsByCategoryLabels ?? []);
    const _appealsData   = @json($appealsByCategoryData ?? []);
    const appealsByCategory = new Chart(cty, {
        type: 'bar',
        data: {
            labels: _appealsLabels.length ? _appealsLabels : ['No appeals yet'],
            datasets: [
                {
                    data: _appealsData.length ? _appealsData : [0],
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
                            return formatAmount(value, 'USD'); // Custom tooltip format
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


    } // end appealsByCategory guard

    const _doughnutEl = document.getElementById('myDoughnutChartPeopleRelation');
    if (_doughnutEl) {
    var ctx = _doughnutEl.getContext('2d');

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

    // Live counts from the controller. If both are zero, seed [1,0] so
    // the doughnut still renders (Chart.js draws nothing for [0,0]).
    const _hearingsPending  = parseInt(@json($hearingsPending ?? 0), 10) || 0;
    const _hearingsResolved = parseInt(@json($hearingsResolved ?? 0), 10) || 0;
    const _hearingsData = (_hearingsPending + _hearingsResolved) === 0
        ? [1, 0]
        : [_hearingsPending, _hearingsResolved];
    var myDoughnutChartPeopleRelation = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Resolved'],
            datasets: [{
                data: _hearingsData,
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

    } // end myDoughnutChartPeopleRelation guard

    const _breakdownEl = document.getElementById('breakdownCases');
    if (_breakdownEl) {
    const ctz = _breakdownEl.getContext('2d');
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
                            return formatAmount(value, 'USD'); // Custom tooltip format
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
    } // end breakdownCases guard
</script>
@endsection

