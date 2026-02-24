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
                    @if(isset($drafts) && $drafts->count() > 0)
                    <div class="col-lg-12">
                        <div class="card h-auto" id="card-drafts">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Drafts <span class="badge bg-secondary">{{ $drafts->count() }}</span></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-collapse">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>Department</th>
                                            <th>Employee Type</th>
                                            <th>No. of Vacancy</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($drafts as $draft)
                                            <tr>
                                                <td>{{ $draft->Getposition->position_title ?? 'N/A' }}</td>
                                                <td>{{ $draft->Getdepartment->name ?? 'N/A' }}</td>
                                                <td>{{ $draft->employee_type ?? 'N/A' }}</td>
                                                <td>{{ $draft->Total_position_required }}</td>
                                                <td>{{ $draft->created_at ? \Carbon\Carbon::parse($draft->created_at)->format('d M Y') : 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ route('resort.vacancies.edit', $draft->id) }}" class="btn btn-sm btn-themeBlue">Edit</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

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
                                                    @php
                                                        $excomSt = $hodSt = $hrSt = $finSt = $gmSt = null;
                                                        if(isset($request->TAnotificationParent[0])) {
                                                            foreach ($request->TAnotificationParent[0]->TAnotificationChildren as $ch) {
                                                                if ($ch->Approved_By == 1) $excomSt = $ch->status;
                                                                elseif ($ch->Approved_By == 2) $hodSt = $ch->status;
                                                                elseif ($ch->Approved_By == 3) $hrSt = $ch->status;
                                                                elseif ($ch->Approved_By == 7) $finSt = $ch->status;
                                                                elseif ($ch->Approved_By == 8) $gmSt = $ch->status;
                                                            }
                                                        }
                                                        $allStatuses = array_filter([$excomSt, $hodSt, $hrSt, $finSt, $gmSt]);
                                                        if (in_array('Rejected', $allStatuses)) {
                                                            $overallStatus = 'Rejected';
                                                            $badgeClass = 'bg-danger';
                                                        } elseif ($gmSt == 'Approved' || $gmSt == 'ForwardedToNext') {
                                                            $overallStatus = 'Approved';
                                                            $badgeClass = 'bg-success';
                                                        } elseif ($finSt == 'Approved' || $finSt == 'ForwardedToNext') {
                                                            $overallStatus = 'Pending GM';
                                                            $badgeClass = 'bg-info';
                                                        } elseif (in_array('Hold', $allStatuses)) {
                                                            $overallStatus = 'On Hold';
                                                            $badgeClass = 'bg-warning text-dark';
                                                        } elseif ($hrSt == 'Approved' || $hrSt == 'ForwardedToNext' || $excomSt == 'Approved' || $excomSt == 'ForwardedToNext' || $hodSt == 'Approved' || $hodSt == 'ForwardedToNext') {
                                                            $overallStatus = 'Pending Finance';
                                                            $badgeClass = 'bg-primary';
                                                        } else {
                                                            $overallStatus = 'Pending HR';
                                                            $badgeClass = 'bg-secondary';
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $request->Getposition->position_title }}</td>
                                                        <td>{{ $request->Total_position_required }}</td>
                                                        <td><span class="badge {{ $badgeClass }}">{{ $overallStatus }}</span></td>
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
                                                        <td colspan="4">
                                                            <div class="bg">
                                                                <ul class="manning-timeline text-start">
                                                                    @php
                                                                        $excomStatusTL = $hodStatusTL = $hrStatus = $financeStatus = $gmStatus = null;
                                                                        $hrDateTL = $financeDateTL = $gmDateTL = $excomDateTL = $hodDateTL = null;

                                                                        foreach ($request->TAnotificationParent[0]->TAnotificationChildren as $child) {
                                                                            if ($child->Approved_By == 1) { $excomStatusTL = $child->status; $excomDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 2) { $hodStatusTL = $child->status; $hodDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 3) { $hrStatus = $child->status; $hrDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 7) { $financeStatus = $child->status; $financeDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 8) { $gmStatus = $child->status; $gmDateTL = $child->updated_at; }
                                                                        }

                                                                        $firstStepStatus = $hrStatus ?? $excomStatusTL ?? $hodStatusTL ?? 'Active';
                                                                        $firstStepDate = $hrDateTL ?? $excomDateTL ?? $hodDateTL ?? null;
                                                                        $financeStatus = $financeStatus ?? 'Active';
                                                                        $gmStatus = $gmStatus ?? 'Active';

                                                                        $step = 0;
                                                                        if ($firstStepStatus == 'Rejected' || $firstStepStatus == 'Hold') { $step = -1; }
                                                                        elseif ($firstStepStatus == 'Approved' || $firstStepStatus == 'ForwardedToNext') { $step = 1; }
                                                                        if ($financeStatus == 'Rejected' || $financeStatus == 'Hold') { $step = -2; }
                                                                        elseif ($financeStatus == 'Approved' || $financeStatus == 'ForwardedToNext') { $step = 2; }
                                                                        if ($gmStatus == 'Rejected' || $gmStatus == 'Hold') { $step = -3; }
                                                                        elseif ($gmStatus == 'Approved' || $gmStatus == 'ForwardedToNext') { $step = 3; }
                                                                    @endphp

                                                                    <li class="active">
                                                                        <span>Respond to HR</span>
                                                                    </li>

                                                                    @if ($firstStepStatus == 'Rejected' || $firstStepStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $firstStepStatus == 'Rejected' ? 'Rejected by HR' : 'On Hold by HR' }}</span>
                                                                            @if($firstStepDate)<br><small class="text-muted">{{ $firstStepDate }}</small>@endif
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 1 ? 'active' : '' }}">
                                                                            <span>Reviewed by HR and Sent to Finance</span>
                                                                            @if($step >= 1 && $firstStepDate)<br><small class="text-muted">{{ $firstStepDate }}</small>@endif
                                                                        </li>
                                                                    @endif

                                                                    @if ($financeStatus == 'Rejected' || $financeStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $financeStatus == 'Rejected' ? 'Rejected by Finance' : 'On Hold by Finance' }}</span>
                                                                            @if($financeDateTL)<br><small class="text-muted">{{ $financeDateTL }}</small>@endif
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 2 ? 'active' : '' }}">
                                                                            <span>Reviewed by Finance and Sent to GM/Corporate Office</span>
                                                                            @if($step >= 2 && $financeDateTL)<br><small class="text-muted">{{ $financeDateTL }}</small>@endif
                                                                        </li>
                                                                    @endif

                                                                    @if ($gmStatus == 'Rejected' || $gmStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $gmStatus == 'Rejected' ? 'Rejected by GM' : 'On Hold by GM' }}</span>
                                                                            @if($gmDateTL)<br><small class="text-muted">{{ $gmDateTL }}</small>@endif
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 3 ? 'active' : '' }}">
                                                                            <span>Approved by GM/Corporate Office</span>
                                                                            @if($step >= 3 && $gmDateTL)<br><small class="text-muted">{{ $gmDateTL }}</small>@endif
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
                                                    @php
                                                        $excomSt = $hodSt = $hrSt = $finSt = $gmSt = null;
                                                        if(isset($request->TAnotificationParent[0])) {
                                                            foreach ($request->TAnotificationParent[0]->TAnotificationChildren as $ch) {
                                                                if ($ch->Approved_By == 1) $excomSt = $ch->status;
                                                                elseif ($ch->Approved_By == 2) $hodSt = $ch->status;
                                                                elseif ($ch->Approved_By == 3) $hrSt = $ch->status;
                                                                elseif ($ch->Approved_By == 7) $finSt = $ch->status;
                                                                elseif ($ch->Approved_By == 8) $gmSt = $ch->status;
                                                            }
                                                        }
                                                        $allStatuses = array_filter([$excomSt, $hodSt, $hrSt, $finSt, $gmSt]);
                                                        if (in_array('Rejected', $allStatuses)) {
                                                            $overallStatus = 'Rejected';
                                                            $badgeClass = 'bg-danger';
                                                        } elseif ($gmSt == 'Approved' || $gmSt == 'ForwardedToNext') {
                                                            $overallStatus = 'Approved';
                                                            $badgeClass = 'bg-success';
                                                        } elseif ($finSt == 'Approved' || $finSt == 'ForwardedToNext') {
                                                            $overallStatus = 'Pending GM';
                                                            $badgeClass = 'bg-info';
                                                        } elseif (in_array('Hold', $allStatuses)) {
                                                            $overallStatus = 'On Hold';
                                                            $badgeClass = 'bg-warning text-dark';
                                                        } elseif ($hrSt == 'Approved' || $hrSt == 'ForwardedToNext' || $excomSt == 'Approved' || $excomSt == 'ForwardedToNext' || $hodSt == 'Approved' || $hodSt == 'ForwardedToNext') {
                                                            $overallStatus = 'Pending Finance';
                                                            $badgeClass = 'bg-primary';
                                                        } else {
                                                            $overallStatus = 'Pending HR';
                                                            $badgeClass = 'bg-secondary';
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $request->Getposition->position_title }}</td>
                                                        <td>{{ $request->Total_position_required }}</td>
                                                        <td><span class="badge {{ $badgeClass }}">{{ $overallStatus }}</span></td>
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
                                                    <td colspan="4">
                                                            <div class="bg">
                                                                <ul class="manning-timeline text-start">
                                                                    @php
                                                                        $excomStatusTL = $hodStatusTL = $hrStatus = $financeStatus = $gmStatus = null;
                                                                        $hrDateTL = $financeDateTL = $gmDateTL = $excomDateTL = $hodDateTL = null;

                                                                        foreach ($request->TAnotificationParent[0]->TAnotificationChildren as $child) {
                                                                            if ($child->Approved_By == 1) { $excomStatusTL = $child->status; $excomDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 2) { $hodStatusTL = $child->status; $hodDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 3) { $hrStatus = $child->status; $hrDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 7) { $financeStatus = $child->status; $financeDateTL = $child->updated_at; }
                                                                            elseif ($child->Approved_By == 8) { $gmStatus = $child->status; $gmDateTL = $child->updated_at; }
                                                                        }

                                                                        $firstStepStatus = $hrStatus ?? $excomStatusTL ?? $hodStatusTL ?? 'Active';
                                                                        $firstStepDate = $hrDateTL ?? $excomDateTL ?? $hodDateTL ?? null;
                                                                        $financeStatus = $financeStatus ?? 'Active';
                                                                        $gmStatus = $gmStatus ?? 'Active';

                                                                        $step = 0;
                                                                        if ($firstStepStatus == 'Rejected' || $firstStepStatus == 'Hold') { $step = -1; }
                                                                        elseif ($firstStepStatus == 'Approved' || $firstStepStatus == 'ForwardedToNext') { $step = 1; }
                                                                        if ($financeStatus == 'Rejected' || $financeStatus == 'Hold') { $step = -2; }
                                                                        elseif ($financeStatus == 'Approved' || $financeStatus == 'ForwardedToNext') { $step = 2; }
                                                                        if ($gmStatus == 'Rejected' || $gmStatus == 'Hold') { $step = -3; }
                                                                        elseif ($gmStatus == 'Approved' || $gmStatus == 'ForwardedToNext') { $step = 3; }
                                                                    @endphp

                                                                    <li class="active">
                                                                        <span>Respond to HR</span>
                                                                    </li>

                                                                    @if ($firstStepStatus == 'Rejected' || $firstStepStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $firstStepStatus == 'Rejected' ? 'Rejected by HR' : 'On Hold by HR' }}</span>
                                                                            @if($firstStepDate)<br><small class="text-muted">{{ $firstStepDate }}</small>@endif
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 1 ? 'active' : '' }}">
                                                                            <span>Reviewed by HR and Sent to Finance</span>
                                                                            @if($step >= 1 && $firstStepDate)<br><small class="text-muted">{{ $firstStepDate }}</small>@endif
                                                                        </li>
                                                                    @endif

                                                                    @if ($financeStatus == 'Rejected' || $financeStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $financeStatus == 'Rejected' ? 'Rejected by Finance' : 'On Hold by Finance' }}</span>
                                                                            @if($financeDateTL)<br><small class="text-muted">{{ $financeDateTL }}</small>@endif
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 2 ? 'active' : '' }}">
                                                                            <span>Reviewed by Finance and Sent to GM/Corporate Office</span>
                                                                            @if($step >= 2 && $financeDateTL)<br><small class="text-muted">{{ $financeDateTL }}</small>@endif
                                                                        </li>
                                                                    @endif

                                                                    @if ($gmStatus == 'Rejected' || $gmStatus == 'Hold')
                                                                        <li class="active">
                                                                            <span>{{ $gmStatus == 'Rejected' ? 'Rejected by GM' : 'On Hold by GM' }}</span>
                                                                            @if($gmDateTL)<br><small class="text-muted">{{ $gmDateTL }}</small>@endif
                                                                        </li>
                                                                    @else
                                                                        <li class="{{ $step >= 3 ? 'active' : '' }}">
                                                                            <span>Approved by GM/Corporate Office</span>
                                                                            @if($step >= 3 && $gmDateTL)<br><small class="text-muted">{{ $gmDateTL }}</small>@endif
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
            <input type="hidden" name="Dasboard_resort_id" value="{{$resort_id}}" id="Dasboard_resort_id">
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
                                    No Record Found
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
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            editable: true,
            eventLimit: 0,
            navLinks: false,
            events: function(start, end, timezone, callback) {
                let Resort_id = $("#Dasboard_resort_id").val();
                $.ajax({
                    url: "{{ route('resort.ta.GetDateclickWiseUpcomingInterview') }}",
                    type: "POST",
                    data: {
                        start: start.format('YYYY-MM-DD'),
                        end: end.format('YYYY-MM-DD'),
                        Resort_id: Resort_id,
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        $("#upinterviews").html(response.view);
                        $('.fc-day').removeClass('custom-dot');
                        response.dates.forEach(function(date) {
                            let formattedDate = moment(date).format('YYYY-MM-DD');
                            let dayCell = $(`.fc-day[data-date="${formattedDate}"]`);
                            if (dayCell.length) {
                                dayCell.addClass('custom-dot');
                            }
                        });
                        callback([]);
                    },
                    error: function(xhr) {
                        console.error("Error fetching interview dates", xhr);
                    }
                });
            },
            dayClick: function(date, jsEvent, view) {
                let Resort_id = $("#Dasboard_resort_id").val();
                $.ajax({
                    url: "{{ route('resort.ta.GetDateclickWiseUpcomingInterview') }}",
                    type: "POST",
                    data: {
                        date: date.format('YYYY-MM-DD'),
                        Resort_id: Resort_id,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            $("#upinterviews").html(response.view);
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        if (errors && errors.errors) {
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                        } else {
                            errs = "An unexpected error occurred.";
                        }
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });
</script>
@endsection
