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
                            <span>Peple</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <form id="edititiernary">
                @csrf
                <input type="hidden" name="itinerary_id" value="{{ $itinerary->id }}">
                <input type="hidden" name="employee_id" value="{{ $itinerary->employee_id }}">
                <div class="card">
                    <div class="card-header">
                        <div class="mt-2">
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <label for="greeting_message" class="form-label">GREETING MESSAGE <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="greeting_message" name="greeting_message" value="{{ $itinerary->greeting_message }}"
                                        placeholder="Write a Welcome Message"
                                        required
                                        data-parsley-required-message="Please enter welcome message"
                                        data-parsley-script-tag="false"
                                        data-parsley-script-tag-message="Script tags are not allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="arrival_date" class="form-label">Arrival Date <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker" id="arrival_date"
                                        placeholder="Arrival Date" required name="arrival_date" value="{{ $itinerary->arrival_date }}"
                                        data-parsley-required-message="Please select arrival date">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="arrival_time" class="form-label">Arrival Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="arrival_time" name="arrival_time"
                                        placeholder="Arrival Time" required data-parsley-required-message="Please select arrival time" value="{{ $itinerary->arrival_time }}">
                                </div>
                                
                                <!-- Entry Pass File -->
                                <div class="col-lg-4 col-sm-6">
                                    <label for="" class="form-label">ENTRY PASS <span class="red-mark">*</span></label>
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                            <input type="file" id="entry_pass_file" name="entry_pass_file" 
                                                {{ empty($itinerary->entry_pass_file) ? 'required' : '' }} 
                                                data-parsley-required-message="Please upload entry pass" accept=".pdf">
                                        </div>
                                        <div class="uploadFile-text"></div>
                                    </div>
                                    @if (!empty($entry_pass_file) && $entry_pass_file['NewURLshow'] !== "No")
                                        <p class="fileText text-success">
                                            <a href="{{ $entry_pass_file['NewURLshow'] }}" target="_blank" class="ms-2 text-primary">
                                                <i class="fas fa-eye" title="View File">View Entry Pass</i>
                                            </a>
                                        </p>
                                    @else
                                        <p class="fileText text-muted">No file uploaded or file inaccessible</p>
                                    @endif
                                </div>

                                <!-- Flight Ticket File -->
                                <div class="col-lg-4 col-sm-6">
                                    <label for="" class="form-label">FLIGHT TICKET <span class="red-mark">*</span></label>
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                            <input type="file" id="flight_ticket_file" name="flight_ticket_file" 
                                                {{ empty($itinerary->flight_ticket_file) ? 'required' : '' }} 
                                                data-parsley-required-message="Please upload flight ticket" accept=".pdf">
                                        </div>
                                        <div class="uploadFile-text"></div>
                                    </div>
                                    @if (!empty($flight_ticket_file) && $flight_ticket_file['NewURLshow'] !== "No")
                                        <p class="fileText text-success">
                                            <a href="{{ $flight_ticket_file['NewURLshow'] }}" target="_blank" class="ms-2 text-primary">
                                                <i class="fas fa-eye" title="View File">View Flight Ticket</i>
                                            </a>
                                        </p>
                                    @else
                                        <p class="fileText text-muted">No file uploaded or file inaccessible</p>
                                    @endif
                                </div>

                                <div class="col-lg-4 col-sm-6">
                                    <label for="pickup_employee_id" class="form-label">PICKUP FROM AIRPORT <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="pickup_employee_id" name="pickup_employee_id" aria-label="Default select example" required data-parsley-required-message="Please select employee" data-parsley-errors-container="#pickup-error">
                                        <option value="">Select employee</option>
                                        @if($employees)
                                            @foreach($employees as $employee)
                                                <option {{ $employee->id == $itinerary->pickup_employee_id ? "Selected" : ""}} value="{{ $employee->id }}">{{$employee->Emp_id}} - {{ $employee->resortAdmin->full_name }}</option>
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
                                                <option {{$employee->id == $itinerary->accompany_medical_employee_id ? "Selected" : ""}} value="{{ $employee->id }}">{{$employee->Emp_id}} - {{ $employee->resortAdmin->full_name }}</option>
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
                                        placeholder="Resort Transportation" required name="resort_transportaion_id" data-parsley-required-message="Please Select resort transportation" data-parsley-errors-container="#resort_transportation-error" >
                                        <option value="">Select Resort Transporation</option>
                                        @if($transportations)
                                            @foreach($transportations as $key => $value)
                                                <option {{ $key == $itinerary->resort_transportation_id ? "Selected" : ""}} value="{{ $key }}">{{ $value }}</option>
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
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn">
                                                <a href="#" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                                <input type="file" id="domestic_flight_ticket" name="domestic_flight_ticket" 
                                                    {{ empty($itinerary->domestic_flight_ticket) ? 'required' : '' }} 
                                                    data-parsley-required-message="Please upload flight ticket" accept=".pdf">
                                            </div>
                                            <div class="uploadFile-text"></div>
                                        </div>
                                        @if (!empty($domestic_flight_ticket) && $domestic_flight_ticket['NewURLshow'] !== "No")
                                            <p class="fileText text-success">
                                                <a href="{{ $domestic_flight_ticket['NewURLshow'] }}" target="_blank" class="ms-2 text-primary">
                                                    <i class="fas fa-eye" title="View File">View Domestic Flight Ticket</i>
                                                </a>
                                            </p>
                                        @else
                                            <p class="fileText text-muted">No file uploaded or file inaccessible</p>
                                        @endif
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="domestic_flight_date" class="form-label">DOMESTIC FLIGHT DATE <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control datepicker" id="domestic_flight_date"
                                            placeholder="DOMESTIC FLIGHT DATE" required name="domestic_flight_date"
                                            data-parsley-required-message="Please select domestic flight date" value="{{ $itinerary->domestic_flight_date }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="domestic_departure_time" class="form-label">Departure Time <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="domestic_departure_time"
                                            placeholder="Departure Time" required name="domestic_departure_time"
                                            data-parsley-required-message="Please select departure time" value="{{ $itinerary->domestic_departure_time }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="domestic_arrival_time" class="form-label">Arrival Time <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="domestic_arrival_time"
                                            placeholder="Arrival Time" required name="domestic_arrival_time"
                                            data-parsley-required-message="Please select arrival time" value="{{ $itinerary->domestic_arrival_time }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Speedboat Fields -->
                            <div class="transportation-section" id="speedboat-section" style="display:none;">
                                <div class="card-title">
                                    <h3>Speedboat details</h3>
                                </div>
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_name" class="form-label">SPEEDBOAT NAME<span class="red-mark">*</span></label>
                                        <input type="text" class="form-control" id="speedboat_name"
                                            placeholder="Speedboat Name" required name="speedboat_name"
                                            data-parsley-required-message="Please enter speedboat name" value="{{ $itinerary->speedboat_name }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="captain_number" class="form-label">Captain Number <span class="red-mark">*</span></label>
                                        <input type="number" class="form-control" id="captain_number"
                                            placeholder="Captain Number" required name="captain_number"
                                            data-parsley-required-message="Please enter captain number" value="{{ $itinerary->captain_number }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="location" class="form-label">Location <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control" name="location" id="location" placeholder="Location" required data-parsley-required-message="Please enter location" value="{{ $itinerary->location }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_date" class="form-label">SPEEDBOAT DATE <span class="red-mark">*</span></label>
                                        <input type="text" class="form-control datepicker" id="speedboat_date"
                                            placeholder="Speedboat Date" name="speedboat_date"
                                            data-parsley-required-message="Please select speedboat date" value="{{ $itinerary->speedboat_date }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_departure_time" class="form-label">SPEEDBOAT DEPARTURE TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="speedboat_departure_time"
                                            placeholder="Speedboat Departure Time" name="speedboat_departure_time"
                                            data-parsley-required-message="Please select speedboat departure time" value="{{ $itinerary->speedboat_departure_time }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="speedboat_arrival_time" class="form-label">SPEEDBOAT ARRIVAL TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="speedboat_arrival_time"
                                            placeholder="Speedboat Arrival Time" name="speedboat_arrival_time"
                                            data-parsley-required-message="Please select speedboat arrival time" value="{{ $itinerary->speedboat_arrival_time }}">
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
                                            placeholder="Flight Date" name="seaplane_date"
                                            data-parsley-required-message="Please select flight date" value="{{ $itinerary->seaplane_date }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="seaplane_departure_time" class="form-label">DEPARTURE TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="seaplane_departure_time"
                                            placeholder="Departure Time" name="seaplane_departure_time"
                                            data-parsley-required-message="Please select departure time" value="{{ $itinerary->seaplane_departure_time }}">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="seaplane_arrival_time" class="form-label">ARRIVAL TIME <span class="red-mark">*</span></label>
                                        <input type="time" class="form-control" id="seaplane_arrival_time"
                                            placeholder="Arrival Time" name="seaplane_arrival_time"
                                            data-parsley-required-message="Please select arrival time" value="{{ $itinerary->seaplane_arrival_time }}">
                                    </div>
                                </div>
                            </div>

                            <div class="card-title">
                                <h3>Hotel Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_id" class="form-label">Hotel ID <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="hotel_id" id="hotel_id" placeholder="Hotel ID" required data-parsley-required-message="Please enter hotel id" value="{{ $itinerary->hotel_id }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_name" class="form-label">Hotel Name <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="hotel_name" id="hotel_name" placeholder="Hotel Name" required data-parsley-required-message="Please enter hotel name" value="{{ $itinerary->hotel_name }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_contact_no" class="form-label">Contact No <span class="red-mark">*</span></label>
                                    <input type="number" class="form-control" name="hotel_contact_no" id="hotel_contact_no" placeholder="Contact No" required data-parsley-required-message="Please enter contact no" value="{{ $itinerary->hotel_contact_no }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="booking_reference" class="form-label">BOOKING REFERENCE <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="booking_reference" id="booking_reference" placeholder="Booking Reference" required data-parsley-required-message="Please enter booking reference" value="{{ $itinerary->booking_reference }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="hotel_address" class="form-label">Hotel Address <span class="red-mark">*</span></label>
                                    <textarea class="form-control" id="hotel_address" name="hotel_address" placeholder="Hotel Address" required data-parsley-required-message="Please enter hotel address">{{$itinerary->hotel_address}}</textarea>
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Work Permit Medical Center Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_center_name" class="form-label">Medical center Name <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_center_name" placeholder="Medical Center Name" required name="medical_center_name" data-parsley-required-message="Please enter medical center name" value="{{ $itinerary->medical_center_name }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_center_contact_no" class="form-label">Contact No <span class="red-mark">*</span></label>
                                    <input type="number" class="form-control" id="medical_center_contact_no" placeholder="Contact No" name="medical_center_contact_no" required data-parsley-required-message="Please enter contact no" value="{{ $itinerary->medical_center_contact_no }}">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_type" class="form-label">Medical Type <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="medical_type" name="medical_type"
                                        placeholder="Medical Type" required data-parsley-required-message="Please enter medical type" value="{{ $itinerary->medical_type }}">
                                </div>
                                 <div class="col-lg-4 col-sm-6">
                                    <label for="medical_date" class="form-label">Medical Test Date <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker" id="medical_date" name="medical_date"  value="{{ $itinerary->medical_date }}"
                                        placeholder="Medical Test Date" required data-parsley-required-message="Please enter medical type">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="medical_time" class="form-label">Medical Test Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="medical_time" name="medical_time"
                                        placeholder="Medical Test Time" required value="{{ $itinerary->medical_time }}"data-parsley-required-message="Please enter medical type">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="approx_time" class="form-label">Approx Time <span class="red-mark">*</span></label>
                                    <input type="time" class="form-control" id="approx_time" name="approx_time" placeholder="Approx Time" required data-parsley-required-message="Please select approx time" value="{{$itinerary->approx_time}}"/>
                                </div>
                            </div>
                            <!-- Meetings Section -->
                            <div class="card-title">
                                <h3>Schedule Meetings</h3>
                            </div>
                            <div class="itineraryTemplateManage-main">
                                <!-- Existing meetings -->
                                @if(isset($itinerary->meetings) && count($itinerary->meetings) > 0)
                                    @foreach($itinerary->meetings as $index => $meeting)
                                        <div class="itineraryTemplateManage-block meeting-block" data-meeting-id="{{ $meeting->id }}">
                                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                                <div class="col-lg-4 col-sm-6">
                                                    <label for="meeting_title_{{ $index }}" class="form-label">Meeting Title <span class="red-mark">*</span></label>
                                                    <input type="text" class="form-control" id="meeting_title_{{ $index }}"
                                                        placeholder="Meeting Title" name="meeting_title[]" 
                                                        value="{{ $meeting->meeting_title }}" required
                                                        data-parsley-required-message="Please enter meeting title">
                                                    <!-- Hidden field for existing meeting ID -->
                                                    <input type="hidden" name="meeting_id[]" value="{{ $meeting->id }}">
                                                </div>
                                                <div class="col-lg-4 col-sm-6">
                                                    <label for="meeting_date_{{ $index }}" class="form-label">Meeting Date<span class="red-mark">*</span></label>
                                                    <input type="text" class="form-control datepicker" id="meeting_date_{{ $index }}"
                                                        placeholder="Meeting Date" name="meeting_date[]" 
                                                        value="{{ $meeting->meeting_date }}" required
                                                        data-parsley-required-message="Please select meeting date">
                                                </div>
                                                <div class="col-lg-4 col-sm-6">
                                                    <label for="meeting_time_{{ $index }}" class="form-label">Meeting Time<span class="red-mark">*</span></label>
                                                    <input type="time" class="form-control" id="meeting_time_{{ $index }}"
                                                        placeholder="Meeting Time" required name="meeting_time[]"
                                                        value="{{ $meeting->meeting_time }}"
                                                        data-parsley-required-message="Please select meeting time">
                                                </div>
                                                <div class="col-lg-4 col-sm-6">
                                                    <label for="meeting_link_{{ $index }}" class="form-label">Meeting Link <span class="red-mark">*</span></label>
                                                    <input type="text" class="form-control" id="meeting_link_{{ $index }}"
                                                        placeholder="Meeting Link" required name="meeting_link[]"
                                                        value="{{ $meeting->meeting_link }}"
                                                        data-parsley-required-message="Please enter meeting link">
                                                </div>
                                                <div class="col-lg-4 col-sm-6">
                                                    <label for="participants_{{ $index }}" class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                                                    <select class="form-select select2t-none participants-select" id="participants_{{ $index }}" 
                                                        name="participants[{{ $index }}][]" aria-label="Default select example" multiple>
                                                        <option value="">Select employees</option>
                                                        @if($participants)
                                                            @php
                                                                $selectedParticipants = !empty($meeting->meeting_participant_ids) 
                                                                    ? explode(',', $meeting->meeting_participant_ids) 
                                                                    : [];
                                                            @endphp
                                                            @foreach($participants as $participant)
                                                                <option value="{{ $participant->id }}" 
                                                                    {{ in_array($participant->id, $selectedParticipants) ? 'selected' : '' }}>
                                                                    {{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-lg-4 col-sm-6 d-flex align-items-end">
                                                    @if($index > 0 || count($itinerary->meetings) > 1)
                                                        <button type="button" class="btn btn-danger btn-sm remove-meeting-btn">
                                                            Remove Meeting
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <!-- Default meeting block if no existing meetings -->
                                    <div class="itineraryTemplateManage-block meeting-block">
                                        <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="meeting_title_0" class="form-label">Meeting Title <span class="red-mark">*</span></label>
                                                <input type="text" class="form-control" id="meeting_title_0"
                                                    placeholder="Meeting Title" name="meeting_title[]" required
                                                    data-parsley-required-message="Please enter meeting title">
                                                <input type="hidden" name="meeting_id[]" value="">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="meeting_date_0" class="form-label">Meeting Date<span class="red-mark">*</span></label>
                                                <input type="text" class="form-control datepicker" id="meeting_date_0"
                                                    placeholder="Meeting Date" name="meeting_date[]" required
                                                    data-parsley-required-message="Please select meeting date">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="meeting_time_0" class="form-label">Meeting Time<span class="red-mark">*</span></label>
                                                <input type="time" class="form-control" id="meeting_time_0"
                                                    placeholder="Meeting Time" required name="meeting_time[]"
                                                    data-parsley-required-message="Please select meeting time">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="meeting_link_0" class="form-label">Meeting Link <span class="red-mark">*</span></label>
                                                <input type="text" class="form-control" id="meeting_link_0"
                                                    placeholder="Meeting Link" required name="meeting_link[]"
                                                    data-parsley-required-message="Please enter meeting link">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="participants_0" class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                                                <select class="form-select select2t-none participants-select" id="participants_0" 
                                                    name="participants[0][]" aria-label="Default select example" multiple>
                                                    <option value="">Select employees</option>
                                                    @if($participants)
                                                        @foreach($participants as $participant)
                                                            <option value="{{ $participant->id }}">{{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Add More Meeting Button -->
                                <div class="row">
                                    <div class="col-12">
                                        <a href="#" class="btn btn-themeSkyblue btn-sm" id="add-meeting-btn">
                                            Add More Meeting
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-themeSkyblue">Update Itinerary</button>
                        <a href="{{ route('people.onboarding.itinerary.list') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        toggleTransportationSections();
        $('#resort_transportaion_id').change(toggleTransportationSections);
        // Initialize select2 and datepicker
        $(".select2t-none").select2();
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

        let meetingIndex = {{ isset($itinerary->meetings) ? count($itinerary->meetings) : 1 }};

        // Add new meeting block
        $('#add-meeting-btn').on('click', function(e) {
            e.preventDefault();
            
            const newMeetingBlock = `
                <div class="itineraryTemplateManage-block meeting-block">
                    <div class="row g-md-3 g-2 mb-md-4 mb-3">
                        <div class="col-lg-4 col-sm-6">
                            <label for="meeting_title_${meetingIndex}" class="form-label">Meeting Title <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" id="meeting_title_${meetingIndex}"
                                placeholder="Meeting Title" name="meeting_title[]" required
                                data-parsley-required-message="Please enter meeting title">
                            <input type="hidden" name="meeting_id[]" value="">
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="meeting_date_${meetingIndex}" class="form-label">Meeting Date<span class="red-mark">*</span></label>
                            <input type="text" class="form-control datepicker" id="meeting_date_${meetingIndex}"
                                placeholder="Meeting Date" name="meeting_date[]" required
                                data-parsley-required-message="Please select meeting date">
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="meeting_time_${meetingIndex}" class="form-label">Meeting Time<span class="red-mark">*</span></label>
                            <input type="time" class="form-control" id="meeting_time_${meetingIndex}"
                                placeholder="Meeting Time" required name="meeting_time[]"
                                data-parsley-required-message="Please select meeting time">
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="meeting_link_${meetingIndex}" class="form-label">Meeting Link <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" id="meeting_link_${meetingIndex}"
                                placeholder="Meeting Link" required name="meeting_link[]"
                                data-parsley-required-message="Please enter meeting link">
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="participants_${meetingIndex}" class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none participants-select" id="participants_${meetingIndex}" 
                                name="participants[${meetingIndex}][]" aria-label="Default select example" multiple>
                                <option value="">Select employees</option>
                                @if($participants)
                                    @foreach($participants as $participant)
                                        <option value="{{ $participant->id }}">{{$participant->Emp_id}} - {{ $participant->resortAdmin->full_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-lg-4 col-sm-6 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-meeting-btn">
                                Remove Meeting
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $(this).closest('.row').before(newMeetingBlock);
            
            // Initialize select2 and datepicker for new elements
            $(`#participants_${meetingIndex}`).select2();
            $(`#meeting_date_${meetingIndex}`).datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
            
            meetingIndex++;
        });

        // Remove meeting block
        $(document).on('click', '.remove-meeting-btn', function(e) {
            e.preventDefault();
            
            // Check if this is the only meeting block
            if ($('.meeting-block').length > 1) {
                $(this).closest('.meeting-block').remove();
                
                // Update participant select names to maintain proper indexing
                $('.participants-select').each(function(index) {
                    $(this).attr('name', `participants[${index}][]`);
                });
            } else {
                alert('At least one meeting is required.');
            }
        });

        $('#entry_pass_file').on('change', function(){
            let fileName = $(this).val().split('\\').pop();
            if(fileName) {
                $(this).closest('.uploadFile-block').find('.uploadFile-text').html('<span class="text-info">Selected: ' + fileName + '</span>');
                // Hide the existing file text when new file is selected
                $(this).closest('.col-lg-4').find('.fileText').hide();
            } else {
                $(this).closest('.uploadFile-block').find('.uploadFile-text').text('');
                $(this).closest('.col-lg-4').find('.fileText').show();
            }
        });

        // Handle Flight Ticket file change
        $('#flight_ticket_file').on('change', function(){
            let fileName = $(this).val().split('\\').pop();
            if(fileName) {
                $(this).closest('.uploadFile-block').find('.uploadFile-text').html('<span class="text-info">Selected: ' + fileName + '</span>');
                // Hide the existing file text when new file is selected
                $(this).closest('.col-lg-4').find('.fileText').hide();
            } else {
                $(this).closest('.uploadFile-block').find('.uploadFile-text').text('');
                $(this).closest('.col-lg-4').find('.fileText').show();
            }
        });

        $('#domestic_flight_ticket').on('change', function(){
            let fileName = $(this).val().split('\\').pop();
            if(fileName) {
                $(this).closest('.uploadFile-block').find('.uploadFile-text').html('<span class="text-info">Selected: ' + fileName + '</span>');
                // Hide the existing file text when new file is selected
                $(this).closest('.col-lg-4').find('.fileText').hide();
            } else {
                $(this).closest('.uploadFile-block').find('.uploadFile-text').text('');
                $(this).closest('.col-lg-4').find('.fileText').show();
            }
        });

        $('#edititiernary').submit(function(e) {
            e.preventDefault();

            $('.transportation-section').not(':visible').find('[required]').prop('required', false);

            
            if ($(this).parsley().isValid()) {
                var formData = new FormData(this);
                
                // Add participants to formData
                $('.participants-select').each(function(index) {
                    var participants = $(this).val();
                    if (participants) {
                        participants.forEach(function(participantId) {
                            formData.append('participants[' + index + '][]', participantId);
                        });
                    }
                });
                
                $.ajax({
                    url: "{{ route('people.onboarding.itinerary.update', $itinerary->id) }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        // Show loading indicator
                        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href = "{{ route('people.onboarding.itinerary.list') }}";
                            }, 1500);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        $('button[type="submit"]').prop('disabled', false).text('Update Itinerary');
                        
                        if (xhr.status === 422) { // Validation error
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $.each(messages, function(index, message) {
                                    toastr.error(message);
                                });
                            });
                        } else {
                            toastr.error(xhr.responseJSON.message || 'An error occurred');
                        }
                        
                        // Scroll to the first error
                        $('html, body').animate({
                            scrollTop: $('.is-invalid').first().offset().top - 100
                        }, 500);
                    },
                    complete: function() {
                        $('button[type="submit"]').prop('disabled', false).text('Update Itinerary');
                    }
                });
            }
        });

    });

    function toggleTransportationSections() {
        const selectedVal = $('#resort_transportaion_id').val();

        // Hide all sections initially and remove required attributes
        $('.transportation-section').hide().find('[required]').prop('required', false);
        
        // Reset file input requirements
        $('#domestic_flight_ticket').prop('required', false);
        $('#speedboat_name').prop('required', false);
        $('#seaplane_date').prop('required', false);

        if (selectedVal == '3') {
            $('#domestic-flight-section').show();
            // Add required only to visible fields
            $('#domestic-flight-section').find('[data-parsley-required-message]').prop('required', true);
        } else if (selectedVal == '2') {
            $('#speedboat-section').show();
            $('#speedboat-section').find('[data-parsley-required-message]').prop('required', true);
        } else if (selectedVal == '1') {
            $('#seaplane-section').show();
            $('#seaplane-section').find('[data-parsley-required-message]').prop('required', true);
        }
    }
</script>
@endsection
