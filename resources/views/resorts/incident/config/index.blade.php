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
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6 ">
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Incident Categories </h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('incident.categories.view')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>
                        <form name="incident-category" id="incident-category" method="POST" action="{{ route('incident.categories.store') }}"  data-parsley-validate>
                            @csrf
                            <div class="incidentCategories-main">
                                <div class="incidentCategories-block">
                                    <div class="row g-2 mb-md-4 mb-3">
                                        <div class="col-12">
                                            <input type="text" class="form-control" id="category_name" name="category_name[]" placeholder="Category" required data-parsley-required-message="Category is required" data-parsley-maxlength="255">                                        
                                        </div>
                                        
                                        <div class="col-12">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm blockAdd-btn">Add
                                                More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Incident Sub-Categories </h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('incident.subcategories.view')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>
                        <form name="incident-sub-category" id="incident-sub-category" method="POST" action="{{ route('incident.subcategories.store') }}" data-parsley-validate>
                            @csrf
                            <div class="incidentCategories-main1">
                                <div class="incidentCategories-block">
                                    <div class="row g-2 mb-md-4 mb-3">
                                        <div class="col-12">
                                            <select name="category_id" id="category_id" class="form-select select2t-none" required data-parsley-required-message="Please select a category" data-parsley-errors-container="#category-error">
                                                <option value="">Select Category</option>
                                                @if($categories)
                                                    @foreach($categories as $category)
                                                        <option value="{{$category->id}}">{{$category->category_name}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="category-error"></div>
                                        </div>
                                        <div class="col-12">
                                        <input type="text" class="form-control" id="subcategory_name"           name="subcategory_name[]" 
                                            placeholder="Sub-category" required 
                                            data-parsley-required-message="Sub-category is required" 
                                            data-parsley-maxlength="255" >
                                        </div>
                                        <div class="col-12">
                                            <select class="form-select select2t-none" name="priority[]" required 
                                            data-parsley-required-message="Please select a priority" data-parsley-errors-container=".priority-error">
                                                <option value="">Priority</option>
                                                <option value="Low">Low</option>
                                                <option value="Medium">Medium</option>
                                                <option value="High">High</option>
                                            </select>
                                            <div class="priority-error"></div>
                                        </div>
                                        <div class="col-12">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm blockAdd-btn" id="add-subCategory">Add More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Resolution Timelines</h3>
                        </div>
                        <form action="{{ route('incident.resolution-timeline.store') }}" method="POST">
                            @csrf
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="high_priority" class="form-label">HIGH PRIORITY</label>
                                    <select class="form-select select2t-none" id="high_priority" name="high_priority">
                                        @php
                                            $selectedHigh = $resoltion_timeline['High'] ?? " ";
                                        @endphp
                                        <option value="">Select Days</option>
                                        @for ($i = 2; $i <= 7; $i++)
                                            <option value="{{ $i }} Business Days" {{ $selectedHigh == "$i Business Days" ? 'selected' : '' }}>
                                                {{ $i }} Business Days
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="medium_priority" class="form-label">MEDIUM PRIORITY</label>
                                    <select class="form-select select2t-none" id="medium_priority" name="medium_priority">
                                        @php
                                            $selectedMedium = $resoltion_timeline['Medium'] ?? " ";
                                        @endphp
                                        <option value="">Select Days</option>
                                        @for ($i = 2; $i <= 7; $i++)
                                            <option value="{{ $i }} Business Days" {{ $selectedMedium == "$i Business Days" ? 'selected' : '' }}>
                                                {{ $i }} Business Days
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="low_priority" class="form-label">LOW PRIORITY</label>
                                    <select class="form-select select2t-none" id="low_priority" name="low_priority">
                                        @php
                                            $selectedLow = $resoltion_timeline['Low'] ?? " ";
                                        @endphp
                                        <option value="">Select Days</option>
                                        @for ($i = 2; $i <= 7; $i++)
                                            <option value="{{ $i }} Business Days" {{ $selectedLow == "$i Business Days" ? 'selected' : '' }}>
                                                {{ $i }} Business Days
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Incident Followup Actions</h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('incident.followup-actions.view')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>

                        <form name="incident-followup-actions" id="incident-followup-actions" method="POST" action="{{ route('incident.followup-actions.store') }}" data-parsley-validate>
                            @csrf
                            <div id="followup-actions-div">
                                <div class="incidentCategories-block">
                                    <div class="row g-2 mb-md-4 mb-3 dynamic-field" data-index="0">
                                        <div class="col-12 col-md-6">
                                            <input type="text" class="form-control" name="followup_actions[]" 
                                                placeholder="Followup Action" required 
                                                data-parsley-required-message="Followup Action is required"
                                                data-parsley-maxlength="255">
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input requires-radio" type="radio" 
                                                    name="requires_employee_statement" value="0">
                                                <label class="form-check-label">Requires employee statement</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm blockAdd-btn" id="add-followup-actions">Add More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Outcome Types </h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('incident.outcome-type.view')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>
                        <form name="incident-outcome-type" id="incident-outcome-type" method="POST" action="{{ route('incident.outcome-type.store') }}" data-parsley-validate>
                            @csrf
                            <div id="outcome-type-div">
                                <div class="incidentCategories-block">
                                    <div class="row g-2 mb-md-4 mb-3">
                                        <div class="col-12">
                                            <input type="text" class="form-control" id="outcome_type" name="outcome_type[]" 
                                            placeholder="Outcome Type" required 
                                            data-parsley-required-message="Outcome Type is required" 
                                            data-parsley-maxlength="255" >
                                        </div>
                                        <div class="col-12">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm blockAdd-btn" id="add-outcome-type">Add More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>

                    <!-- <div class="card mb-30">
                        <div class="card-title">
                            <h3>Approval Roles</h3>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="suspension" class="form-label">SUSPENSION</label>
                                <select class="form-select select2t-none" id="suspension"
                                    aria-label="Default select example">
                                    <option selected>Select Employee</option>
                                    <option value="1">aaa</option>
                                    <option value="2">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="dismissal" class="form-label">DISMISSAL</label>
                                <select class="form-select select2t-none" id="dismissal"
                                    aria-label="Default select example">
                                    <option selected>Select Employee</option>
                                    <option value="1">aaa</option>
                                    <option value="2">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="appeals" class="form-label">APPEALS</label>
                                <select class="form-select select2t-none" id="appeals"
                                    aria-label="Default select example">
                                    <option selected>Select Employee</option>
                                    <option value="1">aaa</option>
                                    <option value="2">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->
                  
                    <!-- <div class="card">
                        <div class="card-title">
                            <h3>Attendance Tracking</h3>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="present" value="attendTracking1"
                                        checked>
                                    <label class="form-check-label" for="present">Present</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="absent" value="attendTracking2">
                                    <label class="form-check-label" for="absent">Absent</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="excused" value="attendTracking3"
                                        checked>
                                    <label class="form-check-label" for="excused">Excused</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->
                </div>
                
                <div class="col-lg-6 ">
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Committees And Assigned Members </h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('incident.committees.view')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>

                        <form id="IncidentCommittees">
                            <div class="commiAssMem-main">
                                <div class="commiAssMem-block">
                                    <div class="row g-2 mb-md-4 mb-3">
                                        <div class="col-6">
                                            <input type="text" class="form-control" name="CommitteeName[]" id="committee" placeholder="Committee Name" required data-parsley-required-message="Committee name is required.">
                                        </div>
                                        <div class="col-6">
                                        <select class="form-select select2t-none" name="members[]" multiple required 
                                            data-parsley-required-message="Please select at least one member."
                                            data-parsley-errors-container=".members-error">
                                            @foreach ($CommitteeMembers as $c)
                                                <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="members-error"></div>
                                        </div>
                                        <div class="col-12">
                                            <a href="#" id="addCommittee" class="btn btn-themeSkyblue btn-sm blockAdd-btn">Add More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm CommitteeSubmit" id="CommitteeSubmit">Submit</button>
                            </div>
                        </form>
                    </div>

                    <!-- <div class="card card-invesTemp mb-30">
                        <div class="card-title">
                            <h3>Investigation Templates</h3>
                        </div>
                        <div class="row g-2 mb-md-4 mb-3">
                            <div class="col-12">
                                <a href="#" class="btn btn-themeSkyblue btn-sm">Create Investigation Templates/Forms</a>
                            </div>
                        </div>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->
                    <!-- <div class="card mb-30">
                        <div class="card-title">
                            <h3>Incident Delegation Rules</h3>
                        </div>
                        <div class="row g-2 mb-md-4 mb-3">
                            <div class="col-12">
                                <select class="form-select select2t-none" aria-label="Default select example">
                                    <option selected>Select Category</option>
                                    <option value="1">aaa</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control" placeholder="Set Rule">
                            </div>
                        </div>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->

                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Status</h3>
                        </div>
                        @php
                            $storedStatus = explode(',', $status->setting_value ?? ''); // Fetch stored severity levels
                        @endphp
                        <form id="saveStatus" data-parsley-validate>
                            <div class="row g-md-3 g-2 mb-md-3 mb-2">
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="reported" value="Reported" {{ in_array('Reported', $storedStatus) ? 'checked' : '' }} data-parsley-mincheck="1">
                                        <label class="form-check-label" for="reported">Reported</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="assigned_to" value="Assigned To" {{ in_array('Assigned To', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="assigned_to">Assigned To</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="acknowledged" value="Acknowledged" {{ in_array('Acknowledged', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="acknowledged">Acknowledged</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="investigation_progress" value="Investigation In Progress" {{ in_array('Investigation In Progress', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="investigation_progress">Investigation In Progress</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="under_review" value="Under Review" {{ in_array('Under Review', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="under_review">Under Review</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="findings_submitted" value="Findings Submitted" {{ in_array('Findings Submitted', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="findings_submitted">Findings Submitted</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" name="status[]" type="checkbox" id="resolution_suggested" value="Resolution Suggested" {{ in_array('Resolution Suggested', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resolution_suggested">Resolution
                                            Suggested</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="status[]" id="approval_pending" {{ in_array('Approval Pending', $storedStatus) ? 'checked' : '' }} value="Approval Pending">
                                        <label class="form-check-label" for="approval_pending">Approval Pending</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="status[]" id="approved" {{ in_array('Approved', $storedStatus) ? 'checked' : '' }} value="Approved">
                                        <label class="form-check-label" for="approved">Approved</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="status[]" id="rejected" {{ in_array('Rejected', $storedStatus) ? 'checked' : '' }} value="Rejected">
                                        <label class="form-check-label" for="rejected">Rejected</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="status[]" id="resolved" value="Resolved" {{ in_array('Resolved', $storedStatus) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="resolved">Resolved</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Meeting Reminder</h3>
                        </div>
                        <form id="saveMeetingReminder">
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="set_reminders" class="form-label">SET REMINDERS FOR THE INVESTIGATION MEETING FOR THE PARTICIPANTS</label>
                                    <select class="form-select select2t-none" id="set_reminders"
                                        aria-label="Default select example">
                                        @php
                                            $selectedValue = isset($meeting_reminder) ? json_decode($meeting_reminder->setting_value, true)['reminder_days'] ?? "" : "";
                                        @endphp
                                        <option value="">Set Reminder</option>
                                        @for ($i = 1; $i <= 7; $i++)
                                            <option value="{{ $i }} Business Days" {{ $selectedValue == "$i Business Days" ? 'selected' : '' }}>
                                                {{ $i }} days before
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>

                    <!-- <div class="card">
                        <div class="card-title">
                            <h3>Preventive Measures</h3>
                        </div>
                        <div class="row g-2 mb-md-4 mb-3">
                            <div class="col-12">
                                <input type="text" class="form-control" placeholder="Category">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control" placeholder="Sub Category">
                            </div>
                        </div>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->

                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Severity Levels</h3>
                        </div>
                        @php
                            $storedSeverities = explode(',', $severity_levels->setting_value ?? ''); // Fetch stored severity levels
                        @endphp
                        <form id="saveSeverity">
                            <div class="row g-2 mb-3">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="minor" value="Minor" name="severity[]" 
                                            {{ in_array('Minor', $storedSeverities) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="minor">Minor</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="moderate" value="Moderate" name="severity[]" 
                                            {{ in_array('Moderate', $storedSeverities) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="moderate">Moderate</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="severe" value="Severe" name="severity[]" 
                                            {{ in_array('Severe', $storedSeverities) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="severe">Severe</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Action Taken </h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('incident.action-taken.view')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>
                        <form name="incident-action-taken" id="incident-action-taken" method="POST" action="{{ route('incident.action-taken.store') }}" data-parsley-validate>
                            @csrf
                            <div id="action-taken-div">
                                <div class="incidentCategories-block">
                                    <div class="row g-2 mb-md-4 mb-3">
                                        <div class="col-12">
                                            <input type="text" class="form-control" id="action_taken" name="action_taken[]" 
                                            placeholder="Action taken" required 
                                            data-parsley-required-message="Action taken is required" 
                                            data-parsley-maxlength="255" >
                                        </div>
                                        <div class="col-12">
                                            <a href="#" class="btn btn-themeSkyblue btn-sm blockAdd-btn" id="add-action-taken">Add More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
<script type="text/javascript">
    $(document).ready(function(){
            // Ensure Parsley is loaded
        if (typeof $.fn.parsley !== 'function') {
            console.error('Parsley.js is not loaded correctly');
            return;
        }
        $('.select2t-none').select2();

        let form1 = $("#incident-sub-category").parsley(); // Initialize Parsley
        let form2 = $("#IncidentCommittees").parsley();
        let form3 = $("#incident-followup-actions").parsley();
        let form4 = $("#incident-outcome-type").parsley();
        let form5 = $("#incident-action-taken").parsley();
        $('#saveStatus').parsley();

        $(document).on("click", "#add-subCategory", function (e) {
            e.preventDefault();
            let uniqueId = Date.now(); // Generate a unique ID for each field group

            var newField = `
                <div class="row g-2 mb-md-4 mb-3 dynamic-field">
                    <div class="col-12">
                        <input type="text" class="form-control" id="subcategory_name"           name="subcategory_name[]" 
                        placeholder="SubCategory" required 
                        data-parsley-required-message="SubCategory is required" 
                        data-parsley-maxlength="255" >
                    </div>
                    <div class="col-12">
                        <select class="form-select select2t-none" name="priority[]" required 
                        data-parsley-required-message="Please select a priority" 
                        data-parsley-errors-container=".priority-error-${uniqueId}">
                            <option value="">Priority</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                        <div class="priority-error-${uniqueId}"></div>
                    </div>
                    <div class="col-12">
                        <a href="#" class="btn btn-danger btn-sm remove-btn" id="remove-subCategory">Remove</a>
                    </div>
                </div>
            `;
            
            $(".incidentCategories-main1").append(newField);
            $('.select2t-none').select2();

                // Reinitialize Parsley for new fields
            form1.destroy();
            $("#incident-sub-category").parsley().reset();
            $("#incident-sub-category").parsley();
        });

        $(document).on("click", "#remove-subCategory", function (e) {
            e.preventDefault();
            $(this).closest(".dynamic-field").remove();
        });

        $("#incident-sub-category").on("submit", function (e) {
            e.preventDefault();
        
            if (!form1.isValid()) {
                form1.validate();
                return false;
            }

            var formData = $(this).serialize();
            
            $.ajax({
                url: "{{ route('incident.subcategories.store') }}",
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 2000);
                },
                error: function (xhr) {
                    toastr.error("Error occurred. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $("#addCommittee").click(function (event) {
            event.preventDefault();
            let uniqueId = Date.now(); // Unique ID for each dynamic field group

            let newField = `
                <div class="commiAssMem-block">
                    <div class="row g-2 mb-md-4 mb-3">
                        <div class="col-6">
                            <input type="text" class="form-control" name="CommitteeName[]" 
                            placeholder="Committee Name" required 
                            data-parsley-required-message="Committee name is required.">
                        </div>
                        <div class="col-6">
                            <select class="form-select select2t-none" name="members[]" multiple required 
                                data-parsley-required-message="Please select at least one member."
                                data-parsley-errors-container=".members-error-${uniqueId}">
                                @foreach ($CommitteeMembers as $c)
                                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                                @endforeach
                            </select>
                            <div class="members-error-${uniqueId}"></div>
                        </div>
                        <div class="col-12 text-end">
                            <a href="#" id="removeCommittee" class="btn btn-danger btn-sm remove-btn">Remove</a>
                        </div>
                    </div>
                </div>`;

            $(".commiAssMem-main").append(newField);

            $(".select2t-none").select2();

            form2.destroy(); // Destroy previous Parsley instance
            form2 = $("#IncidentCommittees").parsley(); // Reinitialize Parsley for new fields

        });

        // Remove Committee Fields
        $(document).on("click", "#removeCommittee", function (event) {
            event.preventDefault();
            $(this).closest(".commiAssMem-block").remove();
            
            form2.destroy();
            form2 = $("#IncidentCommittees").parsley();
        });

        // Form Submission via AJAX
        $("#IncidentCommittees").submit(function (event) {
            event.preventDefault();

            let committees = [];

            $(".commiAssMem-block").each(function () {
                let committeeName = $(this).find('input[name="CommitteeName[]"]').val();
                let members = $(this).find('select[name="members[]"]').val() || []; // Get selected members as an array

                if (committeeName) {
                    committees.push({
                        name: committeeName,
                        members: members
                    });
                }
            });

            let formData = {
                'CommitteeName': committees.map(c => c.name),
                'members': committees.map(c => c.members) // ✅ Group members correctly
            };

            if (!form2.isValid()) {
                form2.validate(); // Trigger Parsley validation if form is invalid
                return;
            }

            $.ajax({
                url: "{{ route('incident.committees.store') }}",
                type: "POST",
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 2000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = "";
                    $.each(errors, function (key, value) {
                        errorMessage += value + "<br>";
                    });
                    
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $("#saveMeetingReminder").submit(function (event) {
            event.preventDefault();

            let reminderValue = $("#set_reminders").val();

            $.ajax({
                url: "{{ route('incident.meeting-reminder.store') }}",
                type: "POST",
                data: {
                    reminder: reminderValue
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    // window.location.reload();
                },
                error: function (xhr) {
                    toastr.error("Error saving Meeting Reminder.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $("#saveSeverity").submit(function (event) {
            event.preventDefault();

            // Get selected severity levels
            let selectedSeverities = [];
            $('input[name="severity[]"]:checked').each(function () {
                selectedSeverities.push($(this).val());
            });

            // Ensure at least one severity is selected
            if (selectedSeverities.length === 0) {
                toastr.error("Please select at least one severity level.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            $.ajax({
                url: "{{ route('incident.severity.store') }}", // Adjust to your actual route
                type: "POST",
                data: {
                    severity: selectedSeverities,
                    _token: "{{ csrf_token() }}" // Ensure CSRF protection
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = "";
                    $.each(errors, function (key, value) {
                        errorMessage += value + "<br>";
                    });
                    toastr.error(errorMessage, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $("#saveStatus").submit(function (event) {
            event.preventDefault();

            // Get selected severity levels
            let selectedStatus = [];
            $('input[name="status[]"]:checked').each(function () {
                selectedStatus.push($(this).val());
            });

            // Ensure at least one status is selected
            if (selectedStatus.length === 0) {
                toastr.error("Please select at least one status.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            $.ajax({
                url: "{{ route('incident.status.store') }}", // Adjust to your actual route
                type: "POST",
                data: {
                    status: selectedStatus,
                    _token: "{{ csrf_token() }}" // Ensure CSRF protection
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = "";
                    $.each(errors, function (key, value) {
                        errorMessage += value + "<br>";
                    });
                    
                    toastr.error(errorMessage, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    
                }
            });
        });

        let followupActionIndex = 1;

        $(document).on("click", "#add-followup-actions", function (e) {
            e.preventDefault();
            let index = followupActionIndex++;

            let newField = `
                <div class="row g-2 mb-md-4 mb-3 dynamic-field" data-index="${index}">
                    <div class="col-12 col-md-6">
                        <input type="text" class="form-control" name="followup_actions[]" 
                            placeholder="Followup Action" required 
                            data-parsley-required-message="Followup Action is required"
                            data-parsley-maxlength="255">
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="form-check mt-2">
                            <input class="form-check-input requires-radio" type="radio" 
                                name="requires_employee_statement" value="${index}">
                            <label class="form-check-label">Requires employee statement</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <a href="#" class="btn btn-danger btn-sm remove-followup-action mt-2">Remove</a>
                    </div>
                </div>
            `;

            $("#followup-actions-div").append(newField);
        });

        $(document).on("click", ".remove-followup-action", function (e) {
            e.preventDefault();
            $(this).closest(".dynamic-field").remove();
        });

        $("#incident-followup-actions").on("submit", function (e) {
            e.preventDefault();

            if (!form3.isValid()) {
                form3.validate();
                return false;
            }

            var formData = $(this).serialize();

            $.ajax({
                url: "{{ route('incident.followup-actions.store') }}",
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function () {
                        window.location.href = response.redirect_url;
                    }, 2000);
                },
                error: function (xhr) {
                    toastr.error("Error occurred. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
        $(document).on("click", "#add-outcome-type", function (e) {
            e.preventDefault();
            let uniqueId = Date.now(); // Generate a unique ID for each field group

            var newField = `
                <div class="row g-2 mb-md-4 mb-3 dynamic-field">
                    <div class="col-12">
                        <input type="text" class="form-control" id="outcome_type" name="outcome_type[]" 
                        placeholder="Outcome Type" required 
                        data-parsley-required-message="Outcome Type is required" 
                        data-parsley-maxlength="255" >
                    </div>
                    <div class="col-12">
                        <a href="#" class="btn btn-danger btn-sm remove-btn" id="remove-outcome-type">Remove</a>
                    </div>
                </div>
            `;
            
            $("#outcome-type-div").append(newField);
            $('.select2t-none').select2();

            // Reinitialize Parsley for new fields
            form1.destroy();
            $("#incident-outcome-type").parsley().reset();
            $("#incident-outcome-type").parsley();
        });

        $(document).on("click", "#remove-outcome-type", function (e) {
            e.preventDefault();
            $(this).closest(".dynamic-field").remove();
        });

        $("#incident-outcome-type").on("submit", function (e) {
            e.preventDefault();
        
            if (!form4.isValid()) {
                form4.validate();
                return false;
            }

            var formData = $(this).serialize();
            
            $.ajax({
                url: "{{ route('incident.outcome-type.store') }}",
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 2000);
                },
                error: function (xhr) {
                    toastr.error("Error occurred. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on("click", "#add-action-taken", function (e) {
            e.preventDefault();
            let uniqueId = Date.now(); // Generate a unique ID for each field group

            var newField = `
                <div class="row g-2 mb-md-4 mb-3 dynamic-field">
                    <div class="col-12">
                        <input type="text" class="form-control" id="action_taken" name="action_taken[]" 
                        placeholder="Action Taken" required 
                        data-parsley-required-message="Action taken is required" 
                        data-parsley-maxlength="255" >
                    </div>
                    <div class="col-12">
                        <a href="#" class="btn btn-danger btn-sm remove-btn" id="remove-action-taken">Remove</a>
                    </div>
                </div>
            `;
            
            $("#action-taken-div").append(newField);
            $('.select2t-none').select2();

            // Reinitialize Parsley for new fields
            form1.destroy();
            $("#incident-action-taken").parsley().reset();
            $("#incident-action-taken").parsley();
        });

        $(document).on("click", "#remove-action-taken", function (e) {
            e.preventDefault();
            $(this).closest(".dynamic-field").remove();
        });

        $("#incident-action-taken").on("submit", function (e) {
            e.preventDefault();
        
            if (!form5.isValid()) {
                form5.validate();
                return false;
            }

            var formData = $(this).serialize();
            
            $.ajax({
                url: "{{ route('incident.action-taken.store') }}",
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 2000);
                },
                error: function (xhr) {
                    toastr.error("Error occurred. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        let form = $("#incident-category").parsley(); // Initialize Parsley

        document.querySelector(".blockAdd-btn").addEventListener("click", function (e) {
            e.preventDefault();
            let newField = `
                <div class="row g-2 mb-md-4 mb-3">
                    <div class="col-12">
                        <input type="text" class="form-control" name="category_name[]" 
                               placeholder="Category" required 
                               data-parsley-required-message="Category is required" 
                               data-parsley-maxlength="255">
                    </div>
                    <div class="col-12">
                        <a href="#" class="btn btn-danger btn-sm remove-btn">Remove</a>
                    </div>
                </div>
            `;
            document.querySelector(".incidentCategories-main").insertAdjacentHTML("beforeend", newField);

            // Reinitialize Parsley for new fields
            form.destroy();
            form = $("#incident-category").parsley();
        });

        document.addEventListener("click", function (e) {
            if (e.target.classList.contains("remove-btn")) {
                e.preventDefault();
                e.target.closest(".row").remove();
            }
        });

        // Submit form using AJAX
        document.getElementById("incident-category").addEventListener("submit", function (e) {
            e.preventDefault();

            if (!form.isValid()) {
                form.validate();
                return false;
            }

            let formData = $(this).serialize();
            let actionUrl = this.action; // Get form action URL
            
            $.ajax({
                url: actionUrl,
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if(response.success){
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        window.location.href = response.redirect_url;
                    }else{
                        
                        toastr.error(response.message,'Error',{
                            positionClass:'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    toastr.error("Error occurred. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
    });

</script>
@endsection