@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #performance-create-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #performance-create-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding" id="performance-create-hero">
            <div class="row justify-content-between g-md-2 g-1">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>

            </div>
        </div>

        <div>
            <div class="card">
                <form id="msform" class="performance-form">
                    <!-- progressbar -->
                    <div class="progressbar-wrapper">
                        <ul id="progressbar"
                            class="progressbar-tab d-flex justify-content-between align-items-center ">
                            <li class="active current"> <span>Name and Start Date</span></li>
                            <li><span>Participant</span></li>
                            <li><span>Template</span></li>
                            <li><span>Cycle Summary & Calendar</span></li>
                            <li><span>Confirmation</span></li>
                        </ul>
                    </div>
                    <hr>
                    <fieldset data-parsley-group="block-0">
                        <div class="mt-md-4 mt-2  mb-4">
                            <div class="mb-4 pb-md-3 text-center">
                                <h4 class="fw-600">Cycle Name and Start Date Selection</h4>
                            </div>
                            <div class="row g-md-4 g-3">
                                <div class="col-md-4 col-sm-6">
                                    <label for="cycle_name" class="form-label">CYCLE NAME</label>
                                    <input type="text" class="form-control" id="cycle_name" name="cycle_name" value=""  placeholder="Cycle Name"
                                        data-parsley-required-message="Please enter cycle name"
                                        data-parsley-group="block-0" required>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label for="Step_One_start_date" class="form-label">START DATE</label>
                                    <input type="text" class="form-control datepicker" id="Step_One_start_date" name="Step_One_start_date" placeholder="Select Date" data-parsley-required-message="Please Select Cycle Start Date"
                                    required data-parsley-group="block-0">
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label for="Step_End_start_date" class="form-label">END DATE</label>
                                    <input type="text" class="form-control datepicker" id="Step_End_start_date" name="Step_One_end_date" placeholder="Select Date"
                                    data-parsley-required-message="Please Select Cycle End Date"
                                    required data-parsley-group="block-0" data-parsley-endgreaterthanstart="#Step_One_start_date">
                                </div>
                                <div class="col-md-12 col-sm-12">
                                    <label for="end_date" class="form-label">Summary</label>
                                    <textarea class="form-control" id="CycleSummary" name="CycleSummary" placeholder="Enter Cycle Description" data-parsley-required-message="Please enter Cycle summary"
                                    required data-parsley-group="block-0"></textarea>
                                </div>
                            </div>
                            <div class="d-none d-md-block" style="height: 170px;"></div>
                        </div>
                        <hr class="hr-footer">
                        <a href=" # " class=" btn perf-btn-primary btn-sm float-end next ">Next</a>
                    </fieldset>
                    <fieldset data-parsley-group="block-1">
                        <div class="mt-md-4 mt-2 mb-md-4 mb-3 pb-md-2  text-center">
                            <h4 class="fw-600">Participant Selection</h4>
                        </div>
                        <div class="perPartiSel-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-4 g-3 align-items-end">
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="select_dep" class="form-label">SELECT DEPARTMENT</label>
                                    <select class="form-select dd-native-select" id="select_dep" name="select_dep"
                                        aria-label="Default select example">
                                        <option ></option>
                                        @if($ResortDepartment->isNotEmpty())
                                            @foreach ($ResortDepartment as $d)
                                                <option value="{{$d->id}}">{{$d->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="dd" data-target="#select_dep">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Department</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Department">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @if($ResortDepartment->isNotEmpty())
                                                    @foreach ($ResortDepartment as $d)
                                                    <div class="dd-item" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="select_position" class="form-label">SELECT POSITION</label>
                                    <select class="form-select dd-native-select" id="select_position"  name="select_position"
                                        aria-label="Default select example">
                                        <option value="">Select Position</option>
                                    </select>
                                    <div class="dd" data-target="#select_position">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Position</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Position">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="emp_status" class="form-label">EMPLOYMENT TYPE</label>
                                    <select class="form-select dd-native-select" id="emp_status" name="emp_status"
                                        aria-label="Default select example">
                                     <option value=""></option>
                                        @php
                                            $employmentTypes = ['Full-Time','Part-Time','Contract','Casual','Probationary','Internship','Temporary','Active','Inactive','Terminated','Resigned','On Leave','Suspended'];
                                            $GenderType = config('settings.GenderType');
                                        @endphp
                                        @foreach ($employmentTypes as $s)
                                            <option value="{{ $s }}">{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    <div class="dd" data-target="#emp_status">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Status</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Employment type">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a status…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @foreach ($employmentTypes as $s)
                                                <div class="dd-item" role="option" data-value="{{ $s }}"><span class="dd-nm">{{ $s }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="gender" class="form-label">GENDER</label>
                                    <select class="form-select dd-native-select" id="gender"  name="gender" aria-label="Default select example">
                                        <option value=""></option>
                                        @foreach ($GenderType as $g)
                                                <option value="{{ $g }}">{{ucfirst($g)}}</option>
                                            @endforeach
                                    </select>
                                    <div class="dd" data-target="#gender">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select gender</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Gender">
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select gender</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @foreach ($GenderType as $g)
                                                <div class="dd-item" role="option" data-value="{{ $g }}"><span class="dd-nm">{{ ucfirst($g) }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="joining_date_from" class="form-label">JOINING DATE RANGE</label>
                                    <div class="d-flex gap-2">
                                        <input type="text" class="form-control datepicker" id="joining_date_from" name="joining_date_from" placeholder="From">
                                        <input type="text" class="form-control datepicker" id="joining_date_to" name="joining_date_to" placeholder="To">
                                    </div>

                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="tenure_duration" class="form-label">TENURE DURATION</label>
                                    <input type="number" min="1" class="form-control" id="tenure_duration" name="tenure_duration"
                                        placeholder="Tenure Duration">
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="location" class="form-label">Location</label>
                                    <select class="form-select dd-native-select" id="Location"  name="Location" aria-label="Default select example">
                                        <option value=""></option>
                                        @foreach ($Location as $g)
                                                <option value="{{  $g }}">{{$g}}</option>
                                            @endforeach
                                    </select>
                                    <div class="dd" data-target="#Location">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select location</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Location">
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select location</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @foreach ($Location as $g)
                                                <div class="dd-item" role="option" data-value="{{ $g }}"><span class="dd-nm">{{ $g }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>


                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn perf-btn-primary FilterEmployees">Submit</button>
                                    <button type="button" class="btn perf-btn-neutral ms-2 ResetFilters">Reset</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-title">
                            <h3>Employee</h3>
                        </div>

                        <div class="table-responsive">
                            <table id="table-CycleFilterData" class="table  table-empSelection  w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="form-check no-label">
                                                <input class="form-check-input CycleEmp" type="checkbox" id="Emp_main_id" name="" value=""  >
                                            </div>
                                        </th>
                                        <th>ID </th>
                                        <th>Employee Name </th>
                                        <th>Department</th>
                                        <th>Position</th>
                                        <th>Joining Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <hr class="hr-footer border-0">
                        <a href=" # " class=" btn perf-btn-primary btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn perf-btn-secondary btn-sm float-end previous me-2">Back</a>
                    </fieldset>

                    <fieldset data-parsley-group="block-2">
                        <div class="mt-md-4 mt-2 mb-md-4 mb-3 pb-md-2  text-center">
                            <h4 class="fw-600">Select Template</h4>
                        </div>

                        <div class="row gx-md-4 g-3 mb-md-4 mb-3 justify-content-center">
                            <div class="col-lg-6 col-md-8">
                                <label for="CycleTemplateSelect" class="form-label">TEMPLATE</label>
                                <select class="form-control dd-native-select" name="CycleTemplate" id="CycleTemplateSelect" required data-parsley-required-message="Please select a template" data-parsley-group="block-2">
                                    <option value="">Select Template</option>
                                </select>
                                <div class="dd" data-target="#CycleTemplateSelect">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select Template</span>
                                        <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Template">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-md-block d-none" style="height: 274px;"></div>
                        <hr class="hr-footer ">
                        <a href=" # " class=" btn perf-btn-primary btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn perf-btn-secondary btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-parsley-group="block-3">
                        <div class="mt-md-4 mt-2 mb-md-4 mb-3 pb-md-2  text-center">
                            <h4 class="fw-600">Cycle Summary & Calendar/Activity Setup</h4>
                        </div>
                        <div class="perCycleSum-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-5 g-3">
                                <div class="col-xl-6 col-md-9">
                                    <div class="table-responsive mb-2">
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <th>Objective Name:</th>
                                                    <td class="Cycle_nameStep_4"></td>
                                                </tr>
                                                <tr>
                                                    <th>Start Date & End Date:</th>
                                                    <td class="Cycle_dateStep_4"> </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <h6>Performance summary:</h6>
                                    <p class="Cycle_Step_One_summary"></p>
                                </div>
                                <div class="col-xl-6 col-md-3 col-12">
                                    <h6 class="mb-md-3 mb-2">Employee List</h6>
                                    <div class="append_select_emp">


                                    </div>

                                </div>

                            </div>
                        </div>
                        <div class="card-title mb-md-3">
                            <h3>Activity Scheduling</h3>

                        </div>
                        <div class="perFinalConselect-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-3 g-2">
                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label">REMINDERS</label> <br>
                                    <div class="form-check form-switch form-switchTheme">
                                        <input class="form-check-input" type="checkbox" role="switch" id="CycleReminders" name="CycleReminders">
                                        <label class="form-check-label" for="CycleReminders">Automated Reminders</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 Selected_Review_type" >
                        </div>
                        <hr class="hr-footer">
                        <a href=" # " class=" btn perf-btn-primary btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn perf-btn-secondary btn-sm float-end previous me-2">Back</a>
                    </fieldset>


                    <fieldset>
                        <div class="mt-md-4 mt-2 mb-md-4 mb-3  text-center">
                            <h4 class="fw-600">Final Confirmation</h4>
                        </div>

                        <div class="card-title">
                            <h3>Selected Settings</h3>
                        </div>
                        <div class="perFinalConselect-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-3 g-2">
                                <div class="col-lg-4 col-md-6">
                                    <p id="SelectTempleteview"></p>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <p id="AutoReminder"><span class="fw-600">Automated reminders:</span> Off</p>
                                </div>

                            </div>
                        </div>
                        <div class="card-title">
                            <h3>Cycle Summary</h3>
                        </div>
                        <div class="perCycleSum-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-5 g-3">
                                <div class="col-xl-6 col-md-9">
                                    <div class="table-responsive mb-2">
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <th>Objective Name:</th>
                                                    <td class="Cycle_nameStep_4"></td>
                                                </tr>
                                                <tr>
                                                    <th>Start Date & End Date:</th>
                                                    <td class="Cycle_dateStep_4"> </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <h6>Performance summary:</h6>
                                    <p class="Cycle_Step_One_summary"></p>
                                </div>
                                <div class="col-xl-6 col-md-3 col-12">
                                    <h6 class="mb-md-3 mb-2">Employee List</h6>
                                    <div class="append_select_emp">


                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="card-title">
                            <h3>Activity Scheduling</h3>
                        </div>
                        <div class="perFinalConselect-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-3 g-2">
                                <div class="col-xl-4 col-md-6">
                                    <p id="Manager_review"><span class="fw-600">Manager Review:</span> - </p>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                    <p id="Self_review"><span class="fw-600">Self Review:</span> - </p>
                                </div>
                            </div>
                        </div>
                         <hr class="hr-footer border-0">
                        <button type="submit" class=" btn perf-btn-primary btn-sm SubmitCycle float-end" id="SubmitCycle">Submit</button>
                        <a href=" # " class=" btn perf-btn-secondary btn-sm float-end previous me-2">Back</a>
                    </fieldset>

                        <input type="hidden" name="step_four_start_date_self_hidden" id="step_four_start_date_self_hidden">
                        <input type="hidden" name="step_four_end_date_self_hidden" id="step_four_end_date_self_hidden">
                        <input type="hidden" name="step_four_start_date_manager_hidden" id="step_four_start_date_manager_hidden">
                        <input type="hidden" name="step_four_end_date_manager_hidden" id="step_four_end_date_manager_hidden">         
                </form>



            </div>

        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    .append_select_emp {
        max-height: 320px;
        overflow-y: auto;
        padding-right: 8px;
    }
    .append_select_emp::-webkit-scrollbar { width: 6px; }
    .append_select_emp::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .append_select_emp::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
    // ---- Form persistence across refresh ----
    // Store all form field values + the active step in sessionStorage, restore on page load.
    var PERF_CYCLE_FORM_KEY = 'performanceCycleFormData';
    var PERF_CYCLE_STEP_KEY = 'performanceCycleStep';

    function savePerfCycleForm() {
        var data = {};
        $('#msform').find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name') || $el.attr('id');
            if (!name) return;
            if ($el.attr('type') === 'checkbox') {
                data[name] = $el.is(':checked');
            } else if ($el.attr('type') === 'radio') {
                if ($el.is(':checked')) data[name] = $el.val();
            } else {
                data[name] = $el.val();
            }
        });
        try { sessionStorage.setItem(PERF_CYCLE_FORM_KEY, JSON.stringify(data)); } catch (e) {}
    }

    function restorePerfCycleForm() {
        var raw = sessionStorage.getItem(PERF_CYCLE_FORM_KEY);
        if (!raw) return;
        var data;
        try { data = JSON.parse(raw); } catch (e) { return; }
        if (!data) return;

        $('#msform').find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name') || $el.attr('id');
            if (!name || !(name in data)) return;
            var val = data[name];
            if ($el.attr('type') === 'checkbox') {
                $el.prop('checked', !!val);
            } else if ($el.attr('type') === 'radio') {
                if ($el.val() === val) $el.prop('checked', true);
            } else {
                $el.val(val);
                if ($el.is('select') && this.id) window.wisdomDD.sync('#' + this.id);
            }
        });
    }

    function restorePerfCycleStep() {
        var step = parseInt(sessionStorage.getItem(PERF_CYCLE_STEP_KEY) || '0', 10);
        if (!step || step <= 0) return;
        var $fieldsets = $('#msform fieldset');
        if (step >= $fieldsets.length) step = $fieldsets.length - 1;
        $fieldsets.hide();
        $fieldsets.eq(step).show().css('opacity', 1);
        $('#progressbar li').removeClass('active current');
        for (var i = 0; i <= step; i++) {
            $('#progressbar li').eq(i).addClass('active');
        }
        $('#progressbar li').eq(step).addClass('current');
    }

    $(document).ready(function ()
    {
        var form = $('#msform');

        // Initialize Parsley
        form.parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<span class="invalid-feedback"></span>',
            errorTemplate: '<span></span>',
            trigger: 'change'
        });

        // Persist form on every input change (delegated so cloned/dynamic fields also save)
        $(document).on('input change', '#msform input, #msform select, #msform textarea', function () {
            savePerfCycleForm();
        });

        // Restore saved values + the active step
        restorePerfCycleForm();
        setTimeout(restorePerfCycleStep, 300); // after select2/datepickers init


            $(".SelectTemplete").select2({
                placeholder: "Select Template",
                allowClear: true
            });

            $("#FormTemplete_1").select2({
                placeholder: "Select Template",
                allowClear: true
            });
            $("#FormTemplete_0").select2({
                placeholder: "Select Template",
                allowClear: true
            });

            flatpickr('#joining_date_from, #joining_date_to', {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    appendTo: document.body
            });
            flatpickr('#Step_End_start_date', {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    appendTo: document.body
            });


            var resortId = "{{ $main_resort_id }}";
            var sessionKey = 'resort_' + resortId;
            if (sessionStorage.getItem(sessionKey))
            {
                var savedData = JSON.parse(sessionStorage.getItem(sessionKey));
                $.each(savedData, function(name, value)
                {
                    $('[name="' + name + '"]').val(value);
                });
            }

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        var current_fs, next_fs, previous_fs; //fieldsets
        var opacity;
        var current = 1;
        var steps = $("fieldset").length;

        // Persist form data across refresh
        var FORM_DATA_KEY = 'performanceCycleFormData';
        var SELECTED_EMP_KEY = 'performanceCycleSelectedEmps';

        function saveFormData() {
            var data = {};
            $('#msform').find('input[type="text"], input[type="number"], input[type="date"], textarea').each(function() {
                var name = $(this).attr('name') || $(this).attr('id');
                if (name) data[name] = $(this).val();
            });
            $('#msform').find('select').each(function() {
                var name = $(this).attr('name') || $(this).attr('id');
                if (name) data[name] = $(this).val();
            });
            $('#msform').find('input[type="checkbox"]').each(function() {
                var name = $(this).attr('name') || $(this).attr('id');
                if (name && name !== 'Emp_main_id[]') data[name] = $(this).is(':checked');
            });
            sessionStorage.setItem(FORM_DATA_KEY, JSON.stringify(data));
        }

        function restoreFormData() {
            var saved = sessionStorage.getItem(FORM_DATA_KEY);
            if (!saved) return;
            try { var data = JSON.parse(saved); } catch(e) { return; }

            Object.keys(data).forEach(function(key) {
                var $el = $('#msform').find('[name="' + key + '"], #' + key).first();
                if ($el.length === 0) return;
                if ($el.attr('type') === 'checkbox') {
                    $el.prop('checked', data[key]);
                } else {
                    $el.val(data[key]);
                    if ($el.is('select') && $el.attr('id')) {
                        window.wisdomDD.sync('#' + $el.attr('id'));
                    }
                }
            });
        }

        // Save form data on any input change (debounced)
        var saveTimer;
        $(document).on('input change', '#msform input, #msform select, #msform textarea', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveFormData, 300);
        });

        // Restore form data on page load
        restoreFormData();

        // Re-sync step 4 display from restored step 1 values
        function syncStep4Display() {
            $(".Cycle_nameStep_4").text($("#cycle_name").val() || '');
            $(".Cycle_Step_One_summary").text($("#CycleSummary").val() || '');
            var sd = $("#Step_One_start_date").val();
            var ed = $("#Step_End_start_date").val();
            if (sd || ed) {
                $(".Cycle_dateStep_4").text((sd || '') + " to " + (ed || ''));
            }
        }

        // Rebuild step 4 Activity Scheduling blocks (Self/Manager Review)
        function rebuildActivityScheduling() {
            if ($(".Selected_Review_type").children().length > 0) return;
            $(".Selected_Review_type").html('');
            var reviewTypes = [
                { name: 'Self Review', value: 'Self_Review', ak: 0 },
                { name: 'Manager Review', value: 'Manager_Review', ak: 1 }
            ];
            reviewTypes.forEach(function(rt) {
                $(".Selected_Review_type").append(`
                    <div class="perActSch-block bg-themeGrayLight">
                        <h6>${rt.name}</h6>
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4 col-sm-6">
                                <label for="step_four_start_date_${rt.ak}" class="form-label">START DATE</label>
                                <input type="text" name="ActivityStartDate[${rt.value}]" class="form-control ActiviteStartDate" id="step_four_start_date_${rt.ak}" data-name="${rt.name}" placeholder="Select Date">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label for="step_four_end_date_${rt.ak}" class="form-label">END DATE</label>
                                <input type="text" class="form-control ActiviteStartDate" name="ActivityEndDate[${rt.value}]" id="step_four_end_date_${rt.ak}" placeholder="Select Date" data-name="${rt.name}" data-parsley-endgreaterthanstart="#step_four_start_date_${rt.ak}">
                            </div>
                        </div>
                    </div>
                `);
                flatpickr(`#step_four_start_date_${rt.ak},#step_four_end_date_${rt.ak}`, {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    appendTo: document.body
                });
            });
            // Restore hidden date values after rebuild
            restoreFormData();
        }

        // Restore step from sessionStorage on page load
        var savedStep = sessionStorage.getItem('performanceCycleStep');
        if (savedStep && parseInt(savedStep) > 0) {
            var stepIndex = parseInt(savedStep);
            $("fieldset").hide();
            $("fieldset").eq(stepIndex).show().css('opacity', 1);
            $("#progressbar li").each(function(i) {
                if (i <= stepIndex) $(this).addClass('active');
                if (i === stepIndex) $(this).addClass('current');
                else $(this).removeClass('current');
            });

            // If restoring to step 3+ (Summary/Confirmation), rebuild step 4 content
            if (stepIndex >= 3) {
                syncStep4Display();
                rebuildActivityScheduling();
            }

            // If restoring to step 4 (Final Confirmation), populate confirmation view
            if (stepIndex >= 4) {
                syncConfirmationDisplay();
            }
        }

        function syncConfirmationDisplay() {
            // Reminders
            var reminderOn = $("#CycleReminders").is(":checked") ? "ON" : "OFF";
            $("#AutoReminder").html(`<span class="fw-600">Automated reminders : ${reminderOn}</span>`);

            // Template selected
            var templateName = $("#CycleTemplateSelect option:selected").text() || '-';
            $("#SelectTempleteview").html(`<span class="fw-600">Template Selected: ${templateName}</span>`);

            // Self review dates
            var selfStart = $("#step_four_start_date_0").val() || $("#step_four_start_date_self_hidden").val();
            var selfEnd = $("#step_four_end_date_0").val() || $("#step_four_end_date_self_hidden").val();
            if (selfStart && selfEnd) {
                $("#Self_review").html(`<span class="fw-600"> Self Review : ${selfStart} To ${selfEnd}</span>`);
                $("#step_four_start_date_self_hidden").val(selfStart);
                $("#step_four_end_date_self_hidden").val(selfEnd);
            }

            // Manager review dates
            var mgrStart = $("#step_four_start_date_1").val() || $("#step_four_start_date_manager_hidden").val();
            var mgrEnd = $("#step_four_end_date_1").val() || $("#step_four_end_date_manager_hidden").val();
            if (mgrStart && mgrEnd) {
                $("#Manager_review").html(`<span class="fw-600"> Manager Review : ${mgrStart} To ${mgrEnd}</span>`);
                $("#step_four_start_date_manager_hidden").val(mgrStart);
                $("#step_four_end_date_manager_hidden").val(mgrEnd);
            }
        }

        $(".next").click(function (e) {
                e.preventDefault();

                var currentFieldset = $(this).closest('fieldset');
                var currentGroup = currentFieldset.data('parsley-group');

                // Only tag activity dates when moving from step 4 (block-3)
                if (currentGroup === 'block-3') {
                    $('.ActiviteStartDate').each(function() {
                        $(this).attr({
                            'data-parsley-required': 'true',
                            'data-parsley-group': currentGroup
                        });
                    });
                }

                var form = $('#msform').parsley();
                var isValid = form.validate({ group: currentGroup });
                if (isValid) 
                {
                    var current_fs = $(this).parent();
                    var next_fs = $(this).parent().next();
                    var selectedEmployees = [];
                    $("#table-CycleFilterData tbody input[type='checkbox']:checked").each(function () 
                    {
                        selectedEmployees.push($(this).val());
                    });
                if ( currentGroup == "block-1" && selectedEmployees.length === 0) 
                {
                    toastr.error("Please Apply the Filter before you proceed to the next step and select at least one employee before proceeding.", {
                        positionClass: 'toast-bottom-right'
                    });    
                    return false;
                }       
                $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("current");
                $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active current");

                // Save current step
                sessionStorage.setItem('performanceCycleStep', $("fieldset").index(next_fs));

                next_fs.show();
                current_fs.animate({ opacity: 0 }, {
                    step: function (now) {
                        var opacity = 1 - now;
                        current_fs.css({
                            'display': 'none',
                            'position': 'relative'
                        });
                        next_fs.css({ 'opacity': opacity });
                    },
                    duration: 500
                });

                // var formData = {};

                // $('#msform').find('input, select, textarea, checkbox').each(function () {
                //     var fieldName = $(this).attr('name');
                //     if (fieldName) 
                //     {
                //         formData[fieldName] = $(this).val();
                //     }
                // });

                // sessionStorage.setItem(sessionKey, JSON.stringify(formData));
                // if (formData.cycle_name)
                // {
                //     $('#cycle_name_display').text(formData.cycle_name);
                // }
                let status = $("#CycleReminders").is(":checked") ? "ON" : "OFF";
                let templateName = $("#CycleTemplateSelect option:selected").text().trim();
                let templateHtml = templateName
                    ? `<strong>${templateName}</strong>`
                    : '<em class="text-muted">No template selected</em>';
                $("#SelectTempleteview").html(`<span class="fw-600">Template Selected:</span> ${templateHtml}`);
                $("#AutoReminder").html(`<span class="fw-600">Automated reminders : ${status}</span>`);
                // Build Self Review and Manager Review activity scheduling blocks
                $(".Selected_Review_type").html('');
                var reviewTypes = [
                    { name: 'Self Review', value: 'Self_Review', ak: 0 },
                    { name: 'Manager Review', value: 'Manager_Review', ak: 1 }
                ];
                reviewTypes.forEach(function(rt) {
                    $(".Selected_Review_type").append(`
                        <div class="perActSch-block bg-themeGrayLight">
                            <h6>${rt.name}</h6>
                            <div class="row g-md-4 g-3">
                                <div class="col-md-4 col-sm-6">
                                    <label for="step_four_start_date_${rt.ak}" class="form-label">START DATE</label>
                                    <input type="text" name="ActivityStartDate[${rt.value}]" class="form-control ActiviteStartDate" id="step_four_start_date_${rt.ak}" data-name="${rt.name}" placeholder="Select Date" required data-parsley-required-message="Please select the start date">
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label for="step_four_end_date_${rt.ak}" class="form-label">END DATE</label>
                                    <input type="text" class="form-control ActiviteStartDate" name="ActivityEndDate[${rt.value}]" id="step_four_end_date_${rt.ak}" placeholder="Select Date" data-name="${rt.name}" required data-parsley-required-message="Please select the end date" data-parsley-endgreaterthanstart="#step_four_start_date_${rt.ak}">
                                </div>
                            </div>
                        </div>
                    `);
                    flatpickr(`#step_four_start_date_${rt.ak},#step_four_end_date_${rt.ak}`, {
                        dateFormat: 'd/m/Y',
                        allowInput: true,
                        appendTo: document.body
                    });
                });
                FindSelectedDateStepFour();
                return false;
            } 
            else
            {
                var $form = $('#msform');
                var $firstErrorElement = $form.find('.parsley-error').first();
            }
        });

        $(".previous").click(function () 
        {
            FindSelectedDateStepFour();
            current_fs = $(this).parent();
            previous_fs = $(this).parent().prev();
            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("current");
            $("#progressbar li").eq($("fieldset").index(previous_fs)).addClass("current");

            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

            // Save current step
            sessionStorage.setItem('performanceCycleStep', $("fieldset").index(previous_fs));

            previous_fs.show();
            current_fs.animate({ opacity: 0 },
            {
                step: function (now)
                {
                    opacity = 1 - now;
                    current_fs.css({
                        'display': 'none',
                        'position': 'relative'
                    });
                    previous_fs.css({ 'opacity': opacity });
                },
                duration: 500
            });



       
           
        });
        $(".FilterEmployees").click(function (e)
        {
            e.preventDefault();
            FetchEmployees();
            GetTheTemplete();
        });
        $(".ResetFilters").click(function (e)
        {
            e.preventDefault();
            $('#select_dep').val(null).trigger('change');
            $('#select_position').html('<option value="">Select Position</option>').val(null).trigger('change');
            window.wisdomDD.rebuild('#select_position');
            $('#emp_status').val(null).trigger('change');
            $('#gender').val(null).trigger('change');
            $('#Location').val(null).trigger('change');
            $('#joining_date_from').val('');
            $('#joining_date_to').val('');
            $('#tenure_duration').val('');
            FetchEmployees();
        });
        $('.datepicker').datepicker({format: 'dd/mm/yyyy', autoclose: true}).on('changeDate', function () {
            $(this).parsley().validate();
        });

        // Load templates on page load
        GetTheTemplete();

        $(".dd-native-select").on('change', function ()
        {
                var parsleyField = $(this).parsley();
                parsleyField.validate();
            var $ddTrigger = $(this).siblings('.dd').find('.dd-trigger');
            if (parsleyField.isValid())
            {
                $ddTrigger.removeClass('is-invalid');
            }
            else
            {
                $ddTrigger.addClass('is-invalid');
            }
        });
});


    $(document).on('keyup',"#cycle_name",function(){
        $(".Cycle_nameStep_4").text($(this).val());
    });
    $(document).on('keyup',"#CycleSummary",function(){
        $(".Cycle_Step_One_summary").text($(this).val());
    });
    $(document).on('change',"#Step_One_start_date, #Step_End_start_date ",function()
    {
        var start_date = $("#Step_One_start_date").val();
        var end_date = $("#Step_End_start_date").val();
        $(".Cycle_dateStep_4").text(start_date + " to " + end_date );
    });
    $(document).on('change', '#select_dep', function()
    {
            var deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "post",
                data: {
                    deptId: deptId
                },
                success: function(d)
                {
                    if(d.success == true)
                    {
                        let string='<option value="">Select Position</option>';
                        $.each(d.data.ResortPosition, function(key, value)
                        {
                            string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                        });
                        $("#select_position").html(string);
                        window.wisdomDD.rebuild('#select_position');
                    }
                },
                error: function(response) {
                }
            });
    });
    $(document).on("change", ".CycleEmp", function() {
        
        selectedempoyees();
        FetchEmployees();
        updatePageLength();
    });
    $(document).on("change", ".SelectCycleEmp", function() {
        selectedempoyees();
    });

    $(document).on("change", ".SelectCycleEmp", function() {
        selectedempoyees();
    });


    
    $(document).on("change",`#step_four_start_date_0,#step_four_end_date_0,#step_four_start_date_1,#step_four_end_date_1`, function() {

        var SelfstartDate = $(`#step_four_start_date_0`).val();

        var SelfendDate = $(`#step_four_end_date_0`).val();
        var ManagerStartDate = $(`#step_four_start_date_1`).val();
        var ManagerEndDate = $(`#step_four_end_date_1`).val();
        var selectedTemplateName = $("#CycleTemplateSelect option:selected").text().trim();
        var templateHtmlStr = selectedTemplateName
            ? `<strong>${selectedTemplateName}</strong>`
            : '<em class="text-muted">No template selected</em>';
        $("#SelectTempleteview").html(`<span class="fw-600">Template Selected:</span> ${templateHtmlStr}`);
        if(isNaN(SelfstartDate)  && isNaN(SelfendDate))
        {
            if (SelfstartDate !== undefined && SelfendDate !== undefined) 
            {
                $("#Self_review").html(`<span class="fw-600"> Self Review : ${SelfstartDate} To ${SelfendDate}</span>`);
                $("#step_four_start_date_self_hidden").val(SelfstartDate);
                $("#step_four_end_date_self_hidden").val(SelfendDate);
                
            }
        } 
        if(isNaN(ManagerStartDate) && isNaN(ManagerEndDate))
        {
            if (ManagerStartDate !== undefined && ManagerEndDate !== undefined) 
            {
                $("#Manager_review").html(`<span class="fw-600"> Manager Review : ${ManagerStartDate} To ${ManagerEndDate}</span>`);
                $("#step_four_start_date_manager_hidden").val(ManagerStartDate);
                $("#step_four_end_date_manager_hidden").val(ManagerEndDate);
            }
        }
        return true;
    });
    
  function FindSelectedDateStepFour()
  {
            if(isNaN($("#step_four_start_date_self_hidden").val())  && isNaN($("#step_four_end_date_self_hidden").val()))
                    {
                        $("#step_four_end_date_self_hidden").val($("#step_four_end_date_self_hidden").val() );
                        $("#step_four_start_date_0").datepicker('setDate',$("#step_four_start_date_self_hidden").val());
                        $("#step_four_end_date_0").datepicker('setDate',$("#step_four_end_date_self_hidden").val());
                    }
                    
                    if(isNaN($("#step_four_start_date_manager_hidden").val()) && isNaN($("#step_four_end_date_manager_hidden").val()))
                    {
                        $("#step_four_start_date_1").datepicker('setDate',$("#step_four_start_date_manager_hidden").val());
                        $("#step_four_end_date_1").datepicker('setDate',$("#step_four_end_date_manager_hidden").val());
                    } 
  }
    function FetchEmployees()
    {
       
            if ($.fn.dataTable.isDataTable('#table-CycleFilterData'))
            {
                $('#table-CycleFilterData').DataTable().destroy();
            }

            var TableAccomMainten = $('#table-CycleFilterData').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 10,
                processing: true,
                serverSide: true,
                order:[[1, 'asc']],
                ajax: {
                    url: '{{ route("Performance.cycle.FetchEmployees") }}',
                    type: 'GET',
                    data: function (d)
                    {
                     d.Department = $('#select_dep').val();
                     d.Position   = $("#select_position").val();
                     d.emp_status = $("#emp_status").val();
                     d.Location   = $("#Location").val();
                     d.gender     = $("#gender").val();
                     d.joining_date_from = $("#joining_date_from").val();
                     d.joining_date_to = $("#joining_date_to").val();
                     d.tenure_duration = $("#tenure_duration").val();
                     d.CheckedAll  = $(".CycleEmp").is(":checked");
                    }

                },
                columns: [
                    { data: 'id', name: 'Id', className: 'text-nowrap', orderable: false, searchable: false },
                    { data: 'Emp_id', name: 'EmpID', className: 'text-nowrap' },
                    { data: 'EmployeeName', name: 'EmployeeName', className: 'text-nowrap' },
                    { data: 'DepartmentName', name: 'DepartmentName', className: 'text-nowrap' },
                    { data: 'PositionTitle', name: 'PositionTitle', className: 'text-nowrap' },
                    { data: 'JoiningDate', name: 'JoiningDate', className: 'text-nowrap' },
                    { data: 'status', name: 'status' },
                ]
            });
            TableAccomMainten.on('draw', function () {
                $(".append_select_emp").html(""); 

                    $('input[name="Emp_main_id[]"]:checked').each(function() {
                        let userBlock = $(this).closest("tr").find(".tableUser-block").html();
                        $(".append_select_emp").append(`<div class="tableUser-block">${userBlock}</div>`);
                    });
            });
    }

    function selectedempoyees()
    {
     
        $(".append_select_emp").html("");

        $('input[name="Emp_main_id[]"]:checked').each(function() {
            let userBlock = $(this).closest("tr").find(".tableUser-block").html();
            $(".append_select_emp").append(`<div class="tableUser-block">${userBlock}</div>`);
        });

    }
    function GetTheTemplete()
    {
            var deptId = $("#select_dep").val();
            var position = $("#select_position").val();
            var tenure_duration = $("#tenure_duration").val();
            $.ajax({
                url: "{{ route('Performance.cycle.Template') }}",
                type: "post",
                data:
                {
                    _token: "{{ csrf_token() }}",
                    deptId: deptId,
                    position: position,
                    tenure_duration: tenure_duration
                },
                success: function(d)
                {
                    if(d.success == true)
                    {
                        let string='<option value="">Select Template</option>';
                        $.each(d.data, function(key, value)
                        {
                            string+='<option value="'+value.id+'">'+value.FormName+'</option>';
                        });
                        $('#CycleTemplateSelect').html(string);
                        window.wisdomDD.rebuild('#CycleTemplateSelect');
                    }
                },
                error: function(response) 
                {
                    toastr.error("Something went wrong", { positionClass: 'toast-bottom-right' });
                }
            });
    }

    window.Parsley.addValidator('endgreaterthanstart', {
        validateString: function (endDateValue, startDateSelector) {
            const startDateStr = $(startDateSelector).val();
            const endDate = moment(endDateValue, 'DD/MM/YYYY', true);  // Parse end date
            const startDate = moment(startDateStr, 'DD/MM/YYYY', true);  // Parse start date

            // Check if both dates are valid
            if (!startDate.isValid() || !endDate.isValid()) {
                return true; // Skip validation if any date is invalid or missing
            }

            // End date may equal the start date (a single-day cycle is valid);
            // only reject when it is genuinely before the start date.
            // (Uses !isBefore rather than isSameOrAfter — the latter isn't in
            // this project's older moment.js build.)
            return !endDate.isBefore(startDate, 'day');
        },
        messages: {
            en: 'End Date cannot be before the Start Date.'
        }
    });
    function updatePageLength() 
    {
        var isChecked = $(".CycleEmp").is(":checked");

        if (isChecked) {
            var table = $('#table-CycleFilterData').DataTable();
            $.ajax({
                url: '{{ route("Performance.cycle.FetchEmployees") }}',
                type: 'GET',
                data: {
                    Department: $('#select_dep').val(),
                    Position: $("#select_position").val(),
                    emp_status: $("#emp_status").val(),
                    Location: $("#Location").val(),
                    gender: $("#gender").val(),
                    joining_date: $("#joining_date").val(),
                    tenure_duration: $("#tenure_duration").val(),
                    CheckedAll: true
                },
                success: function (response) {
                    var totalRecords = response.recordsTotal;
                    table.page.len(totalRecords).draw();
                }
            });
        } 
    }
    window.Parsley.addValidator('minSelect', {
        requirementType: 'integer',
        validateString: function(value, requirement) {
            return value.split(',').length >= requirement;
        },
        messages: {
            en: 'You must select at least %s options.'
        }
    });
    window.Parsley.on('field:validated', function (fieldInstance) {
        var $element = fieldInstance.$element;
        if ($element.hasClass('dd-native-select')) {
            var $ddTrigger = $element.siblings('.dd').find('.dd-trigger');
            if (fieldInstance.isValid()) {
                $ddTrigger.removeClass('is-invalid');
            } else {
                $ddTrigger.addClass('is-invalid');
            }
        }
    });

    $(document).on("click", "#SubmitCycle", function(e) 
    {


        
        e.preventDefault();
        var formData = $('#msform').serialize();
        var reminder = $("#CycleReminders").is(":checked") ? "ON" : "OFF";
        formData += '&CycleReminders=' + encodeURIComponent(reminder);
        $.ajax({
                url: "{{ route('Performance.cycle.store') }}",
                type: "post",
                data: formData,
                success: function(d)
                {
                    if(d.success == true)
                    {
                        sessionStorage.removeItem('performanceCycleStep');
                        sessionStorage.removeItem('performanceCycleFormData');
                        sessionStorage.removeItem('performanceCycleSelectedEmps');
                        toastr.success(d.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        setTimeout(function() {
                            window.location.href = "{{ route('Performance.cycle') }}";
                        }, 1200);
                    }
                    else {
                            toastr.error(d.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                },
                error: function(response) 
                {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key,error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        
    });
</script>
@endsection
