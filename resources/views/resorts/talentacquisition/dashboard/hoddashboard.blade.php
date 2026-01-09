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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="{{route('resort.vacancies.create')}}" class="btn btn-theme @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.create')) == false) d-none @endif">New Hire</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 g-xxl-4 recHR-main">
            <div class="col-xl-8 col-lg-12">
                <div class="row g-3 g-xxl-4 ">
                    <div class="col-md-4  @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.shortlistedapplicants',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Total Applicants</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{$TotalApplicants}}</strong>

                                </div>
                            </div>
                            <div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Interviews</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{$Interviews}}</strong>

                                </div>
                            </div>
                            <div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Hired</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{$Hired}}</strong>
                                </div>
                            </div>
                            <div>
                              
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-auto" id="card-vac">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Vacancies</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('resort.vacancies.FreshApplicant') }}" class="a-link">View all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-collapse ">
                                    <thead>
                                        <tr>
                                            <th>Positions</th>
                                            <th>No. of Vacancy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($vacancies)
                                            @foreach($vacancies as $list)
                                                <tr>
                                                    <td>{{$list->Getposition->position_title}}</td>
                                                    <td>{{$list->Total_position_required}}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-auto" id="card-vac">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Requested</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('resort.ta.ViewVacancies') }}" class="a-link">View all</a>
                                    </div>
                                </div>

                            </div>
                            @php
                                // Split the hiring requests into two halves
                                $splitIndex = ceil($hiring_request->count() / 2);
                                $leftRequests = $hiring_request->slice(0, $splitIndex);
                                $rightRequests = $hiring_request->slice($splitIndex);
                            @endphp

                            <div class="row">
                                <!-- Left Table -->
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-collapseNew table-recHoD">
                                            <tbody>
                                                @foreach($leftRequests as $key => $request)
                                                    <tr>
                                                        <td>{{ $request->Getposition->position_title }}</td>
                                                        <td>{{ $request->Total_position_required }}</td>
                                                        <td>
                                                            <a class="a-link collapsed" data-bs-toggle="collapse"
                                                                data-bs-target="#LeftRequested{{ $key }}"
                                                                aria-expanded="false"
                                                                aria-controls="LeftRequested{{ $key }}">
                                                                View Progress
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="LeftRequested{{ $key }}">
                                                        <td colspan="3">
                                                            <div class="bg">
                                                                <ul class="manning-timeline text-start">
                                                                    @php
                                                                        // Initialize the status for each role
                                                                        $hrStatus = $financeStatus = $gmStatus = 'Active';

                                                                        // Loop through the TAnotificationChildren to get statuses for HR, Finance, GM
                                                                        foreach ($request->TAnotificationParent[0]->TAnotificationChildren as $child) {
                                                                            if ($child->Approved_By == 3) { // HR
                                                                                $hrStatus = $child->status;
                                                                            } elseif ($child->Approved_By == 7) { // Finance
                                                                                $financeStatus = $child->status;
                                                                            } elseif ($child->Approved_By == 8) { // GM
                                                                                $gmStatus = $child->status;
                                                                            }
                                                                        }

                                                                        // Determine the active step based on status
                                                                        $step = 0;

                                                                        // Handle Rejection by HR, Finance, or GM
                                                                        if ($hrStatus == 'Rejected' || $hrStatus == 'Hold') {
                                                                            $step = -1; // Rejected or On Hold by HR, cycle stops here
                                                                        } elseif ($hrStatus == 'Approved' || $hrStatus == 'ForwardedToNext') {
                                                                            $step = 1; // Step 1: Respond to HR is complete
                                                                        }

                                                                        if ($financeStatus == 'Rejected' || $financeStatus == 'Hold') {
                                                                            $step = -2; // Rejected or On Hold by Finance, cycle stops here after HR step
                                                                        } elseif ($financeStatus == 'Approved' || $financeStatus == 'ForwardedToNext') {
                                                                            $step = 2; // Step 2: Reviewed by HR and Sent to Finance is complete
                                                                        }

                                                                        if ($gmStatus == 'Rejected' || $gmStatus == 'Hold') {
                                                                            $step = -3; // Rejected or On Hold by GM, cycle stops here after Finance step
                                                                        } elseif ($gmStatus == 'Approved' || $gmStatus == 'ForwardedToNext') {
                                                                            $step = 3; // Step 3: Reviewed by Finance and Sent to GM is complete
                                                                        }
                                                                    @endphp

                                                                    <!-- Step 1: Respond to HR -->
                                                                    <li class="active">
                                                                        <span>Respond to HR</span>
                                                                    </li>

                                                                    <!-- Step 2: Reviewed by HR and Sent to Finance -->
                                                                    @if ($hrStatus == 'Rejected' || $hrStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $hrStatus == 'Rejected' ? 'Rejected by HR' : 'On Hold by HR' }}</span>
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 1 || $step < 0 && $hrStatus != 'Rejected' && $hrStatus != 'Hold' ? 'active' : '' }}">
                                                                            <span>Reviewed by HR and Sent to Finance</span>
                                                                        </li>
                                                                    @endif

                                                                    <!-- Step 3: Rejected by Finance or On Hold -->
                                                                    @if ($financeStatus == 'Rejected' || $financeStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $financeStatus == 'Rejected' ? 'Rejected by Finance' : 'On Hold by Finance' }}</span>
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 2 || $step < 0 && $hrStatus != 'Rejected' && $hrStatus != 'Hold' && $financeStatus != 'Rejected' && $financeStatus != 'Hold' ? 'active' : '' }}">
                                                                            <span>Reviewed by Finance and Sent to GM/Corporate Office</span>
                                                                        </li>
                                                                    @endif

                                                                    <!-- Step 4: Rejected by GM or On Hold -->
                                                                    @if ($gmStatus == 'Rejected' || $gmStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $gmStatus == 'Rejected' ? 'Rejected by GM' : 'On Hold by GM' }}</span>
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 3 && $hrStatus != 'Rejected' && $financeStatus != 'Rejected' && $gmStatus != 'Rejected' && $hrStatus != 'Hold' && $financeStatus != 'Hold' && $gmStatus != 'Hold' ? 'active' : '' }}">
                                                                            <span>Approved by GM/Corporate Office</span>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Right Table -->
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-collapseNew table-recHoD">
                                            <tbody>
                                                @foreach($rightRequests as $key => $request)
                                                    <tr>
                                                        <td>{{ $request->Getposition->position_title }}</td>
                                                        <td>{{ $request->Total_position_required }}</td>
                                                        <td>
                                                            <a class="a-link collapsed" data-bs-toggle="collapse"
                                                                data-bs-target="#RightRequested{{ $key }}"
                                                                aria-expanded="false"
                                                                aria-controls="RightRequested{{ $key }}">
                                                                View Progress
                                                            </a>
                                                        </td>
                                                    </tr>

                                                    <tr class="collapse" id="RightRequested{{ $key }}">
                                                    <td colspan="3">
                                                            <div class="bg">
                                                                <ul class="manning-timeline text-start">
                                                                    @php
                                                                        // Initialize the status for each role
                                                                        $hrStatus = $financeStatus = $gmStatus = 'Active';

                                                                        // Loop through the TAnotificationChildren to get statuses for HR, Finance, GM
                                                                        foreach ($request->TAnotificationParent[0]->TAnotificationChildren as $child) {
                                                                            if ($child->Approved_By == 3) { // HR
                                                                                $hrStatus = $child->status;
                                                                            } elseif ($child->Approved_By == 7) { // Finance
                                                                                $financeStatus = $child->status;
                                                                            } elseif ($child->Approved_By == 8) { // GM
                                                                                $gmStatus = $child->status;
                                                                            }
                                                                        }

                                                                        // Determine the active step based on status
                                                                        $step = 0;

                                                                        // Handle Rejection by HR, Finance, or GM
                                                                        if ($hrStatus == 'Rejected' || $hrStatus == 'Hold') {
                                                                            $step = -1; // Rejected or On Hold by HR, cycle stops here
                                                                        } elseif ($hrStatus == 'Approved' || $hrStatus == 'ForwardedToNext') {
                                                                            $step = 1; // Step 1: Respond to HR is complete
                                                                        }

                                                                        if ($financeStatus == 'Rejected' || $financeStatus == 'Hold') {
                                                                            $step = -2; // Rejected or On Hold by Finance, cycle stops here after HR step
                                                                        } elseif ($financeStatus == 'Approved' || $financeStatus == 'ForwardedToNext') {
                                                                            $step = 2; // Step 2: Reviewed by HR and Sent to Finance is complete
                                                                        }

                                                                        if ($gmStatus == 'Rejected' || $gmStatus == 'Hold') {
                                                                            $step = -3; // Rejected or On Hold by GM, cycle stops here after Finance step
                                                                        } elseif ($gmStatus == 'Approved' || $gmStatus == 'ForwardedToNext') {
                                                                            $step = 3; // Step 3: Reviewed by Finance and Sent to GM is complete
                                                                        }
                                                                    @endphp

                                                                    <!-- Step 1: Respond to HR -->
                                                                    <li class="active">
                                                                        <span>Respond to HR</span>
                                                                    </li>

                                                                    <!-- Step 2: Reviewed by HR and Sent to Finance -->
                                                                    @if ($hrStatus == 'Rejected' || $hrStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $hrStatus == 'Rejected' ? 'Rejected by HR' : 'On Hold by HR' }}</span>
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 1 || $step < 0 && $hrStatus != 'Rejected' && $hrStatus != 'Hold' ? 'active' : '' }}">
                                                                            <span>Reviewed by HR and Sent to Finance</span>
                                                                        </li>
                                                                    @endif

                                                                    <!-- Step 3: Rejected by Finance or On Hold -->
                                                                    @if ($financeStatus == 'Rejected' || $financeStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $financeStatus == 'Rejected' ? 'Rejected by Finance' : 'On Hold by Finance' }}</span>
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 2 || $step < 0 && $hrStatus != 'Rejected' && $hrStatus != 'Hold' && $financeStatus != 'Rejected' && $financeStatus != 'Hold' ? 'active' : '' }}">
                                                                            <span>Reviewed by Finance and Sent to GM/Corporate Office</span>
                                                                        </li>
                                                                    @endif

                                                                    <!-- Step 4: Rejected by GM or On Hold -->
                                                                    @if ($gmStatus == 'Rejected' || $gmStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $gmStatus == 'Rejected' ? 'Rejected by GM' : 'On Hold by GM' }}</span>
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 3 && $hrStatus != 'Rejected' && $financeStatus != 'Rejected' && $gmStatus != 'Rejected' && $hrStatus != 'Hold' && $financeStatus != 'Hold' && $gmStatus != 'Hold' ? 'active' : '' }}">
                                                                            <span>Approved by GM/Corporate Office</span>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-6 @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card h-auto">
                    <div class="mb-4 overflow-hidden">
                        <div id="calendar"></div>
                    </div>
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-3">
                            <div class="col">
                                <h3>Upcoming Interviews</h3>
                            </div>
                            <!-- <div class="col-auto">
                                <a href="#" class="a-link">View all</a>
                            </div> -->
                        </div>
                    </div>
                    <div id="upinterviews">
                        @if( $UpcomingApplicants->isNotEmpty())
                            @foreach( $UpcomingApplicants as $u)
                            
                                <div class="upInterviews-block">
                                    <div class="img-circle">
                                        <img src="{{ $u->profileImg }}" alt="image">
                                    </div>
                                    <div>
                                        <h6>{{ $u->name }}</h6>
                                        <p>{{ $u->Position }}</p>
                                        <span class="badge badge-theme">{{ $u->Department }}</span>
                                    </div>
                                    <div>
                                        <div class="date">{{ $u->InterViewDate }}</div>
                                        <div class="time">{{ $u->ResortInterviewtime }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="upInterviews-block">
                                <div style="text-align: left;" >
                                    No Recore Found
                                </div>
                            </div>
                        @endif
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
<script>
    $(".table-icon").click(function () {
        $(this).parents('tr').toggleClass("in");
    });
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

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
</script>
@endsection
