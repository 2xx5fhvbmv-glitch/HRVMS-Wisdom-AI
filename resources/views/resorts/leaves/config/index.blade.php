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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Leave</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-xxl-6 col-lg-6 ">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-title">
                                    <h3>Upload Past Attendance Data</h3>
                                </div>
                                <form method="POST" action="{{ route('leave.export') }}" class="mb-4">
                                    @csrf
                                    <div class="row g-2 mb-3">
                                        <div class="col-12">
                                            <select class="form-select select2t-none mb-2 ResortDivision" id="division" name="division" aria-label="Default select example">
                                                <option  selected >Select Division</option>
                                                @if($resort_divisions->isNotEmpty())
                                                    @foreach($resort_divisions as $resort_division)
                                                        <option value="{{ $resort_division->id }}">{{ $resort_division->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select select2t-none mb-2 Department" id="department" name="department" aria-label="Default select example">
                                                <option selected value="">Select Department</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select select2t-none mb-2 Section" id="section" name="section" aria-label="Default select example">
                                                <option selected value="">Select Section</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select select2t-none mb-2 Position" id="position" name="position" aria-label="Default select example">
                                                <option selected value="">Select Position</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-themeBlue">Export Employees</button>
                                    </div>
                                </form>

                                <!-- File Upload Section -->
                                <form id="ImportLeaveDataForm">
                                    @csrf
                                    <div class="col-auto">
                                        <div class="uploadFile-btn me-0">
                                            <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                                onclick="document.getElementById('UploadImportleave').click();">
                                                Upload File
                                            </a>
                                            <input type="file" id="UploadImportleave" name="UploadImportleave"
                                                accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;"
                                                onchange="displayImportFileName()">
                                            <div id="fileNameImportFile" style="margin-top: 10px; color: #333;"></div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button type="submit" class="btn btn-themeBlue" id="ImportLeaveDataFormSubmit">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-title mb-0">
                                    <h3>Transportation Options</h3>
                                </div>
                                <form action="{{ route('submit.transportation') }}" method="POST">
                                    <div class="my-3">

                                        @if($transportations)
                                        @csrf
                                            @foreach($transportations as $key => $option)

                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" id="inlineCheckbox{{ $key }}"
                                                            value="{{ $option }}" @if(in_array($option, $savedOptions)) checked @endif  name="transportation_options[]">
                                                        <label class="form-check-label" for="inlineCheckbox{{ $key }}">{{ $option }}</label>
                                                    </div>

                                            @endforeach
                                            </div>
                                            <div class="card-footer text-end">
                                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                            </div>

                                    @else
                                        <p>No transportation options available.</p>
                                    @endif

                                </form>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-title">
                                    <h3>Occupancy Percentage</h3>
                                </div>
                                <form id="AddbulkoccupancyForm">
                                    @csrf
                                    <div class="row g-md-4 g-2 mb-3">
                                        <div class="col-sm-6">
                                            <div class="dateRangeAb"  id="datapicker">
                                                <div>
                                                    <!-- Hidden input field to attach the calendar to -->
                                                    <input type="text" class="form-control" name="hiddenInput" id="hiddenInput">
                                                </div>
                                                <p id="startDate" class="d-none">Start Date:</p>
                                                <p id="endDate" class="d-none">End Date:</p>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <input type="number" min="0" class="form-control occupancyinPer"  name="occupancyinPer" placeholder="Add Occupancy In %">
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-12 ">
                            <div class="card">
                                <div class="card-title">
                                    <div class="row g-3 align-items-center justify-content-between">
                                        <div class="col-auto">
                                            <div class="d-flex justify-content-start align-items-center">
                                                <h3>Ticket Booking Agents</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form id="ConfigAgent">
                                    @csrf
                                    <div class="row align-items-top  g-3 mb-3">
                                        <div class="col-lg-4">
                                            <label for="name" class="form-label">Agent Name <span class="red-mark">*</span></label>
                                            <div class="ticketBook-form ">
                                                <input type="text" name="name" class="form-control" placeholder="Add Name" />
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label">Agent Email <span class="red-mark">*</span></label>
                                            <div class="ticketBook-form ">
                                                <input type="text" name="agents_email" class="form-control" placeholder="Add Email Address" />
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <button  class="btn btn-themeSkyblue AddAgent mt-3">Submit</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="row g-1 ticketBook-list">
                                    <div class="col-12">
                                        <table class="table table-sm AgentTicket"  id="AgentTicket">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Email</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-lg-6 ">
                    <div class="card">
                        <div class="card-title">
                            <h3>Leave Categories</h3>
                        </div>
                        <div class="row g-xxl-4 g-3" id="leaveCategoriesContainer">
                            @if($LeaveCategories)
                                @foreach($LeaveCategories as $category)
                                    <div class="col-xxl-4 col-lg-6 col-md-4 col-sm-6">
                                        <div class="leaveCate-block themeDiffColor-block" style="background-color: {{ $category->color.'14' }};border-color:{{ $category->color}}">
                                            <div class="d-flex">
                                                <h6 class="themeDiffColor" style="color: {{ $category->color}}">{{$category->leave_type}}</h6>
                                                <div class="d-flex align-items-center">
                                                    <a href="#editLeave-modal"
                                                    data-bs-toggle="modal"
                                                    data-leave-id="{{ $category->id }}"
                                                    data-leave-type="{{ $category->leave_type }}"
                                                    data-number-of-days="{{ $category->number_of_days }}"
                                                    data-carry-forward="{{ $category->carry_forward }}"
                                                    data-carry-max="{{ $category->carry_max ?? '' }}"
                                                    data-earned-leave="{{ $category->earned_leave }}"
                                                    data-earned-max="{{ $category->earned_max ?? '' }}"
                                                    data-eligibility="{{ $category->eligibility }}"
                                                    data-frequency="{{ $category->frequency }}"
                                                    data-number-of-times="{{ $category->number_of_times }}"
                                                    data-color="{{ $category->color }}"
                                                    data-combine-with-other="{{ $category->combine_with_other }}"
                                                    data-leave-category="{{ $category->leave_category }}">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                    </a>
                                                    <a href="#" data-leave-id="{{ $category->id }}" class="ms-md-2 ms-1 delete-leave-btn"><img src="{{ URL::asset('resorts_assets/images/trash-red.svg')}}" alt="icon"></a>
                                                </div>
                                            </div>
                                            <p>{{$category->number_of_days}} Days</p>
                                            <p>Forwarded Next Year - {{$category->carry_max ?? 0}} Days</p>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            <div class="col">
                                <div class="addDash-block">
                                    <i class="fa-regular fa-plus"></i>
                                    <h6>Add Leave Category</h6>
                                </div>
                            </div>
                        </div>

                        <form id="leave-category-form" name="leave-category-form">
                            <div class="leaveAdd-block bg-themeGrayLight mt-3 d-none">
                                <div class="row g-xxl-4 g-3 align-items-end">
                                    <div class="col-sm-6">
                                        <label for="leaveType" class="form-label">LEAVE TYPE <span class="red-mark">*</span></label>
                                        <input type="text" name="leave_type" id="leaveType" class="form-control alpha-only" placeholder="Leave Type" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                        data-parsley-validate-script-message="Script tags are not allowed."/>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="number_of_days" class="form-label">NO OF DAYS <span class="red-mark">*</span></label>
                                        <input type="number" id="number_of_days" name="number_of_days" class="form-control" placeholder="NO OF DAYS" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                        data-parsley-validate-script-message="Script tags are not allowed.">
                                    </div>
                                    <div class="col-xl-6 col-lg-12 col-sm-6">
                                        <label for="" class="form-label">CARRY FORWARD NEXT YEAR <span class="red-mark">*</span></label>
                                        <div class="row g-md-3 g-2 align-items-center">
                                            <div class="col-auto">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="carry_forward" id="carryYes" value="1">
                                                    <label class="form-check-label" for="carryYes">Yes</label>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="carry_forward" id="carryNO" value="0" checked>
                                                    <label class="form-check-label" for="carryNO">No</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <input type="text" class="form-control" name="carry_max" id="carry_max" placeholder="Max No." disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-12 col-sm-6">
                                        <label for="" class="form-label">EARNED LEAVE <span class="red-mark">*</span></label>
                                        <div class="row g-md-3 g-2 align-items-center">
                                            <div class="col-auto">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="earned_leave" id="earnedYes" value="1">
                                                    <label class="form-check-label" for="earnedYes">Yes</label>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="earned_leave" id="earnedNO" value="0" checked>
                                                    <label class="form-check-label" for="earnedNO">No</label>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <input type="text" class="form-control" name="earned_max" id="earned_max" placeholder="Max Earned." disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="eligibility" class="form-label">ELIGIBILITY <span class="red-mark">*</span></label>
                                        <select class="form-select select2t-none" name="eligibility[]" multiple="multiple" data-parsley-required="true" data-parsley-errors-container="#eligibility-error">
                                            <option value="">Select Eligibility</option>
                                            @if(!empty($eligibilty))
                                                @foreach ($eligibilty as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="eligibility-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="frequency" class="form-label">FREQUENCY <span class="red-mark">*</span></label>
                                        <select class="form-select select2t-none"  name="frequency" aria-label="Default select example" data-parsley-required="true"
                                        data-parsley-errors-container="#frequency-error" >
                                            <option value="">Select Frequency</option>
                                            <option value="Weekly">Weekly</option>
                                            <option value="Monthly">Monthly</option>
                                            <option value="Quarterly">Quarterly</option>
                                            <option value="Yearly">Yearly</option>
                                        </select>
                                        <div id="frequency-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="number_of_times" class="form-label">NO. OF TIMES <span class="red-mark">*</span></label>
                                        <input type="number" id="number_of_times" name="number_of_times" placeholder="NO. OF TIMES" class="form-control" required data-parsley-validate-script
                                        data-parsley-validate-script-message="Script tags are not allowed."/>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="inputCustom-color"> Color Theme
                                            <input type="color" name="color" id="color" value="#A264F7" style="top:50%"  data-parsley-required="true"
                                            data-parsley-errors-container="#color-error" >
                                        </div>
                                        <div id="color-error"></div>
                                    </div>
                                    @if(count($LeaveCategories ?? []) >= 1)
                                        <div class="col-sm-6">
                                            <label for="combine_with_other" class="form-label">IS THIS LEAVE COMBINES WITH ANOTHER?</label>
                                            <select class="form-select select2t-none" name="combine_with_other" id="combine_with_other" aria-label="Default select example">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-6">
                                            <label for="leave_category" class="form-label">SELECT LEAVE CATEGORY </label>
                                            <select class="form-select select2t-none"
                                                    aria-label="Default select example"
                                                    name="leave_category"
                                                    id="leave_category"
                                                    data-parsley-required-if="#combine_with_other"
                                                    data-parsley-error-message="Please select a leave category if combining with another."
                                                    data-parsley-errors-container="#leave_category-error" disabled>
                                                <option value="">Select Leave Category</option>
                                                @if($LeaveCategories)
                                                    @foreach($LeaveCategories as $leaves)
                                                        <option value="{{$leaves->id}}">{{$leaves->leave_type}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="leave_category-error"></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-footer mt-4 text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm" id="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- modal -->
<div class="modal fade" id="editLeave-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <form id="edit-leave-category" name="edit-leave-category">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-xxl-4 g-3 align-items-end">
                        <input type="hidden" name="leave_id" id="leave_id" />
                        <div class="col-sm-6">
                            <label for="leaveType" class="form-label">LEAVE TYPE <span class="red-mark">*</span></label>
                            <input type="text" name="leave_type" id="leaveType" class="form-control alpha-only" placeholder="Leave Type" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script data-parsley-validate-script-message="Script tags are not allowed."/>
                        </div>
                        <div class="col-sm-6">
                            <label for="noDay" class="form-label">NO OF DAYS <span class="red-mark">*</span></label>
                            <input type="number" id="number_of_days" name="number_of_days" class="form-control" placeholder="NO OF DAYS" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                            data-parsley-validate-script-message="Script tags are not allowed.">
                        </div>
                        <div class="col-sm-6">
                            <label for="" class="form-label">CARRY FORWARD NEXT YEAR <span class="red-mark">*</span></label>
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="carry_forward" id="carryYes" value="1">
                                        <label class="form-check-label" for="carryYes">Yes</label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="carry_forward" id="carryNO" value="0" checked>
                                        <label class="form-check-label" for="carryNO">No</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="carry_max" id="carry_max" placeholder="Max No." disabled>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="" class="form-label">EARNED LEAVE <span class="red-mark">*</span></label>
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="earned_leave" id="earnedYes" value="1">
                                        <label class="form-check-label" for="earnedYes">Yes</label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="earned_leave" id="earnedNO" value="0" checked>
                                        <label class="form-check-label" for="earnedNO">No</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" name="earned_max" id="earned_max" placeholder="Max Earned." disabled>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="eligibility" class="form-label">ELIGIBILITY <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" name="eligibility[]" multiple="multiple" data-parsley-required="true" data-parsley-errors-container="#eligibility-error">
                            <option value="">Select Eligibility</option>
                                @if(!empty($eligibilty))
                                    @foreach ($eligibilty as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div id="eligibility-error"></div>
                        </div>
                        <div class="col-sm-6">
                            <label for="frequency" class="form-label">FREQUENCY <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none"  name="frequency" aria-label="Default select example" data-parsley-required="true" data-parsley-errors-container="#frequency-error" >
                                <option value="">Select Frequency</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Monthly">Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                                <option value="Yearly">Yearly</option>
                            </select>
                            <div id="frequency-error"></div>
                        </div>
                        <div class="col-sm-6">
                            <label for="noTimes" class="form-label">NO. OF TIMES <span class="red-mark">*</span></label>
                            <input type="number" id="number_of_times" name="number_of_times" placeholder="NO. OF TIMES" class="form-control" required data-parsley-validate-script
                            data-parsley-validate-script-message="Script tags are not allowed."/>
                        </div>
                        <div class="col-sm-6">
                            <div class="inputCustom-color"> Color Theme
                                <input type="color" name="color" id="color" value="#A264F7" style="top:50%"  data-parsley-required="true"
                                data-parsley-errors-container="#color-error" >
                            </div>
                            <div id="color-error"></div>
                        </div>
                        @if(!empty($LeaveCategories) && count($LeaveCategories) >= 1)
                            <div class="col-sm-6">
                                <label for="leaveAnother" class="form-label">IS THIS LEAVE COMBINES WITH
                                    ANOTHER?</label>
                                <select class="form-select select2t-none" name="combine_with_other" aria-label="Default select example">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="laeveCat" class="form-label">LEAVE CATEGORY <span class="red-mark">*</span></label>
                                <select name="leave_category" id="laeveCat" class="form-label select2t-none" data-parsley-required-if="#combine_with_other" data-parsley-error-message="Please select a leave category if combining with another." disabled>
                                    <option value="">Select Leave Category</option>
                                    @if($LeaveCategories)
                                        @foreach($LeaveCategories as $leaves)
                                            <option value="{{$leaves->id}}">{{$leaves->leave_type}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue btn-sm" id="submit">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        color: #dc3545;
        display: block;
        margin-top: 5px;
    }
</style>
@endsection

@section('import-scripts')
<script>
    // new DataTable('#example');
    $(document).ready(function () {
        AgentTicket();
        // Initialize Select2 for dropdowns with placeholders
        $("#division").select2({ placeholder: "Select Division" });
        $(".Department").select2({ placeholder: "Select Department" });
        $(".Section").select2({ placeholder: "Select Section" });
        $(".Position").select2({ placeholder: "Select Position" });

        // Handle Division change event
        $(document).on('change', '.ResortDivision', function () {
            const divisionId = $(this).val();

            $.ajax({
                url: "{{ route('resort.get.ResortDivision') }}",
                type: "POST",
                data: { division_id: divisionId },
                success: function (data) {
                    $(".Department").html('<option value="">Select Department</option>'); // Reset Department dropdown

                    if (data.success) {
                        let departmentOptions = '<option value="" selected ></option>';
                        $.each(data.data, function (key, value) {
                            departmentOptions += `<option value="${value.id}">${value.name}</option>`;
                        });

                        $(".Department").html(departmentOptions);
                        $(".Department").select2({ placeholder: "Select Department" });
                    } else {
                        toastr.warning("No Departments found for the selected Division.", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error("Error fetching Departments.", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Handle Department change event
        $(document).on('change', '.Department', function () {
            const deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "POST",
                data: { deptId: deptId },
                success: function (response) {
                    $(".Position").html('<option value="">Select Position</option>'); // Reset Position dropdown
                    $(".Section").html('<option value="">Select Section</option>'); // Reset Section dropdown

                    if (response.success) {
                        let positionOptions = '<option value=""></option>';
                        $.each(response.data.ResortPosition, function (key, value) {
                            positionOptions += `<option value="${value.id}">${value.position_title}</option>`;
                        });
                        $(".Position").html(positionOptions);

                        let sectionOptions = '<option value=""></option>';
                        $.each(response.data.ResortSection, function (key, value) {
                            sectionOptions += `<option value="${value.id}">${value.name}</option>`;
                        });
                        $(".Section").html(sectionOptions);
                        $(".Section").select2({ placeholder: "Select Section" });
                        $(".Position").select2({ placeholder: "Select Position" });



                    } else {
                        toastr.warning("No Positions or Sections found for the selected Department.", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error("Error fetching Positions and Sections.", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Ensure Parsley is loaded
        if (typeof $.fn.parsley !== 'function') {
            console.error('Parsley.js is not loaded correctly');
            return;
        }
        // Initialize the entire form with Parsley
        var $form = $("#leave-category-form");
        $form.parsley({
            excluded: 'input[type=button], input[type=submit], input[type=reset]',
            trigger: 'change',
            successClass: 'is-valid',
            errorClass: 'is-invalid'
        });

        // Initialize Select2
        $('.select2t-none').select2({
            allowClear: true,
            closeOnSelect: false
        });

        // Manually trigger Parsley validation when Select2 changes
        $(".select2t-none").on('change', function () {
            var parsleyField = $(this).parsley();
            parsleyField.validate();

            // Add/remove the error class to the Select2 container based on validation
            if (parsleyField.isValid()) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            } else {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });

        // Parsley field validation handler
        window.Parsley.on('field:validated', function (fieldInstance) {
            var $element = fieldInstance.$element;
            if ($element.hasClass('select2t-none')) {
                // Update the Select2 container's appearance
                var $select2Container = $element.next('.select2-container').find('.select2-selection');
                if (fieldInstance.isValid()) {
                    $select2Container.removeClass('is-invalid');
                } else {
                    $select2Container.addClass('is-invalid');
                }
            }
        });

        $(".addDash-block").click(function () {
            $(".leaveAdd-block ").removeClass("d-none");
        });

        $('input[name="carry_forward"]').on('change', function () {
            if ($(this).val() === '1') {
                // If "Yes" is selected, enable the textbox
                $('#carry_max').prop('disabled', false).focus();
            } else {
                // If "No" is selected, disable the textbox and clear its value
                $('#carry_max').prop('disabled', true).val('');
            }
        });

        $('input[name="earned_leave"]').on('change', function () {
            if ($(this).val() === '1') {
                // If "Yes" is selected, enable the textbox
                $('#earned_max').prop('disabled', false).focus();
            } else {
                // If "No" is selected, disable the textbox and clear its value
                $('#earned_max').prop('disabled', true).val('');
            }
        });

        $('#combine_with_other').on('change', function () {
            if ($(this).val() === '1') {
                // If "Yes" is selected, enable the textbox and add required validation
                $('#leave_category')
                    .prop('disabled', false)
                    .attr('required', true) // Add required attribute
                    .focus();
            } else {
                // If "No" is selected, disable the textbox, clear its value, and remove validation
                $('#leave_category')
                    .prop('disabled', true)
                    .removeAttr('required') // Remove required attribute
                    .val(''); // Clear the value
            }
        });


        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('center-id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed)
                {
                    $.ajax({
                        type: "delete",
                        url: "{{ route('resort.ta.destroyAgentList','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            $row.remove();

                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            $('#positions-table').DataTable().ajax.reload();
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on('click', '.delete-leave-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('leave-id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('leaves.category.delete', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#leaveCategoriesContainer').html(result.leaveCategoriesHtml);
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on("click", "#AgentTicket .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            // Extract division ID
            var agentId = $(this).data('agent-id');

            var currentName = $row.find("td:nth-child(1)").text().trim();
            var currentEmail = $row.find("td:nth-child(2)").text().trim();

            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control name" value="${currentName}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="email" class="form-control email" value="${currentEmail}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="#" class="btn btn-theme update-row-btn_agent" data-agent-id="${agentId}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
        });

        $(document).on("click", "#AgentTicket .update-row-btn_agent", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var agentId = $(this).data('agent-id');
            var updatedName = $row.find("input").eq(0).val();
            var updatedEmail = $row.find("input").eq(1).val();

            $.ajax({
                url: "{{ route('resort.ta.inlineUpdateAgent', '') }}/" + agentId,
                type: "PUT",
                data: {
                    name : updatedName,
                    agents_email : updatedEmail,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key
                        // Update the row with new values
                        var updatedRowHtml = `
                            <td class="text-nowrap">${updatedName}</td>
                            <td class="text-nowrap">${updatedEmail}</td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-agent-id="${agentId}">
                                        <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                    <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-agent-id="${agentId}">
                                        <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </td>
                        `;

                        $row.html(updatedRowHtml);

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });


        });

        $(document).on("click", 'a[data-bs-toggle="modal"]', function () {
            const leaveId = $(this).data("leave-id");
            const leaveType = $(this).data("leave-type");
            const numberOfDays = $(this).data("number-of-days");
            const carryForward = $(this).data("carry-forward");
            const carryMax = $(this).data("carry-max");
            const earnedLeave = $(this).data("earned-leave");
            const earnedMax = $(this).data("earned-max");
            const frequency = $(this).data("frequency");
            const numberOfTimes = $(this).data("number-of-times");
            const color = $(this).data("color");
            const combineWithOther = $(this).data("combine-with-other");
            const leaveCategory = $(this).data("leave-category");
            var eligibilityValues = $(this).data('eligibility');

            // Convert the string into an array
            if (eligibilityValues) {
                eligibilityValues = eligibilityValues.toString().split(',');
            } else {
                eligibilityValues = []; // Handle null or empty values
            }

            // Populate modal fields
            $('#editLeave-modal #leaveType').val(leaveType);
            $('#editLeave-modal #number_of_days').val(numberOfDays);
            $('#editLeave-modal #carry_max').val(carryMax).prop('disabled', carryForward !== 1);
            $('#editLeave-modal #earned_max').val(earnedMax).prop('disabled', earnedLeave !== 1);
            $('#editLeave-modal input[name="carry_forward"][value="' + carryForward + '"]').prop('checked', true);
            $('#editLeave-modal input[name="earned_leave"][value="' + earnedLeave + '"]').prop('checked', true);
            $('#editLeave-modal select[name="eligibility[]"]').val(eligibilityValues).trigger('change');
            $('#editLeave-modal select[name="frequency"]').val(frequency).trigger('change');
            $('#editLeave-modal #number_of_times').val(numberOfTimes);
            $('#editLeave-modal #color').val(color);
            $('#editLeave-modal select[name="combine_with_other"]').val(combineWithOther).trigger('change');
            $('#editLeave-modal #leave_category').val(leaveCategory);
            $('#editLeave-modal #leave_id').val(leaveId);


        });

        $(document).on('submit', '#edit-leave-category', function (e) {
            e.preventDefault();

            // Create FormData object
            var formData = new FormData(this);

            // Debugging: Check if FormData is populated
            for (var pair of formData.entries()) {
                console.log(pair[0], pair[1]); // Log each key-value pair
            }

            // Get the ID of the leave category to update
            var categoryId = $('#leave_id').val();

            // Construct the URL dynamically
            var updateUrl = "{{ route('leaves.category.update', ':id') }}".replace(':id', categoryId);

            // Make the AJAX request
            $.ajax({
                url: updateUrl,
                type: 'POST', // Laravel accepts PUT via POST with _method override
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    location.reload();
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    toastr.error(error, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $('#ImportLeaveDataForm').on('submit', function(e) {
            e.preventDefault();

            var fileInput = $('#UploadImportleave');
            var filePath = fileInput.val();
            var allowedExtensions = /(\.csv|\.xls|\.xlsx|\.ods|\.xlsb|\.xlt|\.xltx|\.xltm)$/i;

            if (!filePath) {
                toastr.error("Please select a file to upload.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return false;
            }

            if (!allowedExtensions.exec(filePath)) {
            toastr.error("Please upload a valid file (csv, xls, xlsx, ods, xlsb, xlt, xltx, xltm).", "Error", {
                positionClass: 'toast-bottom-right'
            });
            fileInput.val(''); // Clear the input
            return false;
            }

            var formData = new FormData(this);

            $.ajax({
            url: "{{ route('leave.import') }}", // Your route for file upload
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
                $('#ImportLeaveDataForm')[0].reset(); // Reset the form
                $('#fileNameImportFile').text(''); // Clear the file name display
                } else {
                toastr.error(response.message, "Error", {
                    positionClass: 'toast-bottom-right'
                });
                }
            },
            error: function(response) {
                if (response.responseJSON) {
                var errors = response.responseJSON.errors || {};
                var errorMessages = Object.values(errors).flat().join('<br>');
                toastr.error(errorMessages || "An error occurred.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                }
            }
            });
        });

        $.validator.addMethod("noSpecialChars", function(value, element) {
            return this.optional(element) || /^[a-zA-Z\s]*$/.test(value);
        }, "Name should only contain letters and spaces.");

        $('#ConfigAgent').validate({
                rules: {
                    agents_email: {
                        required: true,
                        email: true,
                        maxlength: 100,
                        minlength: 5,

                    },
                    name:{
                        required: true,
                        maxlength: 50,
                        minlength: 1,
                        noSpecialChars: true

                    }

                },
                messages: {
                    agents_email: {
                        required: "Please enter email address.",
                        email: "Please enter a valid email address.",
                        maxlength: "Email cannot be longer than 100 characters",
                        minlength: "Email must be at least 5 characters long"
                    },
                    name :{
                        required: "Please Name .",
                    }
                },
            submitHandler: function(form) {
                var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                var email = $(form).find('[name="agents_email"]').val();

                if (!emailRegex.test(email)) {
                    toastr.error("Invalid email format", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return false;
                }
                var formData = new FormData(form); // Use FormData to include file

                $.ajax({
                    url: "{{ route('resort.ta.add.Agent') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {

                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            AgentTicket();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        console.log(errors);
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {  positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });

       function AgentTicket()
       {

        if ($.fn.DataTable.isDataTable('#AgentTicket'))
        {
            $('#AgentTicket').DataTable().clear().destroy();
        }
            $('#AgentTicket tbody').empty();
        var AgentTicket = $('#AgentTicket').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("resort.ta.getResortAgentTicketList") }}',
                type: 'GET',
                data: function(d) {
                    d.flag = 'Leave'; // Add your custom parameter here
                }
            },
            columns: [
                { data: 'name', name: 'name', className: 'text-nowrap' },
                { data: 'Email', name: 'Email', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false }
            ]
        });
       }

         // Initialize date range picker

        // $('.datepicker').datepicker({});
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment().add(7, 'days'),
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: {
                format: "DD-MM-YYYY", // Ensure the format matches your date parsing logic
            }
        });

        $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
            $("#startDate").text("Start Date: " + picker.startDate.format("DD-MM-YYYY"));
            $("#endDate").text("End Date: " + picker.endDate.format("DD-MM-YYYY"));
        });

        // Form validation using jQuery Validate

        $('#AddbulkoccupancyForm').validate({
            rules: {
                occupancyinPer: {
                    required: true,
                    number: true,
                    min: 0,
                    max: 100
                }
            },
            messages: {
                occupancyinPer: {
                    required: "Please enter the occupancy percentage.",
                    number: "Please enter a valid number.",
                    min: "Occupancy percentage cannot be negative.",
                    max: "Occupancy percentage must be less than or equal to 100."
                }
            },
            submitHandler: function(form) {
                // Extract date range and occupancy percentage
                var dateRange = $("#hiddenInput").val();
                var occupancyPercentage = $('input[name="occupancyinPer"]').val();


                // Split the date range into start and end dates
                var dates = dateRange.split(' - ');
                var startDate = moment(dates[0], "DD-MM-YYYY");
                var endDate = moment(dates[1], "DD-MM-YYYY");

                // Generate all dates within the range
                let enabledDates = [];
                while (startDate <= endDate) {
                    enabledDates.push(startDate.format("YYYY-MM-DD")); // Store formatted date
                    startDate.add(1, 'days');
                }

                // Prepare data for the AJAX request
                var formData = {
                    _token: $('input[name="_token"]').val(),
                    dates: enabledDates, // Array of all dates
                    occupancy_percentage: occupancyPercentage
                };
                console.log(formData);

                // Send the data to the server via AJAX
                $.ajax({
                    url: "{{ route('resort.bulk-occupancy.store') }}", // Adjust to your route
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            console.log(response);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#AddbulkoccupancyForm').get(0).reset();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON.errors;
                        var errorMessages = '';
                        $.each(errors, function(key, error) {
                            errorMessages += error + '<br>';
                        });
                        toastr.error(errorMessages, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });
    });

    function displayImportFileName() {
        var fileInput = document.getElementById('UploadImportleave');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';

        // Display the file name
        document.getElementById('fileNameImportFile').innerText = fileName;

        if(fileName != 'No file selected') {
            document.getElementById('ImportLeaveDataFormSubmit').click();
        }else {
            toastr.error("Please select a file to upload.", "Error", {
                positionClass: 'toast-bottom-right'
            });
        }

    }

    function initSelect2AndValidation() {
        if ($.fn.select2 && $.fn.parsley) {
            // Initialize Select2
            $(".select2t-none").select2({ allowClear: true,
                closeOnSelect: false});

            // Add Parsley validation specifically for Select2
            $(".select2t-none").on('change', function() {
                $(this).parsley().validate();
            });

            // Ensure Select2 trigger changes in Parsley
            $(".select2t-none").on('select2:select', function() {
                $(this).trigger('change');
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        function initSelect2AndValidation() {
            if ($.fn.select2 && $.fn.parsley) {
                // Initialize Select2
                $(".select2t-none").select2();

                // Add Parsley validation specifically for Select2
                $(".select2t-none").on('change', function() {
                    $(this).parsley().validate();
                });

                // Ensure Select2 trigger changes in Parsley
                $(".select2t-none").on('select2:select', function() {
                    $(this).trigger('change');
                });
            }
        }

        // Initialize Parsley Validation
        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#leave-category-form').parsley({
                    errorClass: 'is-invalid',
                    successClass: 'is-valid',
                    errorsWrapper: '<div class="invalid-feedback"></div>',
                    errorTemplate: '<div></div>',
                    trigger: 'change'
                });

                window.Parsley.addValidator('validateScript', {
                    validateString: function(value) {
                        // Pattern to match any <script> tags, even with attributes or content
                        const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                        return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
                    },
                    messages: {
                        en: 'Script tags are not allowed.'
                    }
                });

                window.Parsley.addValidator('requiredIf', {
                    requirementType: 'string',
                    validateString: function (value, selector) {
                        var relatedField = $(selector); // Get the related field
                        console.log(relatedField);
                        if (!relatedField.length) {
                            return true; // If the related field is not found, skip validation
                        }
                        var relatedValue = relatedField.val(); // Get the value of the related field
                        return !(relatedValue === '1' && value.trim() === ''); // Validation condition
                    },
                    messages: {
                        en: 'This field is required when the condition is met.'
                    }
                });

                window.Parsley.on('field:validated', function (fieldInstance) {
                    var $element = fieldInstance.$element;
                    if ($element.hasClass('select2t-none')) {
                        // Update the Select2 container's appearance
                        var $select2Container = $element.next('.select2-container').find('.select2-selection');
                        if (fieldInstance.isValid()) {
                            $select2Container.removeClass('is-invalid');
                        } else {
                            $select2Container.addClass('is-invalid');
                        }
                    }
                });

            }
        }

        // Alpha-only Input Handling
        function initAlphaOnlyInputs() {
            $('.alpha-only').on('keyup blur', function() {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
            });
        }

        // Form Submission Handling
        function initFormSubmission() {
            $('#leave-category-form').on('submit', function(e) {
                // Prevent default submission
                e.preventDefault();

                // Validate entire form
                const form = $(this);
                if (form.parsley().validate()) {
                    // All validations passed
                    var formData = new FormData(this);

                    console.log(formData);
                    // Disable submit button to prevent multiple submissions
                    $('#submit')
                        .prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

                    // Ajax submission
                    $.ajax({
                        url: '{{ route('leaves.category.store') }}', // Your submission route
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#leaveCategoriesContainer').html(response.leaveCategoriesHtml);

                            // Handle successful submission
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        error: function(xhr) {
                            // Handle submission errors
                            var errorMessage = 'An error occurred while submitting your application.';

                            // Check for specific error responses
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Construct error message from Laravel validation errors
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            // Show error alert
                            toastr.error(errorMessage, "Error", {
                                positionClass: 'toast-bottom-right'
                            });

                            // Re-enable submit button
                            $('#submit')
                                .prop('disabled', false)
                                .html('Submit Application');
                        },
                        complete: function() {
                            // Optional: Any cleanup or final actions
                            // Re-enable submit button if it's still disabled
                            $('#submit')
                                .prop('disabled', false)
                                .html('Submit Application');
                        }
                    });
                }
                else
                    return false; // Stop if validation fails
            });
        }

        // Initialize All Validations and Plugins
        function initializeFormValidation() {
            initSelect2AndValidation();
            initParsleyValidation();
            initAlphaOnlyInputs();
            initFormSubmission();
        }

        // Call initialization when document is ready
        $(document).ready(initializeFormValidation);
    });
</script>
@endsection
