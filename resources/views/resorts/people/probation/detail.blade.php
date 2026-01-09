@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto"><a href="#" class="btn btn-theme">Export</a></div> -->
                </div>
            </div>

            <div class="card card-probationDetails" id="probationCard">
                <div class="row g-md-3 g-2 mb-3 align-items-center">
                    <div class="col-md">
                        <div class="d-flex align-items-center">
                            <div class="img-circle userImg-block me-xl-4 me-md-2 me-2">
                                <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id ?? null)}}" alt="image">
                            </div>
                            <div>
                                <h4 class="fw-600">{{$employee->resortAdmin->full_name}} <span class="badge badge-themeNew">#{{$employee->Emp_id}}</span></h4>
                                <p>{{$employee->department->name}} - {{$employee->position->position_title}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto text-end ms-auto">
                        <p class="mb-2"><i>Joining Date: {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</i></p>
                        <span class="badge badge-themeSuccess">{{$employee->probation_status}} Probation</span>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('employee.probation.export', $employee->id) }}" class="btn btn-themeSkyblue btn-sm">Export</a>
                    </div>
                    <div class="col-auto">
                        <button onclick="printCard()" class="btn btn-themeBlue btn-sm">Print</button>
                    </div>
                </div>
                @php
                    $startDate = \Carbon\Carbon::parse($employee->joining_date);
                    $endDate = \Carbon\Carbon::parse($employee->probation_end_date);
                    $today = \Carbon\Carbon::now();

                    $totalDays = $startDate->diffInDays($endDate);
                    
                    if ($today->lte($endDate)) {
                        // If today is before or equal to end date, calculate remaining days directly
                        $remainingDays = $today->diffInDays($endDate);
                        $daysPassed = $totalDays - $remainingDays;
                    } else {
                        // If probation period is already over
                        $remainingDays = 0;
                        $daysPassed = $totalDays;
                    }
                    
                    $progress = $totalDays > 0 ? min(100, round(($daysPassed / $totalDays) * 100)) : 0;
                @endphp

                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="row g-md-2 g-1 justify-content-between mb-md-3 mb-2">
                        <div class="col-auto">
                            <h6 class="fw-600">Probation Details</h6>
                        </div>
                        <div class="col-auto">
                            <h6 class="fw-600">
                                Days Remaining: {{$remainingDays}}
                            </h6>
                        </div>
                    </div>
                    <div class="progress progress-custom progress-themeskyblueNew mb-2">
                        <div class="progress-bar" role="progressbar" style="width: {{$progress}}%" aria-valuenow="80"
                            aria-valuemin="0" aria-valuemax="100">{{$progress}}%</div>
                    </div>
                    <div class="row g-md-2 g-1 justify-content-between">
                        <div class="col-auto">
                            <p>Start Date: {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</p>
                        </div>
                        <div class="col-auto">
                            <p>End Date: {{ \Carbon\Carbon::parse($employee->probation_end_date)->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row g-md-4 g-2 mb-md-4 mb-3">
                    <div class="col-xl-3 col-lg-4 col-md-5">
                        <div class="cardBorder-block">
                            <div class="card-title">
                                <h3>Manager Information</h3>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="img-circle userImg-block me-md-3 me-2">
                                    <img src="{{Common::getResortUserPicture($employee->reportingToAdmin->Admin_Parent_id ?? null)}}" alt="image">
                                </div>
                                <div>
                                    <h4 class="fw-600">{{$employee->reportingToAdmin->first_name}} {{$employee->reportingToAdmin->last_name}} </h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class=" mb-0">
                                    <tr>
                                        <th>Position:</th>
                                        <td>{{$employee->reportingTo->position->position_title}}</td>
                                    </tr>
                                    <tr>
                                        <th>Department:</th>
                                        <td>{{$employee->reportingTo->department->name}}</td>
                                    </tr>
                                    <tr>
                                        <th>Section:</th>
                                        <td>{{$employee->reportingTo->section->name ?? 'N/A'}}</td>
                                    </tr>
                                    <tr>
                                        <th>Division:</th>
                                        <td>{{$employee->reportingTo->division->name ?? 'N/A'}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-9 col-lg-8 col-md-7">
                        <div class="cardBorder-block">
                            <div class="card-title">
                                <h3>Progress Tracking</h3>
                            </div>
                            <ul class="manning-timeline text-start ">
                                <li class="active">
                                    <div>
                                        <h6>Onboarding Training</h6>
                                        <p>Due: 01 Jan 2025</p>
                                    </div>
                                    <span class="badge badge-themeSuccess">Completed</span>
                                </li>
                                @foreach ($monthlyCheckins as $index => $checkin)
                                    <li class="{{ $checkin['status'] === 'Completed' ? 'active' : '' }}">
                                        <div>
                                            <h6>{{ Common::ordinal($index + 1) }} Monthly Check-in</h6>
                                            <p>Due: {{ $checkin['label'] }}</p>
                                        </div>
                                        <span class="badge {{ $checkin['badge_class'] }}">{{ $checkin['status'] }}</span>
                                    </li>
                                @endforeach                                
                                <li>
                                    <div>
                                        <h6>Final Probation Review</h6>
                                        <p>Due: 01 Mar 2025</p>
                                    </div>
                                    <span class="badge badge-themeWarning">Pending</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row g-2">
                        <div class="col-auto"> 
                            <a href="#" class="btn btn-themeNeon btn-sm send-letter" data-id="{{ $employee->id }}" data-type="success">
                                Send Probation Successful Letter
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-themeBlue btn-sm send-letter" data-id="{{ $employee->id }}" data-type="failed">
                                Send Probation Unsuccessful Letter
                            </a>
                        </div>
                        <!-- <div class="col-auto ms-auto">
                            <a href="#" class="btn btn-themeSkyblue btn-sm">
                                Confirm Probation Completion
                            </a>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #probationCard, #probationCard * {
        visibility: visible;
    }
    #probationCard {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    /* Hide print/export buttons permanently from print */
    .card-probationDetails .btn {
        display: none !important;
    }
}
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.send-letter').on('click', function () {
            const empId = $(this).data('id');
            const type = $(this).data('type');
            $.ajax({
                url: '{{route("probation.send-letter")}}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    employee_id: empId,
                    type: type
                },
                success: function (response) {
                    // alert(response.message);
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
    });
    function printCard() {
        window.print();
    }
</script>
@endsection
