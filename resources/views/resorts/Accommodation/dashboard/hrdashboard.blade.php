@extends('resorts.layouts.app')
@section('page_tab_title' ,"Accommodation Dashboard")

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
                        <span>Accommodation</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AccommodationMaster',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">  Employee Present</p>
                            <strong>{{ $EmployeesCount }}</strong>
                        </div>
                        <a href="#">

                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AccommodationMaster',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Occupied Bed </p>
                            <strong class="text-danger">{{ $OccupiedBed }}</strong>
                            <span class="text-danger">More than Employees Presented </span>
                        </div>
                        <a href="#">

                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AccommodationMaster',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Bed </p>
                            <strong>{{ $TotalBed }}</strong>
                        </div>
                        <a href="#">

                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AvailableAccommodation',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Available Accomodation</p>
                            <strong>{{ $AvailableAccomodation }}</strong>
                        </div>
                        <a href="#">

                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-9 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.MaintanaceRequestlist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Maintenance Request</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select ResortDepartment" aria-label="Default select example">
                                        <option ></option>
                                        @foreach ($ResortDepartment as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto"><a href="{{ route('resort.accommodation.MaintanaceRequestlist') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="" class="table table-accomMainten  w-100">
                        <thead>
                            <tr>
                                <th>Requested By</th>
                                <th>Affected Amenity </th>
                                <th>Location </th>
                                <th>Priority</th>
                                <th>Assigned Staff</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-accomSummary">
                    <div class="card-title">
                        <h3>Summary</h3>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-bgBlock">
                            <h6>Total number of open requests</h6>
                            <h3>{{ $Totalnumberofopenrequests }}</h3>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Number of high-priority requests</h6>
                            <h3>{{ $TotalnumberofHighrequests }}</h3>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Number of requests nearing completion.</h6>
                            <h3>{{ $TotalnumberofInProgressrequests }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-9 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.MaintanaceRequestlist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="">Maintenance completion request approved by the engineering department</h3>
                            </div>
                            <div class="col-auto">
                                <!-- <div class="form-group">
                                    <select class="form-select ResortDepartment" aria-label="Default select example">
                                        <option ></option>
                                        @foreach ($ResortDepartment as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div> -->
                            </div>
                            <div class="col-auto">

                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="table-accomComplitionRequest" class="table t w-100">
                        <thead>
                            <tr>
                                <th>Requested By</th>
                                <th>Employee Name </th>
                                <th>Affected Amenity </th>
                                <th>Location </th>
                                <th>Priority</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                    </div>

                </div>
            </div>
            <div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AvailableAccommodation',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Bed Statistics</h3>
                    </div>
                    @php
                        $maleOccupied = $BedStatistics->MaleOccupiedBeds ?? 0;
                        $maleAvailable = $BedStatistics->MaleAvailableBeds ?? 0;
                        $femaleOccupied = $BedStatistics->FemaleOccupiedBeds ?? 0;
                        $femaleAvailable = $BedStatistics->FemaleAvailableBeds ?? 0;
                        $totalMaleBeds = $maleOccupied + $maleAvailable;
                        $totalFemaleBeds = $femaleOccupied + $femaleAvailable;
                        $maleOccupiedPercentage = $totalMaleBeds > 0 ? round(($maleOccupied / $totalMaleBeds) * 100, 2) : 0;
                        $femaleOccupiedPercentage = $totalFemaleBeds > 0 ? round(($femaleOccupied / $totalFemaleBeds) * 100, 2) : 0;
                        $overallOccupiedBeds = $maleOccupied + $femaleOccupied;
                        $overallTotalBeds = $totalMaleBeds + $totalFemaleBeds;
                        $overallOccupiedPercentage = $overallTotalBeds > 0 ? round(($overallOccupiedBeds / $overallTotalBeds) * 100, 2) : 0;
                    @endphp
                  <div class="two-progressbar mb-3">
                    <!-- Male Occupied -->
                    <div class="progress-container blue" data-progress="{{ $maleOccupiedPercentage }}"
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="Male Staff Occupied {{ $maleOccupiedPercentage }}%">
                        <svg class="progress-circle" viewBox="0 0 120 120">
                            <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                            <circle class="progress" cx="60" cy="60" r="54"
                                style="stroke-dashoffset: {{ 339.292 - (339.292 * $maleOccupiedPercentage / 100) }};">
                            </circle>
                        </svg>
                    </div>

                    <!-- Female Occupied -->
                    <div class="progress-container skyblue" data-progress="{{ $femaleOccupiedPercentage }}"
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="Female Staff Occupied {{ $femaleOccupiedPercentage }}%">
                        <svg class="progress-circle" viewBox="0 0 120 120">
                            <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                            <circle class="progress" cx="60" cy="60" r="54"
                                style="stroke-dashoffset: {{ 339.292 - (339.292 * $femaleOccupiedPercentage / 100) }};">
                            </circle>
                        </svg>
                    </div>

                    <!-- Overall Occupied -->
                    <div class="text">
                        <h5>{{ $overallOccupiedPercentage }}%</h5>
                        <p>Occupied Bed</p>
                    </div>
                </div>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Male Staff
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Female Staff
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-xl-5 col-md-6">
                <div class="card">
                    <div class="card-title">
                        <h3>Future Occupancy Rates</h3>
                    </div>
                    <canvas id="myLineChart"></canvas>

                </div>
            </div> -->
            <!-- <div class="col-xl-4 col-md-6">
                <div class="card card-accomTrends">
                    <div class="card-title">
                        <h3>Trends</h3>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div>
                                <h6>Recurring issues in certain accommodations</h6>
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text.</P>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div>
                                <h6>Increase in maintenance requests</h6>
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text.</P>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div>
                                <h6>Increase in maintenance requests</h6>
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text.</P>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div>
                                <h6>Recurring issues in certain accommodations</h6>
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text.</P>
                                <a href="#" class="a-linkTheme">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="col-xl-6 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AccommodationMaster',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Amenities</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select InventoryCategory"  aria-label="Default select example">
                                        <option ></option>
                                        @if($InventoryCategory->isNotEmpty())
                                            @foreach ($InventoryCategory as $s)
                                                <option value="{{$s->id}}" > {{$s->CategoryName}} </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto"><a href="{{ route('resort.accommodation.inventory')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table  table-accomAmenities w-100">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Location</th>
                                <th>Occupied </th>
                                <th>Employee</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>

                </div>
            </div>
            <div class="col-xl @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.AccommodationMaster',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-accomStati">
                    <div class="card-title ">
                        <h3>Accommodation Statistics</h3>
                    </div>
                    <div class="permissions-accordion" id="accordionPermissions">
                        @foreach ($buildings as $ak => $b)
                            @php
                                // Remove spaces and sanitize the accordion group name
                                $sanitizedGroupName = str_replace(' ', '', $ak);
                                // Set the first collapse to be open by default
                                $isFirst = $loop->first;
                            @endphp

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne{{ $sanitizedGroupName }}">
                                    <button class="accordion-button {{ $isFirst ? '' : 'collapsed' }}"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapseOne{{ $sanitizedGroupName }}"
                                            aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                                            aria-controls="collapseOne{{ $sanitizedGroupName }}">
                                        {{ $ak }}
                                    </button>
                                </h2>

                                <div id="collapseOne{{ $sanitizedGroupName }}"
                                     class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}"
                                     aria-labelledby="headingOne{{ $sanitizedGroupName }}"
                                     data-bs-parent="#accordionPermissions">
                                    <div class="accordion-body">
                                        @if(array_key_exists(0,$b))
                                        @foreach ($b[0] as $name => $d)
                                            @php
                                                $sanitizedName = str_replace(' ', '', $name);
                                                $parts = explode('/', $d);
                                                $numerator = (int) $parts[0];
                                                $denominator = (int) $parts[1];
                                                $percentage = $denominator > 0 ? ($numerator / $denominator) * 100 : 0; // Avoid division by zero
                                            @endphp

                                            <div class="d-flex mb-3">
                                                <div class="flex-grow-1">
                                                    <span>{{ $name }}</span>
                                                    <div class="progress progress-custom progress-themeskyblue">
                                                        <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%;"
                                                            aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>{{ $numerator }}/{{ $denominator }}</div>
                                            </div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                </div>
            </div>
            <!-- <div class="col-xl-6">
                <div class="card card-wiINsight card-accomWiINsight">
                    <div class="card-title">
                        <h3>WI Insight's</h3>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Wisdom suggested This room for John Doe</h6>
                                <P>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting industry
                                    Lorem typesetting industry ipsum.</P>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                    <a href="#" class="a-link">Request Leave</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Upcoming Vacancies:</h6>
                                <P>Prediction of the forthcoming vacancies based on staff leaves, contract
                                    terminations, or transfers.</P>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div> -->
            <div class="col-xl-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.MaintanaceRequestlist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">On Hold Requested </h3>
                            </div>
                            <div class="col-auto"><a href="{{ route('resort.accommodation.HoldMaintanaceRequest') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="" class="table OnHoldReq w-100">
                        <thead>
                            <tr>
                                <th>Description of Issue</th>
                                <th>Location </th>
                                <th>Date</th>
                                <th>Priority</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                    </div>

                </div>
            </div>



        </div>
    </div>
</div>
<div class="modal fade" id="RejectedRequest-Model" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Assign Task To HOD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="RejectedRequestForm">
                @csrf
                <div class="modal-body">
                    <div class="bg-themeGrayLight DetailsShow">

                    </div>
                    <div>
                        <label for="select_emp" class="form-label">Enter Reason</label>
                        <textarea class="form-control" rows="7" name="reason" name="reason"></textarea>
                    </div>
                    <input type="hidden" name="task_id" id="task_id">
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type='submit' class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="ForwardToHOD-Model" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Assign Task To HOD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ForwardToHODForm">
                @csrf
                <div class="modal-body">
                    <div class="bg-themeGrayLight DetailsShow">

                    </div>
                    <div><label for="select_emp" class="form-label">SELECT EMPLOYEE</label>
                        <select class="form-select select2t-none" name="HOD_id" id="select_emp" aria-label="Default select example">
                            <option> </option>
                            @if($Employee->isNotEmpty())

                                @foreach ($Employee as $e)
                                    <option value="{{ $e->id}}"> {{ $e->first_name}} {{ $e->last_name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <input type="hidden" name="task_id" id="task_id">
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type='submit' class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="ForwardToHOD-DetailsModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

                <div class="modal-body">
                    <div class="row">
                        <table class="table ShowInternalDetails">

                        </table>
                    </div>
                    <input type="hidden" name="task_id" id="task_id">
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="selectBed-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-selectBed">
        <div class="modal-content">
        <form id="AssignBedForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Select Bed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bg-themeGrayLight">
                <div class="row">
                    <div class="col-12">

                        <select class="form-select" name="emp_id" id="EmployeeList">
                            <option selected>Select Employee</option>
                        </select>
                    </div>
                </div>
                <br>
                <div class="row">

                    <div class="col-12">
                            <div class="bed-badge">Bed</div>
                                <div class="row g-3 AppnedBed">

                                </div>
                                <input type="hidden" name="assignId" id="assignId">
                            </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                 <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <button href="#reviewDetails-modal" data-bs-toggle="modal"
                    class="btn btn-themeBlue">Submit</button>
            </div>
        </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $("#EmployeeList").select2({
            placeholder: "Select Employee",
            allowClear: true,
        })
        // $('.data-Table').dataTable({
        //     "searching": false,
        //     "bLengthChange": false,
        //     "bFilter": true,
        //     "bInfo": false,
        //     "bAutoWidth": false,
        //     scrollX: true,
        //     "iDisplayLength": 10,
        // });
        $("#select_emp").select2({
            placeholder: "Select Employee",
            allowClear: true
        });
        $(".InventoryCategory").select2({
            placeholder: "Select Category",
            allowClear: true
        });


        $(".ResortDepartment").select2({
            placeholder: "Select Department",
            allowClear: true
        });

        $(".ResortDepartment").on("change",function()
        {
            PendingTaskList();
        });
        Aminities();
        OnHoldTaskList();
        PendingTaskList();
        AccomComplitionRequest();
        $(".InventoryCategory").on("change",function()
        {
            Aminities();
        });

        $('#RejectedRequestForm').validate({
            rules: {
                reason: {
                    required: true,
                }
            },
            messages: {
                reason: {
                    required: "Please Enter Reason.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.HrRejeactedRequest') }}", // Your route for file upload
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            form.reset();
                            PendingTaskList();
                            $("#RejectedRequest-Model").modal('hide');

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });
        $('#ForwardToHODForm').validate({
            rules: {
                HOD_id: {
                    required: true,
                }
            },
            messages: {
                HOD_id: {
                    required: "Please Select HOD.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.HrForwardToHODManitenanceRequest') }}", // Your route for file upload
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            form.reset();
                            PendingTaskList();
                            $("#ForwardToHOD-Model").modal('hide');

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });
        $('#AssignBedForm').validate({
            rules: {
                assignId: {
                    required: true,
                },
                emp_id: {
                    required: true,
                }
            },
            messages: {
                assignId: {
                    required: "Please Select Bed.",
                },
                emp_id: {
                    required: "Please Select Employee.",
                }
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") === "assignId") {
                $('.bed-block .assign-error').remove();

                // Add error message after the selected bed-block's paragraph
                if($('.bed-block.selected').length) {
                    $('.bed-block.selected').append(
                        `<div class="assign-error" style="color: red; margin-top: 5px;">${error.text()}</div>`
                    );
                } else {
                    // If no bed is selected, append to all bed-blocks
                    $('.bed-block').append(
                        `<div class="assign-error" style="color: red; margin-top: 5px;">${error.text()}</div>`
                    );
                }
            } else {
                // Default placement for other fields
                error.insertAfter(element);
            }
            },
            submitHandler: function (form, event) {
                event.preventDefault(); // Prevent form default submission

                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.AssignAccommodationToEmp') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            var employee = response.data.employee;
                            var accommodation = response.data.accommodation;
                            var facilities = accommodation.facilities.join(", ");
                            $("#selectBed-modal").modal('hide');

                            $(".appendhereAfterAssign").html(`
                                <div class="empDetails-user">
                                     <div class="img-circle"><img src="${employee.profile_picture}" alt="user">
                                    </div>
                                    <div>
                                        <h4>${employee.name}<span class="badge badge-themeNew">#34523</span></h4>
                                        <p>${employee.position}</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lable">
                                        <tr>
                                            <th>Building:</th>
                                            <td>${accommodation.building_name}</td>
                                        </tr>
                                        <tr>
                                            <th>Room No.</th>
                                            <td>${accommodation.room_no}</td>
                                        </tr>
                                        <tr>
                                            <th>Room Facilities:</th>
                                            <td>${facilities}</td>
                                        </tr>
                                        <tr>
                                            <th>Room Status:</th>
                                            <td>${accommodation.RoomStatus}</td>
                                        </tr>
                                        <tr>
                                            <th>Accommodation Name:</th>
                                            <td>${accommodation.accommodation_name}</td>
                                        </tr>
                                    </table>
                                </div>
                            `);

                            Aminities();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: "toast-bottom-right",
                            });
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function (key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    }
                });
            }
        });
    });
    $(document).on("click", ".bed-block", function () {
        // Remove the 'active' class from all bed blocks
        $(".bed-block").removeClass("active");
        $(this).addClass("active");
        $("#assignId").val($(this).data('id'));
    });

    
    $(document).on("click",".ForwardToEmployee",function()
    {
        
        var  request_id = $(this).data('request_id');
        var  child_appr_maint_req_id = $(this).data('child_appr_maint_req_id');
        $.ajax({
                    url: "{{ route('resort.accommodation.MainRequestForwordToEmp') }}", // Your route for the request
                    type: "POST",
                    data: {
                        "child_appr_maint_req_id": child_appr_maint_req_id,
                        "request_id": request_id,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) 
                    {


                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            AccomComplitionRequest();

                            $("#ForwardToHOD-Model").modal('hide');

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }

                        
                    },
                    error: function(response) {
                        let errors = response.responseJSON;
                        let errs = errors?.errors ? Object.values(errors.errors).join('<br>') : "An unexpected error occurred.";

                        // Show error SweetAlert
                        Swal.fire({
                            title: 'Error!',
                            html: errs,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });

    });
    $(document).on("click",".RejectedRequest",function()
    {

        var EffectedAmenity = $(this).attr("data-EffectedAmenity");
        var Location = $(this).attr("data-Location");

        var task_id = $(this).attr("data-req_id");

        var  row =    `<table>
                        <tr>
                            <th>Item:</th>
                            <td>${EffectedAmenity}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>${Location}</td>
                        </tr>
                    </table>`;
       $(".DetailsShow").html(row);
       $("#task_id").val(task_id);
        $("#RejectedRequest-Model").modal('show');
    });
    $(document).on("click",".ForwardToHOD",function()
    {

        var EffectedAmenity = $(this).attr("data-EffectedAmenity");
        var Location = $(this).attr("data-Location");

        var task_id = $(this).attr("data-req_id");

        var  row =    `<table>
                        <tr>
                            <th>Item:</th>
                            <td>${EffectedAmenity}</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>${Location}</td>
                        </tr>
                    </table>`;
       $(".DetailsShow").html(row);
       $("#task_id").val(task_id);
        $("#ForwardToHOD-Model").modal('show');
    });


    $(document).on("click", ".OnHoldRequest", function() {
        let task_id = $(this).data('task_id');
        let flag = $(this).data('flag');
        let msg = (flag === "On-Hold") ? 'Yes, put it on hold!' : 'Yes, close it!';

        // SweetAlert confirmation dialog with input field
        Swal.fire({
            title: 'Are you sure?',
            text: msg,
            icon: 'warning',
            input: 'textarea', // Input type for providing a reason
            inputPlaceholder: 'Enter your reason here...',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: msg,
            inputValidator: (value) => {
                if (!value) {
                    return 'Reason is required!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let reason = result.value; // Get the reason entered by the user

                // Proceed with AJAX request
                $.ajax({
                    url: "{{ route('resort.accommodation.MainRequestOnHold') }}", // Your route for the request
                    type: "GET",
                    data: {
                        "task_id": task_id,
                        "flag": flag,
                        "reason": reason,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success SweetAlert
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#3085d6'
                            });
                            OnHoldTaskList(); // Refresh task list
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || "Something went wrong.",
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: function(response) {
                        let errors = response.responseJSON;
                        let errs = errors?.errors ? Object.values(errors.errors).join('<br>') : "An unexpected error occurred.";

                        // Show error SweetAlert
                        Swal.fire({
                            title: 'Error!',
                            html: errs,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });
    // tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })


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

    //    equal heigth js
    function equalizeHeights() {
        // Get the elements
        const block1 = document.getElementById('card-chartLeave');
        const block2_1 = document.getElementById('card-upLeave');
        const block2_2 = document.getElementById('card-onLeave');

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

    function AccomComplitionRequest()
    {

        if ($.fn.dataTable.isDataTable('#table-accomComplitionRequest')) 
        {
            $('#table-accomComplitionRequest').DataTable().destroy();
        }

        var TableAccomMainten = $('#table-accomComplitionRequest').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[8,'desc']],
            ajax: {
                url: '{{ route("resort.accommodation.HraccomComplitionRequest") }}',
                type: 'GET',
                data: function (d) {
                    d.ResortDepartment = $(".ResortDepartment").val();
                }
            },
            columns: [
                { data: 'RequestedBy', name: 'RequestedBy', className: 'text-nowrap' },
                { data: 'EmployeeName', name: 'EmployeeName', className: 'text-nowrap' },
                { data: 'EffectedAmenity', name: 'EffectedAmenity', className: 'text-nowrap' },
                { data: 'Location', name: 'Location', className: 'text-nowrap' },
                { data: 'Priority', name: 'Priority', className: 'text-nowrap' },
                { data: 'Date', name: 'Date', className: 'text-nowrap' },
                { data: 'Status', name: 'Status', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
    
    function PendingTaskList()
    {
        if ($.fn.dataTable.isDataTable('.table-accomMainten')) {
            $('.table-accomMainten').DataTable().destroy();
        }

        var TableAccomMainten = $('.table-accomMainten').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[8,'desc']],
            ajax: {
                url: '{{ route("resort.accommodation.HrGetMaintananceRequest") }}',
                type: 'GET',
                data: function (d) {
                    d.ResortDepartment = $(".ResortDepartment").val();
                }
            },
            columns: [
                { data: 'RequestedBy', name: 'RequestedBy', className: 'text-nowrap' },
                { data: 'EffectedAmenity', name: 'EffectedAmenity', className: 'text-nowrap' },
                { data: 'Location', name: 'Location', className: 'text-nowrap' },
                { data: 'Priority', name: 'Priority', className: 'text-nowrap' },
                { data: 'AssgingedStaff', name: 'AssgingedStaff ', className: 'text-nowrap' },
                { data: 'Date', name: 'Date', className: 'text-nowrap' },
                { data: 'Status', name: 'Status', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });
    }


    function OnHoldTaskList()
    {
        @php
            $permission = true;
            if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.HoldMaintanaceRequest',config('settings.resort_permissions.view')) == false){
                $permission = false;
            }
        @endphp
        if ($permission == false) {
            return;
        }
        if ($.fn.dataTable.isDataTable('.OnHoldReq'))
        {
            $('.OnHoldReq').DataTable().destroy();
        }

        var TableAccomMainten = $('.OnHoldReq').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[6,'desc']],
                ajax: {
                    url: '{{ route("resort.accommodation.HoldMaintanaceRequest") }}',
                    type: 'GET',
                    data: function (d)
                    {
                        d.Search =$(".Search").val();
                    }
                },
                columns: [
                    { data: 'descriptionIssues', name: 'descriptionIssues', className: 'text-nowrap' },
                    { data: 'Location', name: 'Location', className: 'text-nowrap' },
                    { data: 'Date', name: 'Date', className: 'text-nowrap' },

                    { data: 'Priority', name: 'Priority', className: 'text-nowrap' },
                    { data: 'ReasonOnHold', name: 'ReasonOnHold ', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                     {data:'created_at',visible:false,searchable:false},
                ]
            });
    }

    function Aminities()
    {
        if ($.fn.dataTable.isDataTable('.table-accomAmenities'))
        {
            $('.table-accomAmenities').DataTable().destroy();
        }

        var TableAccomMainten = $('.table-accomAmenities').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("resort.accommodation.Aminities") }}',
                    type: 'GET',
                    data: function (d)
                    {
                        d.InventoryCategory =$(".InventoryCategory").val();
                    }
                },
                columns: [

                    { data: 'ItemName', name: 'Date', className: 'text-nowrap' },

                    { data: 'Location', name: 'ReasonOnHold ', className: 'text-nowrap' },
                    { data: 'Occupied', name: 'Priority', className: 'text-nowrap' },

                    { data: 'Employee', name: 'action', orderable: false, searchable: false },
                ]
            });
    }

    $(document).on("click",".AssingToRoom",function()
    {
        var RoomType = $(this).data('roomtype');
        var available_a_id = $(this).data('id');
        console.log(RoomType,available_a_id);
        $.ajax({
            url: "{{ route('resort.accommodation.GetAccmmodationwiseEmployee') }}", // Your route for file upload
            type: "POST",
            data: {"_token":"{{ csrf_token() }}","RoomType": RoomType,"available_a_id":available_a_id},
            success: function(response)
            {
                if (response.success)
                {
                    var row = '';
                    var option = '<option></option>';
                    $.each(response.Employees, function (i, v)
                    {
                        option +=`<option value='${v.EmployeeId}'>${v.first_name}  ${v.last_name}</option>`;
                    });
                    $("#EmployeeList").html(option);
                            $.each(response.AssingAccommodation, function (i, v)
                            {

                                    row +=`<div class="col-6">
                                                <div class="bed-block" data-id='${v.id}'>
                                                    <div class="position-relative">
                                                        <img src="{{ URL::asset('resorts_assets/images/bed-active.png') }}" alt="bed">
                                                        <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" class="icon" alt="icon">
                                                        <div class="img-circle" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                            title="Christian Slatter"><img src="{{ URL::asset('resorts_assets/images/user-2.svg') }}" alt="user">
                                                        </div>
                                                    </div>
                                                    <p>Available</p>
                                                </div>
                                            </div>`;

                            });

                            $(".AppnedBed").html(row);
                            $('[data-bs-toggle="tooltip"]').tooltip();


                    $("#selectBed-modal").modal('show');

                    toastr.success(response.message, "success", {
                        positionClass: "toast-bottom-right",
                    });
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: "toast-bottom-right",
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
</script>
<!-- <script type="module">
    var ctx = document.getElementById('myLineChart').getContext('2d');
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Aug 2024', 'Sep 2024', 'Oct 2024', 'Nov 2024', 'Dec 2024'], // X-axis labels
            datasets: [
                {
                    label: 'Occupancy Rates',
                    data: [7, 20, 17, 22, 35],
                    borderColor: '#014653',
                    backgroundColor: '#014653',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4, // Creates smooth curves
                    // cubicInterpolationMode: 'monotone', // Monotone interpolation
                    pointRadius: 0 // Remove dots
                },]
        },
        options: {
            plugins: {
                doughnutLabelsInside: true, // Enable the custom plugin
                legend: {
                    display: false
                }
            },
            layout: {
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 0,
                    right: 0
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Hide the x-axis border
                    }
                },
                y: {
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    },
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5,
                    }
                }
            }
        }

    });

</script> -->
@endsection

