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
        <div class="page-hedding page-appHedding">
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
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                    </fieldset>
                    <fieldset data-parsley-group="block-1">
                        <div class="mt-md-4 mt-2 mb-md-4 mb-3 pb-md-2  text-center">
                            <h4 class="fw-600">Participant Selection</h4>
                        </div>
                        <div class="perPartiSel-block bg-themeGrayLight mb-md-4 mb-3">
                            <div class="row g-md-4 g-3 align-items-end">
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="select_dep" class="form-label">SELECT DEPARTMENT</label>
                                    <select class="form-select select2t-none" id="select_dep" name="select_dep"
                                        aria-label="Default select example">
                                        <option ></option>
                                        @if($ResortDepartment->isNotEmpty())
                                            @foreach ($ResortDepartment as $d)
                                                <option value="{{$d->id}}">{{$d->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="select_position" class="form-label">SELECT POSITION</label>
                                    <select class="form-select select2t-none" id="select_position"  name="select_position"
                                        aria-label="Default select example">
                                        <option ></option>
                                    </select>
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="emp_status" class="form-label">EMPLOYMENT TYPE</label>
                                    <select class="form-select select2t-none" id="emp_status" name="emp_status"
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
                                </div>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <label for="gender" class="form-label">GENDER</label>
                                    <select class="form-select select2t-none" id="gender"  name="gender" aria-label="Default select example">
                                        <option value=""></option>
                                        @foreach ($GenderType as $g)
                                                <option value="{{ $g }}">{{ucfirst($g)}}</option>
                                            @endforeach
                                    </select>
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
                                    <select class="form-select select2t-none" id="Location"  name="Location" aria-label="Default select example">
                                        <option value=""></option>
                                        @foreach ($Location as $g)
                                                <option value="{{  $g }}">{{$g}}</option>
                                            @endforeach
                                    </select>


                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-themeBlue FilterEmployees">Submit</button>
                                    <button type="button" class="btn btn-themeGray ms-2 ResetFilters">Reset</button>
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
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>

                    <fieldset data-parsley-group="block-2">
                        <div class="mt-md-4 mt-2 mb-md-4 mb-3 pb-md-2  text-center">
                            <h4 class="fw-600">Select Template</h4>
                        </div>

                        <div class="row gx-md-4 g-3 mb-md-4 mb-3 justify-content-center">
                            <div class="col-lg-6 col-md-8">
                                <label for="CycleTemplateSelect" class="form-label">TEMPLATE</label>
                                <select class="form-control select2t-none" name="CycleTemplate" id="CycleTemplateSelect" required data-parsley-required-message="Please select a template" data-parsley-group="block-2">
                                    <option value="">Select Template</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-md-block d-none" style="height: 274px;"></div>
                        <hr class="hr-footer ">
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
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
                        <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
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
                        <button type="submit" class=" btn btn-themeBlue btn-sm SubmitCycle float-end" id="SubmitCycle">Submit</button>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
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


            $("#select_dep").select2({
                placeholder: "Select Department",
                allowClear: true
            });
            $("#select_position").select2({
                placeholder: "Select Position",
                allowClear: true
            });

            $("#emp_status").select2({
                placeholder: "Select Status",
                allowClear: true
            });
            $("#Location").select2({
                placeholder: "Select location",
                allowClear: true
            });
            $("#gender").select2({
                placeholder: "Select gender",
                allowClear: true
            });
            $(".SelectTemplete").select2({
                placeholder: "Select Templete",
                allowClear: true
            });

            $("#FormTemplete_1").select2({
                placeholder: "Select Templete",
                allowClear: true
            });
            $("#FormTemplete_0").select2({
                placeholder: "Select Templete",
                allowClear: true
            });

            $('#joining_date_from, #joining_date_to').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,
                    todayHighlight: true
            });
            $('#Step_End_start_date').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,      // Close the picker after selection
                    todayHighlight: true  // Highlight today's date
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
                    if ($el.hasClass('select2-hidden-accessible') || $el.hasClass('select2')) {
                        $el.trigger('change.select2');
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
                $(`#step_four_start_date_${rt.ak},#step_four_end_date_${rt.ak}`).datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,
                    todayHighlight: true
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
                    toastr.error("Please Apply the Filter before You proceed to the next step and select at least one employee before proceeding.", {
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
                let selectedtemp0 = $("#FormTemplete_0").find(":selected").text();

                let selectedtemp1 =  $("#FormTemplete_1").find(":selected").text();
                let newstring ='';
                if(selectedtemp0 == null)
                {
                    newstring +="First Template : "+ selectedtemp0;
                }
                
                if(selectedtemp1 == null)
                {
                    newstring +="First Template : "+ selectedtemp1;
                }
                $("#SelectTempleteview").html(`<span class="fw-600">Template Selected: ${newstring}</span>`);
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
                    $(`#step_four_start_date_${rt.ak},#step_four_end_date_${rt.ak}`).datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true,
                        todayHighlight: true
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
            $('#select_position').html('<option></option>').val(null).trigger('change');
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

        // Initialize Select2 for template dropdown
        $("#CycleTemplateSelect").select2({
            placeholder: "Select Template",
            allowClear: true
        });
        $(".select2t-none").on('change', function () 
        {
                var parsleyField = $(this).parsley();
                parsleyField.validate();
            if (parsleyField.isValid()) 
            {
                $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            }
            else 
            {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
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
                        let string='<option></option>';
                        $.each(d.data.ResortPosition, function(key, value)
                        {
                            string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                        });
                        $("#select_position").html(string);
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
        var FormTemplete_0 = $("#FormTemplete_0 option:selected").text();

        var FormTemplete_1 =  $("#FormTemplete_1 option:selected").text();
        var string = "";

        if(isNaN(FormTemplete_0)) {
            string = "Self Review";
        }

        if(isNaN(FormTemplete_1)) {
            if(isNaN(FormTemplete_0)) {
                string += " - Manager Review";  // Add manager review with separator
            } else {
                string = "Manager Review";  // Only manager review
            }
        }
        $("#SelectTempleteview").html(`<span class="fw-600">Template Selected: ${string}</span>`);
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

            // Check that the end date is strictly after the start date
            return endDate.isAfter(startDate, 'day'); // Ensure day-level comparison
        },
        messages: {
            en: 'End Date must be greater than Start Date.'
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
