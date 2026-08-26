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
                                   <input type="text"
                                        class="form-control"
                                        id="greeting_message"
                                        name="greeting_message"
                                        placeholder="Write a Welcome Message"
                                        required
                                        data-parsley-required-message="Please enter welcome message"
                                        data-parsley-script-tag="true"
                                        data-parsley-html="true">
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
                                <h3>Resort Transportation</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="resort_transportaion" class="form-label">RESORT TRANSPORTATION <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="resort_transportaion_id"
                                        placeholder="Resort Transportation" required name="resort_transportaion_id" data-parsley-required-message="Please select resort transportation" data-parsley-errors-container="#resort_transportation-error" >
                                        <option value="">Select Resort Transportation</option>
                                        @if($transportations)
                                            @foreach($transportations as $key => $value)
                                                {{-- data-option carries the transportation NAME so the
                                                     section-switching JS matches by name, not by a
                                                     hardcoded id (resort_transportations ids vary per resort). --}}
                                                <option value="{{ $key }}" data-option="{{ $value }}" {{ optional($lastItinerary)->resort_transportation_id == $key ? 'selected' : '' }}>{{ $value }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div id="resort_transportation-error"></div>
                                </div>
                            </div>

                            <!-- Domestic Flight Fields -->
                            <div class="transportation-section" id="domestic-flight-section" style="display:none;">
                                <div class="card-title">
                                    <h3>Domestic Flight details</h3>
                                </div>
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="" class="form-label">DOMESTIC FLIGHT TICKET <span class="red-mark">*</span></label>
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn">
                                                <a href="#" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                                <input type="file" id="domestic_flight_ticket" name="domestic_flight_ticket" required data-parsley-required-message="Please upload domestic flight ticket" accept=".pdf">
                                            </div>
                                            <div class="uploadFile-text"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="domestic_flight_date" class="form-label">DOMESTIC FLIGHT DATE <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control datepicker" id="domestic_flight_date"
                                            placeholder="DOMESTIC FLIGHT DATE" name="domestic_flight_date"
                                            data-parsley-required-message="Please select domestic flight date">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="domestic_departure_time" class="form-label">DEPARTURE TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="domestic_departure_time"
                                            placeholder="Departure Time" name="domestic_departure_time"
                                            data-parsley-required-message="Please select departure time">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="domestic_arrival_time" class="form-label">ARRIVAL TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="domestic_arrival_time"
                                            placeholder="Arrival Time" name="domestic_arrival_time"
                                            data-parsley-required-message="Please select arrival time">
                                    </div>
                                </div>
                            </div>

                            <!-- Speedboat Fields -->
                            <div class="transportation-section" id="speedboat-section" style="display:none;">
                                <div class="card-title">
                                    <h3>Speedboat details</h3>
                                </div>
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6 transportation-field" id="transportation-name-field">
                                        <label for="speedboat_name" class="form-label">SPEEDBOAT NAME <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control" id="speedboat_name"
                                            placeholder="Speedboat Name" required name="speedboat_name"
                                            data-parsley-required-message="Please enter speedboat name" data-parsley-script-tag="true"
                                            data-parsley-html="true" value="{{ optional($lastItinerary)->speedboat_name }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="captain_number" class="form-label">CAPTAIN NUMBER <span class="red-mark">*</span></label>
                                        <input type="number" class="form-control" id="captain_number"
                                            placeholder="Captain Number" name="captain_number"
                                            data-parsley-required-message="Please enter captain number">
                                    </div>
                                    <div class="col-lg-4 col-sm-6 transportation-field" id="location-field">
                                        <label for="location" class="form-label">LOCATION <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control" name="location" id="location"
                                            placeholder="Location" required data-parsley-script-tag="true"
                                            data-parsley-html="true" data-parsley-required-message="Please enter location" value="{{ optional($lastItinerary)->location }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_date" class="form-label">SPEEDBOAT DATE <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control datepicker" id="speedboat_date"
                                            placeholder="Speedboat Date" name="speedboat_date"
                                            data-parsley-required-message="Please select speedboat date">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_departure_time" class="form-label">SPEEDBOAT DEPARTURE TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="speedboat_departure_time"
                                            placeholder="Speedboat Departure Time" name="speedboat_departure_time"
                                            data-parsley-required-message="Please select speedboat departure time">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_arrival_time" class="form-label">SPEEDBOAT ARRIVAL TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="speedboat_arrival_time"
                                            placeholder="Speedboat Arrival Time" name="speedboat_arrival_time"
                                            data-parsley-required-message="Please select speedboat arrival time">
                                    </div>
                                </div>
                            </div>

                            <!-- Seaplane Fields -->
                            <div class="transportation-section" id="seaplane-section" style="display:none;">
                                <div class="card-title">
                                    <h3>Seaplane details</h3>
                                </div>
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="seaplane_date" class="form-label">FLIGHT DATE <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control datepicker" id="seaplane_date"
                                            placeholder="Flight Date" name="domestic_flight_date"
                                            data-parsley-required-message="Please select flight date">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="seaplane_departure_time" class="form-label">DEPARTURE TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="seaplane_departure_time"
                                            placeholder="Departure Time" name="domestic_departure_time"
                                            data-parsley-required-message="Please select departure time">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="seaplane_arrival_time" class="form-label">ARRIVAL TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="seaplane_arrival_time"
                                            placeholder="Arrival Time" name="domestic_arrival_time"
                                            data-parsley-required-message="Please select arrival time">
                                    </div>
                                </div>
                            </div>

                            <div class="card-title">
                                <h3>Hotel Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                {{-- Hotel fields default from the resort's most recent
                                     itinerary ($lastItinerary) — same partner hotel is
                                     typically reused. Booking Reference is per-booking
                                     so it is NOT pre-filled. --}}
                                {{-- Hotel ID field hidden per request. Kept as a hidden
                                     input so the (still-nullable) backend column keeps
                                     receiving the pre-filled value without HR re-typing. --}}
                                <input type="hidden" name="hotel_id" id="hotel_id" value="{{ optional($lastItinerary)->hotel_id }}">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_name" class="form-label">Hotel Name <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="hotel_name" id="hotel_name" placeholder="Hotel Name" required data-parsley-required-message="Please enter hotel name" data-parsley-script-tag="true"
                                        data-parsley-html="true" value="{{ optional($lastItinerary)->hotel_name }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_contact_no" class="form-label">Contact No <span class="red-mark">*</span></label>
                                    <input type="number" class="form-control" name="hotel_contact_no" id="hotel_contact_no" placeholder="Contact No" required data-parsley-required-message="Please enter contact no" value="{{ optional($lastItinerary)->hotel_contact_no }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="booking_reference" class="form-label">BOOKING REFERENCE <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="booking_reference" id="booking_reference" placeholder="Booking Reference" required data-parsley-required-message="Please enter booking reference" data-parsley-script-tag="true"
                                        data-parsley-html="true">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_address" class="form-label">Hotel Address <span class="red-mark">*</span></label>
                                    <textarea class="form-control" id="hotel_address" name="hotel_address" placeholder="Hotel Address" required data-parsley-required-message="Please enter hotel address" data-parsley-script-tag="true"
                                        data-parsley-html="true">{{ optional($lastItinerary)->hotel_address }}</textarea>
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Work Permit Medical Center Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                {{-- Medical-centre fields default from the last
                                     itinerary — the resort's work-permit medical
                                     centre is the same partner each time. Test
                                     date/time stay blank (per appointment). --}}
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_center_name" class="form-label">Medical Center Name <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_center_name" placeholder="Medical Center Name" required name="medical_center_name" data-parsley-required-message="Please enter medical center name" data-parsley-script-tag="true"
                                        data-parsley-html="true" value="{{ optional($lastItinerary)->medical_center_name }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_center_contact_no" class="form-label">Contact No <span class="red-mark">*</span></label>
                                    <input type="number" class="form-control" id="medical_center_contact_no" placeholder="Contact No" name="medical_center_contact_no" required data-parsley-required-message="Please enter contact no" value="{{ optional($lastItinerary)->medical_center_contact_no }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_type" class="form-label">Medical Type <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_type" name="medical_type"
                                        placeholder="Medical Type" required data-parsley-required-message="Please enter medical type" data-parsley-script-tag="true"
                                        data-parsley-html="true" value="{{ optional($lastItinerary)->medical_type }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_date" class="form-label">Medical Test Date <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker" id="medical_date" name="medical_date"
                                        placeholder="Medical Test Date" required data-parsley-required-message="Please enter medical test date">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_time" class="form-label">Medical Test Time <span class="red-mark">*</span></label>
                                    {{-- Native time picker — matches arrival_time / meeting_time
                                         elsewhere on this form (was plain text, no picker UI). --}}
                                    <input type="time" class="form-control" id="medical_time" name="medical_time"
                                        required data-parsley-required-message="Please enter medical test time">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="approx_time" class="form-label">Approx Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="approx_time" name="approx_time"
                                        required data-parsley-required-message="Please enter approx time"/>
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
                                                data-parsley-required-message="Please enter meeting title" data-parsley-script-tag="true"
                                                data-parsley-html="true">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="meeting_date" class="form-label">Meeting Date<span class="red-mark">*</span></label>
                                            <input type="text" class="form-control datepicker" id="meeting_date"
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
                                                data-parsley-required-message="Please enter meeting link" data-parsley-script-tag="true"
                                                data-parsley-html="true">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="participants" class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                        
                                            <select class="form-select select2t-none" id="participants" name="participants[]" aria-label="Default select example" multiple>
                                                <option value="">Select employees</option>
                                                @if(isset($participants) && count($participants) > 0)
                                                    @foreach($participants as $participant)
                                                        <option value="{{ $participant->id }}">{{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}</option>
                                                    @endforeach
                                                @else
                                                    <option value="">No participants available</option>
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
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="user">
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
                                                    <div class="img-circle">
                                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}"
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
                                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="user">
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
                                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                                    </div>
                                                    <div class="img-circle">
                                                        <img src="{{ URL::asset('resorts_assets/images/user-3.svg')}}" alt="image">
                                                    </div>
                                                    <div class="img-circle">
                                                        <img src="{{ URL::asset('resorts_assets/images/user-4.svg')}}" alt="image">
                                                    </div>
                                                    <div class="img-circle">
                                                        <img src="{{ URL::asset('resorts_assets/images/user-5.svg')}}" alt="image">
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

                           
        // Initialize fields based on default selection
        updateTransportationFields();
        
        // Update fields when transportation type changes
        $('#resort_transportaion_id').on('change', function() {
            updateTransportationFields();
            
            // Reinitialize datepicker for newly displayed fields
            $('.transportation-section:visible .datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: new Date()
            });
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
                        <input type="text" class="form-control datepicker" name="meeting_date[]" placeholder="Meeting Date" required data-parsley-required-message="Please select meeting date">
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
                        <select class="form-select select2t-none" name="participants[]" aria-label="Select Participants" required multiple>
                            <option value="">Select employees</option>
                            @foreach($participants as $participant)
                                <option value="{{ $participant->id }}">{{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>`;

            $('.itineraryTemplateManage-main').append(newMeetingBlock);

            // Bind the datepicker + select2 directly to the just-added block.
            // Previously this leaned on updateDatepickerValidation(), but that
            // function early-returns when the employee has no joining date in
            // localStorage — leaving the new Meeting Date input with no picker.
            const $newBlock = $('.itineraryTemplateManage-main .itineraryTemplateManage-block').last();
            $newBlock.find('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: new Date()
            });
            $newBlock.find('.select2t-none').select2();

            // Re-apply joining-date bounds across all date inputs when known
            // (no-op when the employee has no joining date).
            updateDatepickerValidation();
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

        $('#domestic_flight_ticket').on('change', function(){
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

        window.Parsley.addValidator('html', {
            validateString: function(value) {
                return value === $('<div>').text(value).html();
            },
            messages: {
                en: 'HTML tags or JavaScript code are not allowed.'
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
                error: function (xhr) {
                    // 404 = no template configured for this employee's band;
                    // surface the controller's specific message.
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Error loading templates.';
                    templateContainer.html('<div class="text-danger">' + msg + '</div>');
                    toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        }

        // Open a SweetAlert input asking HR for a replacement email when the
        // candidate's TA email collides with an existing account. Calls
        // onSubmit(newEmail) on confirm or onCancel() on dismiss.
        function promptForReplacementEmail(body, onSubmit, onCancel) {
            const intro = body.is_override
                ? `<p>The email <strong>${body.conflicting_email}</strong> is also in use (by <strong>${body.owner_name}</strong>).</p>
                   <p>Enter a different unique email for <strong>${body.applicant_name}</strong>.</p>`
                : `<p>The email <strong>${body.conflicting_email}</strong> on this candidate's Talent Acquisition record is already used by <strong>${body.owner_name}</strong>.</p>
                   <p>Enter a unique email to use for <strong>${body.applicant_name}</strong>'s new account.</p>`;

            wisdomConfirm({
                role: 'confirm',
                title: 'Email Already In Use',
                confirmText: 'Use this email',
                extra: {
                    html: intro + '<input id="replacementEmail" type="email" class="swal2-input" placeholder="name@example.com" />',
                    focusConfirm: false,
                    preConfirm: function () {
                        const value = (document.getElementById('replacementEmail').value || '').trim();
                        if (!value) {
                            Swal.showValidationMessage('Please enter an email address.');
                            return false;
                        }
                        // Lightweight client-side sanity check; the server still validates.
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                            Swal.showValidationMessage('Please enter a valid email address.');
                            return false;
                        }
                        if (value.toLowerCase() === String(body.conflicting_email).toLowerCase()) {
                            Swal.showValidationMessage('That is the same email — pick a different one.');
                            return false;
                        }
                        return value;
                    }
                }
            }).then(function (result) {
                if (result.isConfirmed && result.value) {
                    onSubmit(result.value);
                } else {
                    onCancel();
                }
            });
        }

        // Step 1 continuation: given a resolved employees.id, load the employee
        // details, store them, and advance to Step 2. Shared by both real
        // employees and applicants that were just auto-converted.
        function proceedStep1WithEmployee($current, selectedEmployee) {
            $('#selectedEmployeeId').val(selectedEmployee);
            localStorage.setItem("employee_id", selectedEmployee);

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

                // --- Pre-fill Step 3 defaults (HR can still edit) ---------------
                // Greeting message — (re)generate whenever it is empty OR still
                // an auto-generated line. Without the re-check, switching the
                // employee left a stale "Dear <previous name>" greeting. A
                // greeting HR has actually customised won't match the auto
                // pattern, so it is left untouched.
                var currentGreeting = $('#greeting_message').val() || '';
                var isAutoGreeting = currentGreeting === ''
                    || /^Dear .*welcome aboard! We are delighted to have you join our team/.test(currentGreeting);
                if (isAutoGreeting) {
                    var who = employeeData.full_name || 'Team Member';
                    $('#greeting_message').val('Dear ' + who + ', welcome aboard! '
                        + 'We are delighted to have you join our team and look forward '
                        + 'to a great journey together.');
                }

                // Arrival date — default to the day before the joining date
                // (the latest date the arrival datepicker allows), only when
                // empty so a re-selection doesn't clobber HR's choice.
                if (employeeData.joining_date && !$('#arrival_date').val()) {
                    var jd = new Date(employeeData.joining_date);
                    if (!isNaN(jd.getTime())) {
                        jd.setDate(jd.getDate() - 1);
                        var dd = String(jd.getDate()).padStart(2, '0');
                        var mm = String(jd.getMonth() + 1).padStart(2, '0');
                        $('#arrival_date').val(dd + '/' + mm + '/' + jd.getFullYear());
                    }
                }

                // Move to next step after employee details are loaded
                moveToNextStep($current, function () {
                    fetchTemplatesForEmployee(selectedEmployee);
                });
            }).catch(function(error) {
                console.error('Error fetching employee details:', error);
                toastr.error("Error loading employee details. Please try again.");
            });
        }

        $(".next").click(function (e) {
            e.preventDefault();
            const $current = $(this).closest("fieldset");
            const currentStep = $current.data("step");

            // Updated Step 1 next button handler
            if (currentStep === 1) {
                const selectedValue = $(".employee-radio:checked").val();
                if (!selectedValue) {
                    toastr.error("Please select an employee.");
                    return;
                }

                // The selected radio is either a real employees.id, or an
                // accepted Talent Acquisition applicant carried as
                // `applicant:<applicant_id>`. In the latter case we first
                // auto-create (or reuse) the Employee record server-side and
                // then continue onboarding with the resulting employees.id.
                if (String(selectedValue).startsWith('applicant:')) {
                    const applicantId = String(selectedValue).split(':')[1];
                    const $btn = $(this);
                    $btn.prop('disabled', true);

                    // Single attempt. On 'email_collision' the caller re-invokes
                    // with overrideEmail collected from a SweetAlert prompt;
                    // any other failure surfaces via toastr.
                    const attemptConvert = function (overrideEmail) {
                        const payload = { applicant_id: applicantId, _token: '{{ csrf_token() }}' };
                        if (overrideEmail) {
                            payload.override_email = overrideEmail;
                        }
                        $.ajax({
                            url: '{{ route("people.onboarding.convertApplicant") }}',
                            method: 'POST',
                            data: payload
                        }).done(function (resp) {
                            $btn.prop('disabled', false);
                            if (resp && resp.success && resp.employee_id) {
                                // Refresh the candidate list so the row now
                                // renders as a real (matched) employee on
                                // subsequent visits.
                                getUpcomingEmployees();
                                proceedStep1WithEmployee($current, resp.employee_id);
                            } else {
                                toastr.error((resp && resp.message) || 'Could not create the employee record.');
                            }
                        }).fail(function (xhr) {
                            const body = xhr.responseJSON || {};
                            if (xhr.status === 422 && body.code === 'email_collision') {
                                // Keep the button disabled across the prompt so
                                // HR can't double-fire while the modal is open.
                                promptForReplacementEmail(body, attemptConvert, function () {
                                    $btn.prop('disabled', false);
                                });
                                return;
                            }
                            $btn.prop('disabled', false);
                            toastr.error(body.message || 'Could not create the employee record.');
                        });
                    };

                    attemptConvert(null);
                    return;
                }

                proceedStep1WithEmployee($current, selectedValue);
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

                        // Append hidden IDs
                        globalFormData.append('employee_id', localStorage.getItem("employee_id"));
                        globalFormData.append('template_id', localStorage.getItem("template_id"));

                        // Store basic form values
                        const fieldsToStore = [
                            'greeting_message', 'arrival_date', 'arrival_time',
                            'domestic_flight_date', 'domestic_arrival_time','domestic_departure_time',
                            'resort_transportaion_id', 'transporation_name','speedboat_name','speedboat_date','speedboat_departure_time', 
                            'speedboat_arrival_time','captain_number', 'location','seaplane_name','seaplane_date','seaplane_departure_time','seaplane_arrival_time',
                            'hotel_id', 'hotel_name', 'hotel_contact_no', 'booking_reference', 'hotel_address',
                            'medical_center_name', 'medical_center_contact_no', 'medical_type', 'medical_date','medical_time','approx_time'
                        ];

                        fieldsToStore.forEach(id => {
                            const value = $('#' + id).val();
                            globalFormData.append(id, value);
                            localStorage.setItem(id, value);
                        });

                        // FIXED: Collect meeting data properly - structured for backend
                        const meetingTitles = [];
                        const meetingDates = [];
                        const meetingTimes = [];
                        const meetingLinks = [];
                        const meetingParticipants = []; // This will be 2D array for each meeting
                        const allUniqueParticipantIds = []; // For display purposes

                        // Collect data from all meeting blocks
                        $('.itineraryTemplateManage-block').each(function(meetingIndex) {
                            const $block = $(this);
                            
                            const title = $block.find('input[name="meeting_title[]"]').val();
                            const date = $block.find('input[name="meeting_date[]"]').val();
                            const time = $block.find('input[name="meeting_time[]"]').val();
                            const link = $block.find('input[name="meeting_link[]"]').val();
                            const participants = $block.find('select[name="participants[]"]').val() || [];

                            if (title) {
                                meetingTitles.push(title);
                                meetingDates.push(date);
                                meetingTimes.push(time);
                                meetingLinks.push(link);
                                
                                // Store participants for this specific meeting
                                meetingParticipants.push(participants);
                                
                                // Collect unique participant IDs for display
                                if (participants && participants.length > 0) {
                                    participants.forEach(id => {
                                        if (id && !allUniqueParticipantIds.includes(id)) {
                                            allUniqueParticipantIds.push(id);
                                        }
                                    });
                                }
                            }
                        });

                        // Store meeting data in localStorage for preview
                        localStorage.setItem('meeting_titles', JSON.stringify(meetingTitles));
                        localStorage.setItem('meeting_dates', JSON.stringify(meetingDates));
                        localStorage.setItem('meeting_times', JSON.stringify(meetingTimes));
                        localStorage.setItem('meeting_links', JSON.stringify(meetingLinks));
                        localStorage.setItem('meeting_participants', JSON.stringify(allUniqueParticipantIds));
                        localStorage.setItem('meeting_participants_structured', JSON.stringify(meetingParticipants)); // New: structured data

                        console.log("📦 Meeting Data Collected:");
                        console.log("Titles:", meetingTitles);
                        console.log("Dates:", meetingDates);  
                        console.log("Times:", meetingTimes);
                        console.log("Links:", meetingLinks);
                        console.log("Structured Participants:", meetingParticipants);
                        console.log("All Unique Participant IDs:", allUniqueParticipantIds);

                        // Rest of your code for fetching employee details...
                        const pickupEmployeeId = $('#pickup_employee_id').val();
                        const medicalEmployeeId = $('#accompany_medical_employee_id').val();
                        
                        Promise.all([
                            fetchEmployeeDetails(pickupEmployeeId),
                            fetchEmployeeDetails(medicalEmployeeId),
                            allUniqueParticipantIds.length > 0 ? fetchParticipantDetails(allUniqueParticipantIds) : Promise.resolve([])
                        ]).then(function([pickupEmployee, medicalEmployee, participants]) {
                            localStorage.setItem('pickup_employee_name', pickupEmployee.full_name);
                            localStorage.setItem('pickup_employee_image', pickupEmployee.profile_image);
                            localStorage.setItem('pickup_employee_emp_id', pickupEmployee.emp_id);
                            
                            localStorage.setItem('medical_employee_name', medicalEmployee.full_name);
                            localStorage.setItem('medical_employee_image', medicalEmployee.profile_image);
                            localStorage.setItem('medical_employee_emp_id', medicalEmployee.emp_id);

                            if (participants && participants.length > 0) {
                                localStorage.setItem('meeting_participants_data', JSON.stringify(participants));
                                console.log("📦 Participants Data Stored:", participants);
                            }

                            moveToNextStep($current);
                            
                            setTimeout(function() {
                                updateStep4Preview();
                            }, 500);
                        }).catch(function(error) {
                            console.error('Error fetching employee/participant details:', error);
                            moveToNextStep($current);
                            setTimeout(function() {
                                updateStep4Preview();
                            }, 500);
                        });

                    }).catch(function(error) {
                        console.error('Error checking meeting conflicts:', error);
                        toastr.error("Error checking meeting conflicts. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            }
        });

        $(".previous").click(function () {
            moveToPreviousStep($(this).closest("fieldset"));
        });

        // Handle form submission
        $(document).on('click', '[data-step="4"] .btn:contains("Submit")', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this);
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
            
            try {
                // Get structured meeting data
                const meetingTitles = JSON.parse(localStorage.getItem('meeting_titles') || '[]');
                const meetingDates = JSON.parse(localStorage.getItem('meeting_dates') || '[]');
                const meetingTimes = JSON.parse(localStorage.getItem('meeting_times') || '[]');
                const meetingLinks = JSON.parse(localStorage.getItem('meeting_links') || '[]');
                const meetingParticipantsStructured = JSON.parse(localStorage.getItem('meeting_participants_structured') || '[]');

                // Clear any existing meeting data in FormData
                // Create fresh FormData to avoid conflicts
                const finalFormData = new FormData();
                
                // Add all basic form data
                const basicFields = [
                    'employee_id', 'template_id', 'greeting_message', 'arrival_date', 'arrival_time',
                    'resort_transportaion_id', 'pickup_employee_id', 'accompany_medical_employee_id',
                    'hotel_id', 'hotel_name', 'hotel_contact_no', 'booking_reference', 'hotel_address',
                    'medical_center_name', 'medical_center_contact_no', 'medical_type', 'approx_time','medical_date',
                    'medical_time'
                ];
                
                basicFields.forEach(field => {
                    const value = localStorage.getItem(field) || $('#' + field).val();
                    if (value) {
                        finalFormData.append(field, value);
                    }
                });

                 // Add transportation-specific fields based on type
                const transportationType = localStorage.getItem('resort_transportaion_id');
                if (transportationType === '3') { // Domestic Flight
                    const domesticFields = [
                        'domestic_flight_date', 'domestic_arrival_time', 'domestic_departure_time'
                    ];
                    domesticFields.forEach(field => {
                        const value = localStorage.getItem(field) || $('#' + field).val();
                        if (value) {
                            finalFormData.append(field, value);
                        }
                    });
                } else if (transportationType === '2') { // Speedboat
                    const speedboatFields = [
                        'speedboat_name', 'speedboat_date', 'speedboat_departure_time',
                        'speedboat_arrival_time', 'captain_number', 'location'
                    ];
                    speedboatFields.forEach(field => {
                        const value = localStorage.getItem(field) || $('#' + field).val();
                        if (value) {
                            finalFormData.append(field, value);
                        }
                    });
                } else if (transportationType === '1') { // Seaplane
                    const seaplaneFields = [
                        'seaplane_name', 'seaplane_date', 'seaplane_departure_time', 'seaplane_arrival_time'
                    ];
                    seaplaneFields.forEach(field => {
                        const value = localStorage.getItem(field) || $('#' + field).val();
                        if (value) {
                            finalFormData.append(field, value);
                        }
                    });
                }

                // Add files
                const entryPassFile = $('#entry_pass_file')[0].files[0];
                const flightTicketFile = $('#flight_ticket_file')[0].files[0];
                const domesticflightTicketFile = $('#domestic_flight_ticket')[0].files[0];

                if (entryPassFile) {
                    finalFormData.append('entry_pass_file', entryPassFile);
                }
                if (flightTicketFile) {
                    finalFormData.append('flight_ticket_file', flightTicketFile);
                }
                if (domesticflightTicketFile) {
                    finalFormData.append('domestic_flight_ticket', domesticflightTicketFile);
                }

                // Add meeting data in the correct structure
                meetingTitles.forEach((title, index) => {
                    finalFormData.append('meeting_title[]', title);
                });
                
                meetingDates.forEach((date, index) => {
                    finalFormData.append('meeting_date[]', date);
                });
                
                meetingTimes.forEach((time, index) => {
                    finalFormData.append('meeting_time[]', time);
                });
                
                meetingLinks.forEach((link, index) => {
                    finalFormData.append('meeting_link[]', link);
                });
                
                // CRITICAL: Add participants in the correct format
                meetingParticipantsStructured.forEach((participantArray, meetingIndex) => {
                    if (participantArray && participantArray.length > 0) {
                        participantArray.forEach((participantId) => {
                            finalFormData.append(`participants[${meetingIndex}][]`, participantId);
                        });
                    }
                });

                // Add CSRF token
                finalFormData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                
                console.log("📦 Final FormData before submission:");
                for (let pair of finalFormData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                // Submit the form
                $.ajax({
                    url: '{{ route("people.onboarding.itinerary.store") }}',
                    method: 'POST',
                    data: finalFormData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success', {
                                positionClass: 'toast-bottom-right',
                                timeOut: 5000
                            });
                            
                            localStorage.clear();
                            $('#msform')[0].reset();
                            
                            setTimeout(function() {
                                window.location.href = "{{ route('people.onboarding.itinerary.list') }}";
                            }, 1500);
                            
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
                            
                        } else {
                            const errorMessage = xhr.responseJSON?.message || 'Error creating onboarding itinerary. Please try again.';
                            toastr.error(errorMessage, 'Error', {
                                positionClass: 'toast-bottom-right',
                                timeOut: 5000
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
                
            } catch (error) {
                console.error('Form submission error:', error);
                toastr.error('An unexpected error occurred. Please try again.', 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                
                submitBtn.prop('disabled', false).text(originalText);
            }
        });

    });
    // Function to show/hide transportation fields based on selection
    function updateTransportationFields() {
        // Match by the transportation NAME (data-option), not the row id —
        // resort_transportations ids differ per resort (5/6/7, 13/14/15…),
        // so the old hardcoded '1'/'2'/'3' comparison never matched and no
        // section was ever revealed.
        var option = ($('#resort_transportaion_id option:selected').data('option') || '')
            .toString().toLowerCase();

        // Hide all sections + drop their required flags first.
        $('.transportation-section').hide();
        $('.transportation-section input').prop('required', false);

        var $section = null;
        if (option.indexOf('seaplane') !== -1) {
            $section = $('#seaplane-section');
        } else if (option.indexOf('speed') !== -1 || option.indexOf('boat') !== -1) {
            $section = $('#speedboat-section');
        } else if (option.indexOf('domestic') !== -1 || option.indexOf('flight') !== -1) {
            $section = $('#domestic-flight-section');
        }

        if ($section && $section.length) {
            $section.show();
            // Only the *visible* section's inputs are required.
            $section.find('input').prop('required', true);
        }
    }
            
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
            $('.datepicker, .meeting-date-input').datepicker('destroy');
            $('.datepicker, .meeting-date-input').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: today,
                endDate: beforeJoining
            });
            $('#meeting_date').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: new Date() // Only restrict to today by default
            });
            
            console.log(`Datepicker updated: Start Date = ${today.toDateString()}, End Date = ${beforeJoining.toDateString()}`);
        }
    }
    // Enhanced function to check meeting conflicts for all participants
    function checkMeetingConflicts(participantIds, meetingDate, meetingTime, currentMeetingBlock = null) {
        if (!participantIds || participantIds.length === 0 || !meetingDate || !meetingTime) {
            return Promise.resolve({ hasConflicts: false, conflicts: [] });
        }

        return $.ajax({
            url: '{{ route("people.onboarding.checkMeetingConflicts") }}',
            method: 'POST',
            data: {
                participant_ids: participantIds,  // Array of participant IDs
                meeting_date: meetingDate,      // Date in YYYY-MM-DD format
                meeting_time: meetingTime,       // Time in HH:MM format
                current_meeting_id: currentMeetingBlock ? $(currentMeetingBlock).data('meeting-id') : null,
                _token: '{{ csrf_token() }}'
            }
        }).then(function(response) {
            return {
                hasConflicts: response.has_conflicts || false,
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
            const dateInput = meetingBlock.find('input[name="meeting_date[]"]');
            const timeInput = meetingBlock.find('input[name="meeting_time[]"]'); 
            const participantsSelect = meetingBlock.find('select[name="participants[]"]');
            
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
    // Format a dd/mm/yyyy string as "15 May 2026" — the project-wide display
    // format (matches the Visa module). Returns the input unchanged if it
    // isn't a parseable dd/mm/yyyy value.
    function fmtDisplayDate(ddmmyyyy) {
        if (!ddmmyyyy) return '';
        var p = String(ddmmyyyy).split('/');
        if (p.length !== 3) return ddmmyyyy;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var d = parseInt(p[0], 10), m = parseInt(p[1], 10), y = p[2];
        if (isNaN(d) || isNaN(m) || m < 1 || m > 12) return ddmmyyyy;
        return (d < 10 ? '0' + d : d) + ' ' + months[m - 1] + ' ' + y;
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
        const employeeImage = localStorage.getItem('employee_image') || '{{ asset('resorts_assets/images/user-2.svg') }}';
        
        const employeeHtml = `
            <div class="col-xl-4 col-lg-5 col-md-6">
                <div class="bg-white">
                    <div class="d-flex align-items-center">
                        <div class="img-circle userImg-block me-md-3 me-2">
                            <img src="${employeeImage}" alt="user" onerror="this.onerror=null;this.src='{{ asset('resorts_assets/images/user-2.svg') }}';">
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
        const pickupEmployeeImage = localStorage.getItem('pickup_employee_image') || '{{ asset('resorts_assets/images/user-2.svg') }}';
        const pickupEmployeeEmpId = localStorage.getItem('pickup_employee_emp_id') || '';
        
        const medicalEmployeeName = localStorage.getItem('medical_employee_name') || '';
        const medicalEmployeeImage = localStorage.getItem('medical_employee_image') || '{{ asset('resorts_assets/images/user-2.svg') }}';
        const medicalEmployeeEmpId = localStorage.getItem('medical_employee_emp_id') || '';

        // Resort transportation — resolve by NAME (data-option on the still-in-DOM
        // select), not by row id. The old '1'/'2'/'3' id comparison never matched
        // real resort_transportations ids (5/6/7…), so the name + detail rows
        // came through blank on this review page.
        const transportationName = ($('#resort_transportaion_id option:selected').data('option') || '').toString();
        const transportationKey  = transportationName.toLowerCase();
        let transportationDetails = '';
        let transporatationName = transportationName || '—';

        if (transportationKey.indexOf('seaplane') !== -1) {
            transportationDetails = `
                <tr><th>Seaplane Date</th><td>${fmtDisplayDate(localStorage.getItem('seaplane_date'))}</td></tr>
                <tr><th>Departure Time</th><td>${localStorage.getItem('seaplane_departure_time') || ''}</td></tr>
                <tr><th>Arrival Time</th><td>${localStorage.getItem('seaplane_arrival_time') || ''}</td></tr>
            `;
        } else if (transportationKey.indexOf('speed') !== -1 || transportationKey.indexOf('boat') !== -1) {
            transportationDetails = `
                <tr><th>Speedboat Name</th><td>${localStorage.getItem('speedboat_name') || ''}</td></tr>
                <tr><th>Speedboat Date</th><td>${fmtDisplayDate(localStorage.getItem('speedboat_date'))}</td></tr>
                <tr><th>Departure Time</th><td>${localStorage.getItem('speedboat_departure_time') || ''}</td></tr>
                <tr><th>Arrival Time</th><td>${localStorage.getItem('speedboat_arrival_time') || ''}</td></tr>
                <tr><th>Captain Number</th><td>${localStorage.getItem('captain_number') || ''}</td></tr>
                <tr><th>Location</th><td>${localStorage.getItem('location') || ''}</td></tr>
            `;
        } else if (transportationKey.indexOf('domestic') !== -1 || transportationKey.indexOf('flight') !== -1) {
            transportationDetails = `
                <tr><th>Domestic Flight Date</th><td>${fmtDisplayDate(localStorage.getItem('domestic_flight_date'))}</td></tr>
                <tr><th>Departure Time</th><td>${localStorage.getItem('domestic_departure_time') || ''}</td></tr>
                <tr><th>Arrival Time</th><td>${localStorage.getItem('domestic_arrival_time') || ''}</td></tr>
            `;
        }

        const onboardingTableRows = `
            <tr><th>Greeting Message:</th><td>${localStorage.getItem('greeting_message') || ''}</td></tr>
            <tr><th>Arrival Date:</th><td>${fmtDisplayDate(localStorage.getItem('arrival_date'))}</td></tr>
            <tr><th>Arrival Time:</th><td>${localStorage.getItem('arrival_time') || ''}</td></tr>
            <tr><th>Pickup From Airport</th><td>
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="${pickupEmployeeImage}" alt="user" onerror="this.onerror=null;this.src='{{ asset('resorts_assets/images/user-2.svg') }}';">
                    </div>
                    <span class="userApplicants-btn">${pickupEmployeeName} (#${pickupEmployeeEmpId})</span>
                </div>
            </td></tr>
            <tr><th>Accompany For The Medical Test</th><td>
                <div class="tableUser-block">
                    <div class="img-circle">
                        <img src="${medicalEmployeeImage}" alt="user" onerror="this.onerror=null;this.src='{{ asset('resorts_assets/images/user-2.svg') }}';">
                    </div>
                    <span class="userApplicants-btn">${medicalEmployeeName} (#${medicalEmployeeEmpId})</span>
                </div>
            </td></tr>
            
            <tr><th>Resort Transportation</th><td>${transporatationName}</td></tr>
            ${transportationDetails}

            {{-- Hotel ID row hidden — field removed from the form. --}}
            <tr><th>Hotel Name</th><td>${localStorage.getItem('hotel_name') || ''}</td></tr>
            <tr><th>Contact No</th><td>${localStorage.getItem('hotel_contact_no') || ''}</td></tr>
            <tr><th>Booking Reference</th><td>${localStorage.getItem('booking_reference') || ''}</td></tr>
            <tr><th>Hotel Address</th><td>${localStorage.getItem('hotel_address') || ''}</td></tr>
            <tr><th>Medical Center Name</th><td>${localStorage.getItem('medical_center_name') || ''}</td></tr>
            <tr><th>Medical Center Contact</th><td>${localStorage.getItem('medical_center_contact_no') || ''}</td></tr>
            <tr><th>Medical Type</th><td>${localStorage.getItem('medical_type') || ''}</td></tr>
            <tr><th>Medical Date</th><td>${fmtDisplayDate(localStorage.getItem('medical_date'))}</td></tr>
            <tr><th>Medical Time</th><td>${localStorage.getItem('medical_time') || ''}</td></tr>
            <tr><th>Approx Time</th><td>${localStorage.getItem('approx_time') || ''}</td></tr>
        `;

        // Update meeting schedules with participant images
        const meetingTitles = JSON.parse(localStorage.getItem('meeting_titles') || '[]');
        const meetingDates = JSON.parse(localStorage.getItem('meeting_dates') || '[]');
        const meetingTimes = JSON.parse(localStorage.getItem('meeting_times') || '[]');
        const meetingLinks = JSON.parse(localStorage.getItem('meeting_links') || '[]');
        // Flat enriched objects for all unique participants across meetings:
        //   [{id, emp_id, full_name, profile_image, …}, …]
        const meetingParticipantsData = JSON.parse(localStorage.getItem('meeting_participants_data') || '[]');
        // Per-meeting IDs from the form, indexed by meeting position:
        //   [[177], [171]]
        const meetingParticipantsStructured = JSON.parse(localStorage.getItem('meeting_participants_structured') || '[]');

        // Quick lookup by id for the enriched objects.
        const participantsById = {};
        meetingParticipantsData.forEach(function (p) {
            if (p && p.id !== undefined && p.id !== null) {
                participantsById[String(p.id)] = p;
            }
        });

        const fallbackImg = '{{ asset('resorts_assets/images/user-2.svg') }}';

        let meetingTableRows = '';
        meetingTitles.forEach(function (title, index) {
            // Pull only THIS meeting's participants (not the merged union).
            const idsForMeeting = (meetingParticipantsStructured[index] || []).map(String);
            const thisMeetingParticipants = idsForMeeting
                .map(function (id) { return participantsById[id]; })
                .filter(Boolean);

            let participantImagesHtml = '';
            if (thisMeetingParticipants.length > 0) {
                participantImagesHtml = thisMeetingParticipants.slice(0, 4).map(function (participant) {
                    var img   = participant.profile_image || fallbackImg;
                    var name  = participant.full_name || 'Unknown';
                    var empId = participant.emp_id || '';
                    return `<div class="img-circle">
                        <img src="${img}" alt="${name}" title="${name} (#${empId})"
                        onerror="this.onerror=null;this.src='${fallbackImg}';">
                    </div>`;
                }).join('');

                if (thisMeetingParticipants.length > 4) {
                    participantImagesHtml += `<div class="img-circle more-participants">+${thisMeetingParticipants.length - 4}</div>`;
                }
            } else {
                participantImagesHtml = '<span class="text-muted">No participants selected</span>';
            }

            meetingTableRows += `
                <tr><th>Meeting Title</th><td>${title}</td></tr>
                <tr><th>Date & Time</th><td>${fmtDisplayDate(meetingDates[index])} - ${meetingTimes[index] || ''}</td></tr>
                <tr><th>Meeting Link</th><td><a href="${meetingLinks[index]}" target="_blank">${meetingLinks[index]}</a></td></tr>
                <tr><th>Participants</th><td>
                    <div class="user-ovImg">
                        ${participantImagesHtml}
                    </div>
                </td></tr>
            `;
        });

        // Update the HTML elements
        $('[data-step="4"] .bg-themeGrayLight:eq(0) .row').html(employeeHtml);
        $('[data-step="4"] .bg-themeGrayLight:eq(1) .row').html(templateHtml);
        $('[data-step="4"] .bg-themeGrayLight:eq(2) .table-responsive table tbody').html(onboardingTableRows);
        $('[data-step="4"] .bg-themeGrayLight:eq(3) .table-responsive table tbody').html(meetingTableRows);
    }
</script>
@endsection