@extends('resorts.layouts.app')
@section('page_tab_title' ,"Onboarding Creation")

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
                            <h1>Create Itinerary</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <form id="msform" class="onboarding-form" action="{{route('people.onboarding.itinerary.store')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- progressbar -->
                    <div class="progressbar-wrapper">
                        <ul id="progressbar" class="progressbar-tab d-flex justify-content-between align-items-center ">
                            <li class="active current"> <span>Employee Selection</span></li>
                            <li><span>Template Selection</span></li>
                            <li><span>Onboarding</span></li>
                            <li><span>Confirmation</span></li>
                        </ul>
                    </div>
                    <hr>
                    <fieldset data-step="1">
                        <div class="mt-md-3 mt-2  mb-4">
                            <div class="card-header border-0 pb-0 mb-4">
                                <div class="row g-md-3 g-2">
                                    <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-8 col-sm-9 ">
                                        <div class="input-group">
                                            <input type="search" class="form-control" id="searchInput" placeholder="Search by Employee Name, ID or Manager Name" />

                                            <i class="fa-solid fa-search"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-title mb-md-3">
                            <h3>Upcoming Arrivals</h3>
                        </div>
                        <div class="row g-md-4 g-3 mb-md-5 mb-4" id="upcoming_employees">
                            
                        </div>
                        <hr class="hr-footer">
                        <a href="#" id="step1NextBtn" class="btn btn-themeBlue btn-sm float-end next">Next</a>
                    </fieldset>
                    <fieldset data-step="2">
                        <div class="row g-md-4 g-3 mb-md-5 mb-4" id="templateContainer">
                        </div>
                        <hr class="hr-footer border-0">
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-step="3">
                        <div class="mt-2">
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <label for="greeting_message" class="form-label">GREETING MESSAGE <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="greeting_message" name="greeting_message"
                                        placeholder="Write a Welcome Message"
                                        required
                                        data-parsley-required-message="Please enter welcome message"
                                        data-parsley-script-tag="false"
                                        data-parsley-script-tag-message="Script tags are not allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="arrival_date" class="form-label">Arrival Date <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker" id="arrival_date"
                                        placeholder="Arrival Date" required name="arrival_date"
                                        data-parsley-required-message="Please select arrival date">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="arrival_time" class="form-label">Arrival Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="arrival_time" name="arrival_time"
                                        placeholder="Arrival Time" required data-parsley-required-message="Please select arrival time">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="" class="form-label">ENTRY PASS <span class="red-mark">*</span></label>
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                            <input type="file" id="entry_pass_file" name="entry_pass_file" required
                                                data-parsley-required-message="Please upload entry pass" accept=".pdf">
                                        </div>
                                        <div class="uploadFile-text"></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="" class="form-label">FLIGHT TICKET <span class="red-mark">*</span></label>
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                            <input type="file" id="flight_ticket_file" name="flight_ticket_file" required data-parsley-required-message="Please upload flight ticket" accept=".pdf">
                                        </div>
                                        <div class="uploadFile-text"></div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="pickup_employee_id" class="form-label">PICKUP FROM AIRPORT <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="pickup_employee_id" name="pickup_employee_id" aria-label="Default select example" required data-parsley-required-message="Please select employee" data-parsley-errors-container="#pickup-error">
                                        <option value="">Select employee</option>
                                        @if($employees)
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}">{{$employee->Emp_id}} - {{ $employee->resortAdmin->full_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div id="pickup-error"></div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="accompany_medical_employee_id" class="form-label">ACCOMPANY FOR THE MEDICAL TEST <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="accompany_medical_employee_id" name="accompany_medical_employee_id" aria-label="Default select example" required data-parsley-required-message="Please select employee" data-parsley-errors-container="#accompany_medical-error">
                                        <option value="">Select employee</option>
                                        @if($employees)
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}">{{$employee->Emp_id}} - {{ $employee->resortAdmin->full_name }}</option>
                                            @endforeach
                                        @endif                                    
                                    </select>
                                    <div id="accompany_medical-error"></div>

                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Domestic Flight details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="domestic_flight_date" class="form-label">DOMESTIC FLIGHT DATE <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker" id="domestic_flight_date"
                                        placeholder="DOMESTIC FLIGHT DATE" required name="domestic_flight_date"
                                        data-parsley-required-message="Please select domestic flight date">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="domestic_departure_time" class="form-label">Departure Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="domestic_departure_time"
                                        placeholder="Departure Time" required name="domestic_departure_time"
                                        data-parsley-required-message="Please select departure time">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="domestic_arrival_time" class="form-label">Arrival Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="domestic_arrival_time"
                                        placeholder="Arrival Time" required name="domestic_arrival_time"
                                        data-parsley-required-message="Please select arrival time">
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Resort Transportation</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="resort_transportaion" class="form-label">RESORT TRANSPORTATION <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="resort_transportaion"
                                        placeholder="Resort Transportation" required name="resort_transportaion"
                                        data-parsley-required-message="Please enter resort transportation">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="transporation_name" class="form-label">TRANSPORTATION NAME <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="transporation_name"
                                        placeholder="Transportation Name" required name="transporation_name"
                                        data-parsley-required-message="Please enter transporation name">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="captain_number" class="form-label">Captain Number <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="captain_number"
                                        placeholder="Captain Number" required name="captain_number"
                                        data-parsley-required-message="Please enter captain number">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="location" class="form-label">Location <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="location" id="location" placeholder="Location" required data-parsley-required-message="Please enter location">
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Hotel Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_id" class="form-label">Hotel ID <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="hotel_id" id="hotel_id" placeholder="Hotel ID" required data-parsley-required-message="Please enter hotel id">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_name" class="form-label">Hotel Name <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="hotel_name" id="hotel_name" placeholder="Hotel Name" required data-parsley-required-message="Please enter hotel name">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_contact_no" class="form-label">Contact No <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="hotel_contact_no" id="hotel_contact_no" placeholder="Contact No" required data-parsley-required-message="Please enter contact no">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="booking_reference" class="form-label">BOOKING REFERENCE <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="booking_reference" id="booking_reference" placeholder="Booking Reference" required data-parsley-required-message="Please enter booking reference">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_address" class="form-label">Hotel Address <span class="red-mark">*</span></label>
                                    <textarea class="form-control" id="hotel_address" name="hotel_address" placeholder="Hotel Address" required data-parsley-required-message="Please enter hotel address"></textarea>
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Work Permit Medical Center Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_center_name" class="form-label">Medical center Name <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_center_name" placeholder="Medical Center Name" required name="medical_center_name" data-parsley-required-message="Please enter medical center name">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_center_contact_no" class="form-label">Contact No <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_center_contact_no" placeholder="Contact No" name="medical_center_contact_no" required data-parsley-required-message="Please enter contact no">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_type" class="form-label">Medical Type <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_type" name="medical_type"
                                        placeholder="Medical Type" required data-parsley-required-message="Please enter medical type">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="approx_time" class="form-label">Approx Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="approx_time" name="approx_time" placeholder="Approx Time" required data-parsley-required-message="Please select approx time"/>
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Schedule a Meeting</h3>
                            </div>
                            <div class="itineraryTemplateManage-main">
                                <div class="itineraryTemplateManage-block">
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="meeting_title" class="form-label">Meeting Title <span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="meeting_title"
                                                placeholder="Meeting Title" name="meeting_title[]" required
                                                data-parsley-required-message="Please enter meeting title">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="meeting_date" class="form-label">Meeting Date<span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="meeting_date"
                                                placeholder="Meeting Date" name="meeting_date[]" required
                                                data-parsley-required-message="Please select meeting date">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="meeting_time" class="form-label">Meeting Time<span class="red-mark">*</span></label>
                                            <input type="time" class="form-control" id="meeting_time"
                                                placeholder="Meeting Time" required name="meeting_time[]"
                                                data-parsley-required-message="Please select meeting time">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="meeting_link" class="form-label">Meeting Link <span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="meeting_link"
                                                placeholder="Meeting Link" required name="meeting_link[]"
                                                data-parsley-required-message="Please enter meeting link">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="participants" class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" id="participants" name="participants[]" aria-label="Default select example" multiple>
                                                <option value="">Select employees</option>
                                                @if($participants)
                                                    @foreach($participants as $participant)
                                                        <option value="{{ $participant->id }}">{{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm blockAdd-btn">
                                                Add More Meeting
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer ">
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-step="4">
                        <div class="reviewOnboardIti-block mt-md-4 mt-2">
                            <div class="mb-md-4 mb-3 pb-md-2  text-center">
                                <h4 class="fw-600">Review Onboarding Itinerary</h4>
                            </div>
                            <div class="bg-themeGrayLight mb-md-4 mb-3">
                                <div class="card-title">
                                    <h3>Selected Employees</h3>
                                </div>
                                <div class="row g-md-3 g-2">
                                    <div class="col-xl-4 col-lg-5 col-md-6">
                                        <div class="bg-white">
                                            <div class="d-flex align-items-center">
                                                <div class="img-circle userImg-block me-md-3 me-2">
                                                    <img src="assets/images/user-2.svg" alt="user">
                                                </div>
                                                <div>
                                                    <h6 class="fw-600">John Doe 
                                                        <span class="badge badge-themeNew">#34523</span>
                                                    </h6>
                                                    <p>Management - Assistant Front Desk Manager</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-themeGrayLight mb-md-4 mb-3">
                                <div class="card-title">
                                    <h3>Selected Templates</h3>
                                </div>
                                <div class="row g-md-3 g-2">
                                    <div class="col-xl-4 col-lg-5 col-md-6">
                                        <div class="bg-white">
                                            <h6 class="fw-600 mb-1">Day 1 Template</h6>
                                            <p class="fw-500">Standard first-day orientation</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class=" bg-themeGrayLight mb-md-4 mb-3">
                                <div class="card-title">
                                    <h3>Onboarding</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tr>
                                            <th>Greeting Message:</th>
                                            <td>Welcome to the Maldives</td>
                                        </tr>
                                        <tr>
                                            <th>Arrival Date:</th>
                                            <td>15 April 2025</td>
                                        </tr>
                                        <tr>
                                            <th>Arrival Time:</th>
                                            <td>05:00 am</td>
                                        </tr>
                                        <tr>
                                            <th>Pickup From Airport</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img src="assets/images/user-2.svg"
                                                            alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">John Doe</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Accompany For The Medical Test</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle">
                                                        <img src="assets/images/user-2.svg" alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">John Doe</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Domestic Flight Date</th>
                                            <td>20 April 2025</td>
                                        </tr>
                                        <tr>
                                            <th>Departure Time</th>
                                            <td>03:00 pm</td>
                                        </tr>
                                        <tr>
                                            <th>Arrival Time</th>
                                            <td>03:00 pm</td>
                                        </tr>
                                        <tr>
                                            <th>Resort Transportation</th>
                                            <td>Speed Boat</td>
                                        </tr>
                                        <tr>
                                            <th>Speedboat Name</th>
                                            <td>Jetty 1</td>
                                        </tr>
                                        <tr>
                                            <th>Captain Number</th>
                                            <td>1523633</td>
                                        </tr>
                                        <tr>
                                            <th>Hotel ID</th>
                                            <td>Hj452</td>
                                        </tr>
                                        <tr>
                                            <th>Hotel Name</th>
                                            <td>Loremipsum Hotel</td>
                                        </tr>
                                        <tr>
                                            <th>Contact No</th>
                                            <td>98548723561</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class=" bg-themeGrayLight mb-md-4 mb-3">
                                <div class="card-title">
                                    <h3>Meeting Schedules</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tr>
                                            <th>Meeting Title</th>
                                            <td>Onboarding Meeting</td>
                                        </tr>
                                        <tr>
                                            <th>Date & Time</th>
                                            <td>22 April 2025 - 2:30 pm</td>
                                        </tr>
                                        <tr>
                                            <th>Meeting Link</th>
                                            <td>meetinglink.com</td>
                                        </tr>
                                        <tr>
                                            <th>Participants</th>
                                            <td>
                                                <div class="user-ovImg">
                                                    <div class="img-circle">
                                                        <img src="assets/images/user-2.svg" alt="image">
                                                    </div>
                                                    <div class="img-circle">
                                                        <img src="assets/images/user-3.svg" alt="image">
                                                    </div>
                                                    <div class="img-circle">
                                                        <img src="assets/images/user-4.svg" alt="image">
                                                    </div>
                                                    <div class="img-circle">
                                                        <img src="assets/images/user-5.svg" alt="image">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end  ">Submit</a>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    let globalFormData = new FormData();

    $(document).ready(function () {
         initializeDatepicker();

        $('.select2t-none').select2();
        getUpcomingEmployees();

        // Trigger search
        $('#searchInput').on('keyup', function () {
            getUpcomingEmployees();
        });

        let meetingCount = 1;

        $('.blockAdd-btn').click(function (e) {
            e.preventDefault();
            meetingCount++;

            const newMeetingBlock = `
            <div class="itineraryTemplateManage-block mt-4 position-relative border-top pt-4">
                <button type="button" class="btn-close remove-meeting position-absolute top-0 end-0 mt-2 me-2" aria-label="Remove"></button>
                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                    <div class="col-lg-4 col-sm-6">
                        <label class="form-label">Meeting Title <span class="red-mark">*</span></label>
                        <input type="text" class="form-control" name="meeting_title[]" placeholder="Meeting Title" required data-parsley-required-message="Please enter meeting title">
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <label class="form-label">Meeting Date <span class="red-mark">*</span></label>
                        <input type="text" class="form-control" name="meeting_date[]" placeholder="Meeting Date" required data-parsley-required-message="Please select meeting date">
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <label class="form-label">Meeting Time <span class="red-mark">*</span></label>
                        <input type="time" class="form-control" name="meeting_time[]" placeholder="Meeting Time" required data-parsley-required-message="Please select meeting time">
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <label class="form-label">Meeting Link <span class="red-mark">*</span></label>
                        <input type="text" class="form-control" name="meeting_link[]" placeholder="Meeting Link" required data-parsley-required-message="Please enter meeting link">
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <label class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                        <select class="form-select select2t-none" name="participants[]" aria-label="Select Participants" required>
                            <option value="">Select employees</option>
                            @foreach($participants as $participant)
                                <option value="{{ $participant->id }}">{{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>`;

            $('.itineraryTemplateManage-main').append(newMeetingBlock);

            // Re-initialize components
            updateDatepickerValidation();

            $('.select2t-none').select2();
        });

        // Remove meeting block
        $(document).on('click', '.remove-meeting', function () {
            $(this).closest('.itineraryTemplateManage-block').remove();
        });

        $('#entry_pass_file').on('change', function(){
            let fileName = $(this).val().split('\\').pop();
            $(this).closest('.uploadFile-block').find('.uploadFile-text').text(fileName);
        });

        $('#flight_ticket_file').on('change', function(){
            let fileName = $(this).val().split('\\').pop();
            $(this).closest('.uploadFile-block').find('.uploadFile-text').text(fileName);
        });

        window.Parsley.addValidator('scriptTag', {
            validateString: function(value) {
                return !/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi.test(value);
            },
            messages: {
                en: 'Script tags are not allowed.'
            }
        });

        function moveToNextStep(currentFieldset, callback) {
            const nextFieldset = currentFieldset.next("fieldset");

            $("#progressbar li").eq($("fieldset").index(nextFieldset)).addClass("active current");
            $("#progressbar li").eq($("fieldset").index(currentFieldset)).removeClass("current");

            currentFieldset.hide();
            nextFieldset.show();

            if (typeof callback === 'function') {
                callback();
            }
        }

        function moveToPreviousStep(currentFieldset) {
            const prevFieldset = currentFieldset.prev("fieldset");

            $("#progressbar li").eq($("fieldset").index(currentFieldset)).removeClass("current active");
            $("#progressbar li").eq($("fieldset").index(prevFieldset)).addClass("current");

            currentFieldset.hide();
            prevFieldset.show();
        }

        function fetchTemplatesForEmployee(employeeId) {
            const templateContainer = $('#templateContainer');
            templateContainer.html('<div class="text-center w-100">Loading templates...</div>');

            $.ajax({
                url: '{{ route("people.onboarding.getTemplatesForEmployees") }}',
                method: 'POST',
                data: {
                    employee_id: employeeId,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    templateContainer.empty();
                    if (response && response.id) {
                        localStorage.setItem("template_id", response.id);
                        localStorage.setItem("template_name", response.name);
                        localStorage.setItem("template_description", response.description);

                        templateContainer.append(`
                            <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                <div class="templateSelect-block">
                                    <div class="flex-grow-1">
                                        <h6>${response.name}</h6>
                                        <p>${response.description}</p>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" checked value="${response.id}" name="template_id">
                                        <label class="form-check-label">Select Template</label>
                                    </div>
                                </div>
                            </div>
                        `);
                    } else {
                        toastr.error("No templates available for this employee.", "Error", {
                            positionClass: 'toast-bottom-right',
                        });
                    }
                },
                error: function () {
                    templateContainer.html('<div class="text-danger">Error loading templates.</div>');
                }
            });
        }

        $(".next").click(function (e) {
            e.preventDefault();
            const $current = $(this).closest("fieldset");
            const currentStep = $current.data("step");

            // Updated Step 1 next button handler
            if (currentStep === 1) {
                const selectedEmployee = $(".employee-radio:checked").val();
                if (!selectedEmployee) {
                    toastr.error("Please select an employee.");
                    return;
                }

                $('#selectedEmployeeId').val(selectedEmployee);
                localStorage.setItem("employee_id", selectedEmployee);

                // Fetch and store employee details
                // Fetch and store employee details
                fetchEmployeeDetails(selectedEmployee).then(function(employeeData) {
                    localStorage.setItem("employee_name", employeeData.full_name);
                    localStorage.setItem("employee_role", employeeData.position);
                    localStorage.setItem("employee_department", employeeData.department);
                    localStorage.setItem("employee_emp_id", employeeData.emp_id);
                    localStorage.setItem("employee_image", employeeData.profile_image);
                    localStorage.setItem("AdminParentId", employeeData.admin_parent_id);
                    
                    // Store joining date and update datepicker validation
                    if (employeeData.joining_date) {
                        localStorage.setItem("employee_joining_date", employeeData.joining_date);
                        updateDatepickerValidation();
                    }

                    // Move to next step after employee details are loaded
                    moveToNextStep($current, function () {
                        fetchTemplatesForEmployee(selectedEmployee);
                    });
                }).catch(function(error) {
                    console.error('Error fetching employee details:', error);
                    toastr.error("Error loading employee details. Please try again.");
                });
            } else if (currentStep === 2) {
                const selectedTemplate = $('#templateContainer input[name="template_id"]:checked').val();
                if (!selectedTemplate) {
                    toastr.error("Please select a template.");
                    return;
                }

                $('#selectedTemplateId').val(selectedTemplate);
                localStorage.setItem("template_id", selectedTemplate);

                moveToNextStep($current);

            } else if (currentStep === 3) {
                let isValid = $('#msform').parsley().validate();

                if (isValid) {
                        checkAllMeetingConflicts().then(function(hasConflicts) {
                        if (hasConflicts) {
                            toastr.error("Please resolve meeting conflicts before proceeding.", "Meeting Conflicts Found", {
                                positionClass: 'toast-bottom-right',
                                timeOut: 5000
                            });
                            return;
                        }
                    // Clear old data
                    globalFormData = new FormData($('#msform')[0]);

                    // ... existing code for storing form data ...

                    // Fetch pickup and medical accompany employee details
                    const pickupEmployeeId = $('#pickup_employee_id').val();
                    const medicalEmployeeId = $('#accompany_medical_employee_id').val();
                    
                    Promise.all([
                        fetchEmployeeDetails(pickupEmployeeId),
                        fetchEmployeeDetails(medicalEmployeeId)
                    ]).then(function([pickupEmployee, medicalEmployee]) {
                        localStorage.setItem('pickup_employee_name', pickupEmployee.full_name);
                        localStorage.setItem('pickup_employee_image', pickupEmployee.profile_image);
                        localStorage.setItem('pickup_employee_emp_id', pickupEmployee.emp_id);
                        
                        localStorage.setItem('medical_employee_name', medicalEmployee.full_name);
                        localStorage.setItem('medical_employee_image', medicalEmployee.profile_image);
                        localStorage.setItem('medical_employee_emp_id', medicalEmployee.emp_id);
                    });

                    // Handle meeting participants
                    const meetingParticipants = $('select[name="participants[]"]').map(function() { 
                        return $(this).val(); 
                    }).get().filter(id => id); // Remove empty values

                    if (meetingParticipants.length > 0) {
                        fetchParticipantDetails(meetingParticipants).then(function(participants) {
                            localStorage.setItem('meeting_participants_data', JSON.stringify(participants));
                        });
                    }

                    moveToNextStep($current);
                    
                    // Small delay to ensure all AJAX calls complete before updating preview
                    setTimeout(function() {
                        updateStep4Preview();
                    }, 500);
                }
            }
        });

        $(".previous").click(function () {
            moveToPreviousStep($(this).closest("fieldset"));
        });

        // Handle form submission
        $(document).on('click', '[data-step="4"] .btn:contains("Submit")', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = $(this);
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
            
            try {
                // Add meeting arrays to FormData
                const meetingTitles = JSON.parse(localStorage.getItem('meeting_titles') || '[]');
                const meetingDates = JSON.parse(localStorage.getItem('meeting_dates') || '[]');
                const meetingTimes = JSON.parse(localStorage.getItem('meeting_times') || '[]');
                const meetingLinks = JSON.parse(localStorage.getItem('meeting_links') || '[]');
                const meetingParticipants = JSON.parse(localStorage.getItem('meeting_participants') || '[]');

                // Clear any existing meeting data in FormData and add fresh data
                meetingTitles.forEach((title, index) => {
                    if (title) {
                        globalFormData.append('meeting_title[]', title);
                    }
                });
                
                meetingDates.forEach((date, index) => {
                    if (date) {
                        globalFormData.append('meeting_date[]', date);
                    }
                });
                
                meetingTimes.forEach((time, index) => {
                    if (time) {
                        globalFormData.append('meeting_time[]', time);
                    }
                });
                
                meetingLinks.forEach((link, index) => {
                    if (link) {
                        globalFormData.append('meeting_link[]', link);
                    }
                });
                
                meetingParticipants.forEach((participant, index) => {
                    if (participant) {
                        globalFormData.append('participants[]', participant);
                    }
                });

                // Add CSRF token
                globalFormData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                // Submit the form
                $.ajax({
                    url: '{{ route("people.onboarding.itinerary.store") }}',
                    method: 'POST',
                    data: globalFormData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success', {
                                positionClass: 'toast-bottom-right',
                                timeOut: 5000
                            });
                            
                            // Clear localStorage
                            localStorage.clear();
                            
                            // Reset form
                            $('#msform')[0].reset();
                            
                            // Redirect to success page or itinerary list
                            setTimeout(function() {
                                window.location.href = '{{ route("people.onboarding.index") }}';
                            }, 2000);
                            
                        } else {
                            toastr.error(response.message || 'Failed to create onboarding itinerary', 'Error', {
                                positionClass: 'toast-bottom-right',
                                timeOut: 5000
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Submission error:', xhr);
                        
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            let errorMessage = 'Please fix the following errors:\n';
                            
                            Object.keys(errors).forEach(function(key) {
                                errorMessage += '• ' + errors[key][0] + '\n';
                            });
                            
                            toastr.error(errorMessage, 'Validation Error', {
                                positionClass: 'toast-bottom-right',
                                timeOut: 10000,
                                escapeHtml: false
                            });
                        } else if (xhr.status === 413) {
                            toastr.error('File size too large. Please upload smaller files.', 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            const errorMessage = xhr.responseJSON?.message || 'Error creating onboarding itinerary. Please try again.';
                            toastr.error(errorMessage, 'Error', {
                                positionClass: 'toast-bottom-right',
                                timeOut: 5000
                            });
                        }
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
                
            } catch (error) {
                console.error('Form submission error:', error);
                toastr.error('An unexpected error occurred. Please try again.', 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                
                // Reset button state
                submitBtn.prop('disabled', false).text(originalText);
            }
        });

    });

    // Initialize datepicker without restrictions initially
    function initializeDatepicker() {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            startDate: new Date() // Only restrict to today by default
        });

        $('#meeting_date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            startDate: new Date() // Only restrict to today by default
        });
    }

    // Update datepicker validation based on selected employee's joining date
    function updateDatepickerValidation() {
        const joiningDate = localStorage.getItem('employee_joining_date');
        
        if (joiningDate) {
            const today = new Date();
            const beforeJoining = new Date(new Date(joiningDate).getTime() - 86400000); // One day before joining
            
            // Destroy existing datepickers and reinitialize with new restrictions
            $('.datepicker').datepicker('destroy');
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: today,
                endDate: beforeJoining
            });
            
            console.log(`Datepicker updated: Start Date = ${today.toDateString()}, End Date = ${beforeJoining.toDateString()}`);
        }
    }

    function checkMeetingConflicts(participantIds, meetingDate, meetingTime, currentMeetingBlock = null) {
        if (!participantIds || participantIds.length === 0 || !meetingDate || !meetingTime) {
            return Promise.resolve({ hasConflicts: false, conflicts: [] });
        }

        return $.ajax({
            url: '{{ route("people.onboarding.checkMeetingConflicts") }}',
            method: 'POST',
            data: {
                participant_ids: participantIds,
                meeting_date: meetingDate,
                meeting_time: meetingTime,
                _token: '{{ csrf_token() }}'
            }
        }).then(function(response) {
            return {
                hasConflicts: response.hasConflicts || false,
                conflicts: response.conflicts || []
            };
        }).catch(function(xhr) {
            console.error('Error checking meeting conflicts:', xhr);
            return { hasConflicts: false, conflicts: [] };
        });
    }

    // Function to add conflict checking listeners to meeting inputs
    function addMeetingConflictListeners() {
        // Remove existing listeners to prevent duplicates
        $(document).off('change blur', '.meeting-date-input, .meeting-time-input, .meeting-participants-select');
        
        // Add new listeners
        $(document).on('change blur', '.meeting-date-input, .meeting-time-input, .meeting-participants-select', function() {
            const meetingBlock = $(this).closest('.itineraryTemplateManage-block');
            checkSingleMeetingConflict(meetingBlock);
        });
    }
    // Function to check conflicts for a single meeting block
    function checkSingleMeetingConflict(meetingBlock) {
        const dateInput = meetingBlock.find('.meeting-date-input');
        const timeInput = meetingBlock.find('.meeting-time-input');
        const participantsSelect = meetingBlock.find('.meeting-participants-select');
        
        const meetingDate = dateInput.val();
        const meetingTime = timeInput.val();
        const participantIds = participantsSelect.val();

        // Clear previous conflict indicators
        clearConflictIndicators(meetingBlock);

        if (meetingDate && meetingTime && participantIds && participantIds.length > 0) {
            checkMeetingConflicts(participantIds, meetingDate, meetingTime, meetingBlock)
                .then(function(result) {
                    if (result.hasConflicts) {
                        showConflictIndicators(meetingBlock, result.conflicts);
                    }
                });
        }
    }

    // Function to check all meeting conflicts before proceeding
    function checkAllMeetingConflicts() {
        const conflictPromises = [];
        let hasAnyConflicts = false;

        $('.itineraryTemplateManage-block').each(function() {
            const meetingBlock = $(this);
            const dateInput = meetingBlock.find('.meeting-date-input, input[name="meeting_date[]"]');
            const timeInput = meetingBlock.find('.meeting-time-input, input[name="meeting_time[]"]');
            const participantsSelect = meetingBlock.find('.meeting-participants-select, select[name="participants[]"]');
            
            const meetingDate = dateInput.val();
            const meetingTime = timeInput.val();
            const participantIds = participantsSelect.val();

            if (meetingDate && meetingTime && participantIds && participantIds.length > 0) {
                const promise = checkMeetingConflicts(participantIds, meetingDate, meetingTime, meetingBlock)
                    .then(function(result) {
                        if (result.hasConflicts) {
                            hasAnyConflicts = true;
                            showConflictIndicators(meetingBlock, result.conflicts);
                            return true;
                        } else {
                            clearConflictIndicators(meetingBlock);
                            return false;
                        }
                    });
                conflictPromises.push(promise);
            }
        });

        return Promise.all(conflictPromises).then(function(results) {
            return hasAnyConflicts;
        });
    }

    // Function to show conflict indicators
    function showConflictIndicators(meetingBlock, conflicts) {
        // Add error styling to inputs
        meetingBlock.find('.meeting-date-input, input[name="meeting_date[]"]').addClass('is-invalid border-danger');
        meetingBlock.find('.meeting-time-input, input[name="meeting_time[]"]').addClass('is-invalid border-danger');
        meetingBlock.find('.meeting-participants-select, select[name="participants[]"]').addClass('is-invalid border-danger');

        // Remove existing conflict messages
        meetingBlock.find('.conflict-message').remove();

        // Create conflict message
        let conflictMessage = '<div class="conflict-message alert alert-danger mt-2 mb-2"><strong>Meeting Conflict!</strong><br>';
        conflicts.forEach(function(conflict) {
            conflictMessage += `• ${conflict.participant_name} has another meeting "${conflict.meeting_title}" at ${conflict.meeting_time} on ${conflict.meeting_date}<br>`;
        });
        conflictMessage += '</div>';

        // Add conflict message after the meeting block
        meetingBlock.append(conflictMessage);

        // Show toast notification
        toastr.warning(`Meeting conflicts detected. Please resolve conflicts before proceeding.`, 'Meeting Conflicts', {
            positionClass: 'toast-bottom-right',
            timeOut: 5000
        });
    }

    // Function to clear conflict indicators
    function clearConflictIndicators(meetingBlock) {
        meetingBlock.find('.meeting-date-input, input[name="meeting_date[]"]').removeClass('is-invalid border-danger');
        meetingBlock.find('.meeting-time-input, input[name="meeting_time[]"]').removeClass('is-invalid border-danger');
        meetingBlock.find('.meeting-participants-select, select[name="participants[]"]').removeClass('is-invalid border-danger');
        meetingBlock.find('.conflict-message').remove();
    }

    // Fetch Upcoming Employees with optional search

    // Fetch Upcoming Employees with optional search
    function getUpcomingEmployees() {
        let search = $('#searchInput').val();

        $.ajax({
            url: "{{ route('people.onboarding.upcoming_employees') }}",
            type: "GET",
            data: { search: search },
            success: function (response) {
                $('#upcoming_employees').html(response.html);
            },
            error: function (xhr, status, error) {
                console.error(xhr);
            }
        });
    }

    // Add this function to fetch employee details from backend
    function fetchEmployeeDetails(employeeId) {
        return $.ajax({
            url: '{{ route("people.onboarding.getEmployeeDetails") }}', // You'll need to create this route
            method: 'POST',
            data: {
                employee_id: employeeId,
                _token: '{{ csrf_token() }}'
            }
        });
    }

    // Add this function to fetch participant details
    function fetchParticipantDetails(participantIds) {
        return $.ajax({
            url: '{{ route("people.onboarding.getParticipantDetails") }}', // You'll need to create this route
            method: 'POST',
            data: {
                participant_ids: participantIds,
                _token: '{{ csrf_token() }}'
            }
        });
    }

    // Updated Step 4 Preview Function
    function updateStep4Preview() {
        console.log("📦 All localStorage Data:");
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            const value = localStorage.getItem(key);
            console.log(`${key}:`, value);
        }

        // Update Selected Employee section
        const employeeName = localStorage.getItem('employee_name') || 'Unknown Employee';
        const employeeRole = localStorage.getItem('employee_role') || 'Unknown Role';
        const employeeDepartment = localStorage.getItem('employee_department') || '';
        const employeeEmpId = localStorage.getItem('employee_emp_id') || '';
        const employeeImage = localStorage.getItem('employee_image') || 'assets/images/user-2.svg';
        
        const employeeHtml = `
            <div class="col-xl-4 col-lg-5 col-md-6">
                <div class="bg-white">
                    <div class="d-flex align-items-center">
                        <div class="img-circle userImg-block me-md-3 me-2">
                            <img src="${employeeImage}" alt="user" onerror="this.src='assets/images/user-2.svg'">
                        </div>
                        <div>
                            <h6 class="fw-600">${employeeName} 
                            </h6>
                            <p>${employeeDepartment} - ${employeeRole}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Update Selected Template section
        const templateName = localStorage.getItem('template_name') || 'Day 1 Template';
        const templateDescription = localStorage.getItem('template_description') || 'Standard first-day orientation';
        
        const templateHtml = `
            <div class="col-xl-4 col-lg-5 col-md-6">
                <div class="bg-white">
                    <h6 class="fw-600 mb-1">${templateName}</h6>
                    <p class="fw-500">${templateDescription}</p>
                </div>
            </div>
        `;

        // Update the onboarding details table with employee images
        const pickupEmployeeName = localStorage.getItem('pickup_employee_name') || '';
        const pickupEmployeeImage = localStorage.getItem('pickup_employee_image') || 'assets/images/user-2.svg';
        const pickupEmployeeEmpId = localStorage.getItem('pickup_employee_emp_id') || '';
        
        const medicalEmployeeName = localStorage.getItem('medical_employee_name') || '';
        const medicalEmployeeImage = localStorage.getItem('medical_employee_image') || 'assets/images/user-2.svg';
        const medicalEmployeeEmpId = localStorage.getItem('medical_employee_emp_id') || '';

        const onboardingTableRows = `
            <tr><th>Greeting Message:</th><td>${localStorage.getItem('greeting_message') || ''}</td></tr>
            <tr><th>Arrival Date:</th><td>${localStorage.getItem('arrival_date') || ''}</td></tr>
            <tr><th>Arrival Time:</th><td>${localStorage.getItem('arrival_time') || ''}</td></tr>
            <tr><th>Pickup From Airport</th><td>
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="${pickupEmployeeImage}" alt="user" onerror="this.src='assets/images/user-2.svg'">
                    </div>
                    <span class="userApplicants-btn">${pickupEmployeeName} (#${pickupEmployeeEmpId})</span>
                </div>
            </td></tr>
            <tr><th>Accompany For The Medical Test</th><td>
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="${medicalEmployeeImage}" alt="user" onerror="this.src='assets/images/user-2.svg'">
                    </div>
                    <span class="userApplicants-btn">${medicalEmployeeName} (#${medicalEmployeeEmpId})</span>
                </div>
            </td></tr>
            <tr><th>Domestic Flight Date</th><td>${localStorage.getItem('domestic_flight_date') || ''}</td></tr>
            <tr><th>Departure Time</th><td>${localStorage.getItem('domestic_departure_time') || ''}</td></tr>
            <tr><th>Arrival Time</th><td>${localStorage.getItem('domestic_arrival_time') || ''}</td></tr>
            <tr><th>Resort Transportation</th><td>${localStorage.getItem('resort_transportaion') || ''}</td></tr>
            <tr><th>Transportation Name</th><td>${localStorage.getItem('transporation_name') || ''}</td></tr>
            <tr><th>Captain Number</th><td>${localStorage.getItem('captain_number') || ''}</td></tr>
            <tr><th>Location</th><td>${localStorage.getItem('location') || ''}</td></tr>
            <tr><th>Hotel ID</th><td>${localStorage.getItem('hotel_id') || ''}</td></tr>
            <tr><th>Hotel Name</th><td>${localStorage.getItem('hotel_name') || ''}</td></tr>
            <tr><th>Contact No</th><td>${localStorage.getItem('hotel_contact_no') || ''}</td></tr>
            <tr><th>Booking Reference</th><td>${localStorage.getItem('booking_reference') || ''}</td></tr>
            <tr><th>Hotel Address</th><td>${localStorage.getItem('hotel_address') || ''}</td></tr>
            <tr><th>Medical Center Name</th><td>${localStorage.getItem('medical_center_name') || ''}</td></tr>
            <tr><th>Medical Center Contact</th><td>${localStorage.getItem('medical_center_contact_no') || ''}</td></tr>
            <tr><th>Medical Type</th><td>${localStorage.getItem('medical_type') || ''}</td></tr>
            <tr><th>Approx Time</th><td>${localStorage.getItem('approx_time') || ''}</td></tr>
        `;

        // Update meeting schedules with participant images
        const meetingTitles = JSON.parse(localStorage.getItem('meeting_titles') || '[]');
        const meetingDates = JSON.parse(localStorage.getItem('meeting_dates') || '[]');
        const meetingTimes = JSON.parse(localStorage.getItem('meeting_times') || '[]');
        const meetingLinks = JSON.parse(localStorage.getItem('meeting_links') || '[]');
        const meetingParticipantsData = JSON.parse(localStorage.getItem('meeting_participants_data') || '[]');

        let meetingTableRows = '';
        meetingTitles.forEach((title, index) => {
            // Generate participant images HTML
            let participantImagesHtml = '';
            if (meetingParticipantsData && meetingParticipantsData.length > 0) {
                participantImagesHtml = meetingParticipantsData.slice(0, 4).map(participant => 
                    `<div class="img-circle">
                        <img src="${participant.profile_image || 'assets/images/user-2.svg'}" 
                            alt="${participant.full_name}" 
                            title="${participant.full_name} (#${participant.emp_id})"
                            onerror="this.src='assets/images/user-2.svg'">
                    </div>`
                ).join('');
                
                // Add more indicator if there are more than 4 participants
                if (meetingParticipantsData.length > 4) {
                    participantImagesHtml += `<div class="img-circle more-participants">+${meetingParticipantsData.length - 4}</div>`;
                }
            }

            meetingTableRows += `
                <tr><th>Meeting Title</th><td>${title}</td></tr>
                <tr><th>Date & Time</th><td>${meetingDates[index]} - ${meetingTimes[index]}</td></tr>
                <tr><th>Meeting Link</th><td><a href="${meetingLinks[index]}" target="_blank">${meetingLinks[index]}</a></td></tr>
                <tr><th>Participants</th><td>
                    <div class="user-ovImg">
                        ${participantImagesHtml}
                    </div>
                </td></tr>
            `;
            
            // Only show first meeting in the preview for now
            if (index === 0) return false;
        });

        // Update the HTML elements
        $('[data-step="4"] .bg-themeGrayLight:eq(0) .row').html(employeeHtml);
        $('[data-step="4"] .bg-themeGrayLight:eq(1) .row').html(templateHtml);
        $('[data-step="4"] .bg-themeGrayLight:eq(2) .table-responsive table tbody').html(onboardingTableRows);
        $('[data-step="4"] .bg-themeGrayLight:eq(3) .table-responsive table tbody').html(meetingTableRows);
    }
</script>
@endsection
