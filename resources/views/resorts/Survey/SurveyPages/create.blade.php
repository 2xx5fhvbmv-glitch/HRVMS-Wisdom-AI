@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding">
            <div class="row justify-content-between g-md-2 g-1">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Survey</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <form id="msform" class="createSurvey-form" data-parsley-validate>

              
                    <!-- progressbar -->
                    <div class="progressbar-wrapper">
                        <ul id="progressbar"
                            class="progressbar-tab d-flex justify-content-between align-items-center">
                            <li class="active current"> <span>Question Setup</span></li>
                            <li><span>Participant Selection</span></li>
                            <li><span>Configuration</span></li>
                        </ul>
                    </div>
                    <fieldset data-parsley-group="block-0">
                        <div class="surveyTitle-block bg-themeGrayLight mb-3 mb-md-4">
                            <label for="survey_title" class="form-label">Survey Title</label>
                            <input type="text" class="form-control" id="survey_title"  name="survey_title" data-parsley-required-message="Please Enter Survey Title"
                            required data-parsley-group="block-0" required  placeholder="Survey Title">
                        </div>
                        <div class=" mb-md-4 mb-3">
                            <div class="row g-md-4 g-3 mb-2">
                                <div class="col-md-12 AppendHerer">

                                </div>
                            </div>
                            <hr class="hr-footer mt-3">
                            <div class="row g-md-4 g-3 mb-2 align-items-end">
                        
                                <div class="col-sm-6">
                                    <label for="question_type" class="form-label">QUESTION TYPE</label>
                                 
                                    <select class="form-select que_type select2t-none" 
                                        name="que_type" 
                                        id="que_type_1" 
                                        data-id="1"
                                        required
                                        data-parsley-required-message="Please select a question type"
                                        data-parsley-group="block-0"
                                        data-parsley-trigger="change"
                                        data-parsley-errors-container="#que_type_error"
                                        >
                                    <option value="">Select question type</option>
                                    <option value="text">Text</option>
                                    <option value="multiple">Multi-Choice</option>
                                    <option value="Radio">Single Choice</option>
                                    <option value="Rating">Rating/Scaling Button</option>
                                 </select>
                                <div id="que_type_error"></div>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="question_type" class="form-label"></label>

                                    <a href="javascript:void(0);" class="AddMore btn btn-themeSkyblue btn-sm ">Add New Question</a>
                                </div>
                            </div>
                    
                        
                            
                        </div>
                        <div class="d-none d-md-block" style="height: 50px;"></div>
                        <hr class="hr-footer mb-3">
                        <div class="d-inline-flex align-items-center">
                            {{-- <a href="#" class="a-link">Save As Draft</a> --}}
                        </div>
                        <a href="javascript:void(0)" class=" btn btn-themeBlue btn-sm float-end next ">Next</a>

                    </fieldset>
                    <fieldset data-parsley-group="block-1">
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-8 col-lg-7 sumDisEmpDetail-block">
                                <div class="row  gx-md-4 g-3 align-items-end">
                                    <div class="col-12">
                                        <label for="select_emp" class="form-label">SELECT PARTICIPANTS</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"  name="selectParticipants" id="selectParticipants1" value="Everyone">
                                                <label class="form-check-label"
                                                    for="selectParticipants1">Everyone</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="selectParticipants" id="selectParticipants2"value="Selective" checked>
                                                <label class="form-check-label"
                                                    for="selectParticipants2">Selective</label>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Filters: shown only when Selective; hidden when Everyone --}}
                                    <div class="col-auto participant-filters-actions">
                                        <div class="position-relative">
                                            <a href="javascript:void(0)"
                                                class="btn btn-themeGrayLight filters-btn">Filters<i
                                                    class="fa-solid fa-angle-down"></i></a>
                                        </div>
                                    </div>
                                    <div class="col-auto participant-filters-actions">
                                        <a href="javascript:void(0)" class="a-link d-inline-block mb-md-3 mb-3 ClearFilter">Clear Filter</a>
                                    </div>
                                    <div class="col-12">
                                        <div class="filters-block d-none participant-filters-block">
                                            <div class="row g-md-4 g-3 align-items-end">
                                                <div class="col-sm-6 col-lg-3">
                                                    <label for="department" class="form-label">DEPARTMENT</label>
                                                    <select class="form-select select2-multi" id="department" name="department[]" multiple="multiple" data-placeholder="Department">
                                                        @if( $ResortDepartment->isNotEmpty())
                                                            @foreach ($ResortDepartment as $d)
                                                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-sm-6 col-lg-3">
                                                    <label for="position" class="form-label">POSITION</label>
                                                    <select class="form-select select2-multi" name="position[]" id="position" multiple="multiple" data-placeholder="Position">
                                                    </select>
                                                </div>
                                                <div class="col-sm-6 col-lg-3">
                                                    <label for="employment_grade" class="form-label">EMPLOYMENT TYPE (Grade)</label>
                                                    @php $grade = config('settings.eligibilty'); @endphp
                                                    <select class="form-select select2-multi" name="employment_grade[]" id="employment_grade" multiple="multiple" data-placeholder="Type">
                                                        @if( !empty($grade))
                                                            @foreach ($grade as $k => $d)
                                                                <option value="{{ $k }}">{{ $d }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-sm-6 col-lg-3">
                                                    <label for="gender" class="form-label">GENDER</label>
                                                    <select class="form-select select2t-none" name="gender" id="gender" data-placeholder="Male / Female">
                                                        <option value="">Male / Female</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="javascript:void(0)" class="FilterSubmit btn btn-themeBlue">Submit</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-5">
                                <div class="bg-themeGrayLight sumDisEmp-block">
                                    <div class="card-title mb-md-3">
                                        <h3>Selected Participants</h3>
                                        <p class="text-muted small mb-0 d-none" id="everyoneLabel">All resort employees are selected (non-editable).</p>
                                    </div>
                                    <input type="text" class="form-control" id="searchEmployee" placeholder="Search employees...">
                                    <div class="overflow-auto pe-1" id="employeeList">                                      
                                        
                                        @if( $emp->isNotEmpty())
                                            @foreach ($emp  as $e)
                                                <div class="d-flex employee-item"> {{-- Parent element for each employee --}}
                                                    <div class="img-circle userImg-block">
                                                        <img src="{{ $e->profileImg }}" alt="user">
                                                    </div>
                                                    <div class="employee-details">
                                                        <h6 class="employee-name">{{ $e->EmployeeName }}</h6>
                                                        <p class="position-name">{{ $e->positionName }}</p>
                                                    </div>
                                                    <div class="form-check no-label">
                                                        <input class="form-check-input" type="checkbox" name="Emp_id[]" value="{{ $e->Emp_id }}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        {{-- <a href="#" class="a-link me-1">Save As Draft</a> --}}
                        <a href="javascript:void(0) " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href="javascript:void(0)" class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>

                    <fieldset data-parsley-group="block-2">
                        <div class="mb-3">
                            <label for="select_emp" class="form-label">PRIVACY</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="survey_privacy_type" id="privacy1"
                                        value="Confidential" data-parsley-required="true"
                                        data-parsley-required-message="Please select a privacy type"
                                        data-parsley-errors-container="#privacy_error"
                                        data-parsley-group="block-2" checked>
                                    <label class="form-check-label" for="privacy1">Confidential</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="survey_privacy_type" id="privacy2"
                                        value="Neutral">
                                    <label class="form-check-label" for="privacy2">Neutral</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="survey_privacy_type" id="privacy3"
                                        value="Anonymous">
                                    <label class="form-check-label" for="privacy3">Anonymous</label>
                                </div>
                            </div>
                            <div id="privacy_error"></div>
                        </div>
                        
                        <div class="card-title">
                            <div class="row justify-content-start align-items-center g-">
                                <div class="col"></div>
                            </div>
                        </div>
                        
                        <div class="row g-md-4 g-3 mb-md-4 mb-3 ">
                            <div class="col-sm-6">
                                <label for="startDate" class="form-label">START DATE</label>
                                <input type="text" class="form-control " id="startDate_step_3" name="startDate" 
                                    placeholder="Start Date" data-parsley-required="true"
                                    data-parsley-required-message="Please select a start date"
                                    data-parsley-group="block-2"
                                    data-parsley-trigger="change">
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="text" class="form-control " id="endDate" name="endDate" 
                                    placeholder="End Date" data-parsley-required="true"
                                    data-parsley-required-message="Please select an end date"
                                    data-parsley-group="block-2"
                                    data-parsley-trigger="change" data-parsley-endgreaterthanstart="#startDate_step_3">
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="recurringSurvey" class="form-label">RECURRING SURVEY</label>
                                <select class="form-select select2t-none" id="recurringSurvey" name="recurring_survey"
                                    data-parsley-required="true"
                                    data-parsley-required-message="Please select a recurring survey option"
                                    data-parsley-group="block-2"
                                    data-parsley-trigger="change">
                                    <option value="One time">One time</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                    <option value="Annually">Annually</option>
                                </select>
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="reminderNotification" class="form-label">REMINDER NOTIFICATIONS</label>
                                <select class="form-select select2-multi" name="reminderNotification[]" id="reminderNotification" multiple="multiple"
                                    data-placeholder="Days before expiry"
                                    data-parsley-required="true"
                                    data-parsley-required-message="Please select at least one reminder notification"
                                    data-parsley-group="block-2"
                                    data-parsley-trigger="change">
                                    @for($i=1; $i<=10; $i++)
                                    <option value="{{ $i }}">{{ $i }} Days Before Expiry</option>
                                    @endfor
                                </select>
                            </div>
                            
                            <div class="col-lg-6 confiCreateSurvey-switch">
                                <div class="d-flex align-items-center mb-md-3 mb-2">
                                    <label class="form-label md-md-0 mb-1 me-xl-3 me-2" for="flexSwitchCheckDefault">
                                        Allow Participants To Edit Their Responses After Submission
                                    </label>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch" name="surevey_editable" 
                                            id="flexSwitchCheckDefault">
                                        <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="hr-footer">
                        <a href="javascript:void(0)" data-flag="SaveAsDraft" class="a-link me-md-3 me-1 SubmitAsSaveAsDraft ">
                            Save As Draft
                        </a>
                        <a href="javascript:void(0)" class="a-linkTheme me-1 SurveyPreview" role="button">Preview</a>
                        <button type="submit" data-flag="Publish" class="btn btn-themeBlue btn-sm float-end SubmitAsPublish ">
                            Publish Survey
                        </button>
                        <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm float-end previous me-2">
                            Back
                        </a>
                    </fieldset>
                    <input type="hidden" id="increment" value="1">
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
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
@endsection

@section('import-scripts')
<script >

    $(document).ready(function () 
    {
        $('.datepicker').datepicker({});

        $('#endDate').attr('data-parsley-date-after', 'startDate_step_3');
        var current_fs, next_fs, previous_fs; //fieldsets
        var opacity;
        var current = 1;
        var steps = $("fieldset").length;
        var form = $('#msform');
        $('#startDate_step_3').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
        });
        $('#endDate').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
        });
            var $form = $("#msform");
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

            $('#startDate_step_3').on('change', function () {
                $('#endDate').parsley().validate();
            });
        $("#que_type_1").select2({
            "Placeholder": "Select Question Type",
            "width": "100%"
        });


        $(".next").click(function (e) {
        e.preventDefault();
        var currentFieldset = $(this).closest('fieldset');
        var currentGroup = currentFieldset.data('parsley-group');
        var form = $('#msform').parsley();
       
        if (!form.validate({group: currentGroup})) 
        {
            return false;
        }
        var selectedEmployees = [];
            $("input[name='Emp_id[]']:checked").each(function () {
                selectedEmployees.push($(this).val());
            });
        var isEveryone = $("input[name='selectParticipants']:checked").val() === 'Everyone';
        if (currentGroup == "block-1" && !isEveryone && selectedEmployees.length === 0) {
            toastr.error("Please Apply the Filter before You proceed to the next step and select at least one employee before proceeding.", "Error",
            { positionClass: 'toast-bottom-right' });
            return false;
        }  

        
        // Navigation logic (this part seems fine)
        var current_fs = $(this).parent();
        var next_fs = $(this).parent().next();
        
        // Update progress bar
        $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("current");
        $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active current");
        
        // Show next fieldset with animation
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
        
        return false;
    });


        $(".previous").click(function () {

            current_fs = $(this).parent();
            previous_fs = $(this).parent().prev();
            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("current");
            $("#progressbar li").eq($("fieldset").index(previous_fs)).addClass("current");

            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");
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



        $(".SurveyPreview").on("click", function (e) {
            e.preventDefault();
            $('#msform').data('preview-after-save', true);
            $(".SubmitAsSaveAsDraft").click();
        });

        $(".SubmitAsPublish , .SubmitAsSaveAsDraft").click(function (e) 
        {
            e.preventDefault(); // Prevent default form submission

            var flag = $(this).data('flag');
            var currentFieldset = $(this).closest('fieldset');
            var currentGroup = currentFieldset.data('parsley-group');

            var form = $('#msform'); // Select the form
            var parsleyForm = form.parsley(); // Initialize Parsley

            if (!parsleyForm.validate({ group: currentGroup })) {
                return false; // Stop execution if validation fails
            }
            var formData = new FormData($('#msform')[0]); // Correct FormData initialization
            formData.append('Status', flag);
            $.ajax({
                url: "{{ route('Survey.store') }}", 
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $(".SubmitAsPublish , .SubmitAsSaveAsDraft").prop("disabled", true); // Disable buttons
                },
                success: function (response) {
                    $(".SubmitAsPublish , .SubmitAsSaveAsDraft").prop("disabled", false); // Enable buttons

                    if (response.success) {
                        if ($('#msform').data('preview-after-save')) {
                            $('#msform').removeData('preview-after-save');
                            window.open(response.route, '_blank');
                            toastr.success('Preview opened in new tab.', 'Success', { positionClass: 'toast-bottom-right' });
                            return;
                        }
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        form[0].reset(); // Reset form after success
                        parsleyForm.reset(); // Reset Parsley validation

                        window.location.href = response.route;
                    
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (response) {
                    $(".SubmitAsPublish , .SubmitAsSaveAsDraft").prop("disabled", false); // Enable buttons
                    $('#msform').removeData('preview-after-save');

                    var errors = response.responseJSON;
                    var errorMsg = '';

                    if (errors && errors.errors) {
                        $.each(errors.errors, function (key, error) {
                            errorMsg += error + '<br>';
                        });
                    } else {
                        errorMsg = "An unexpected error occurred.";
                    }

                    toastr.error(errorMsg, "Error", { positionClass: 'toast-bottom-right' });
                    console.error(response); // Log for debugging
                }
            });

            return false; // Prevent default form submission
        });


        // $(".filters-btn").hover(
        //     function () {
        //         $(this).addClass("active"); // Add class on hover
        //         $(".filters-block").removeClass("d-none");
        //     },
        //     // function () {
        //     //     $(this).removeClass("active"); // Remove class when not hovering
        //     //     $(".filters-block").addClass("d-none");
        //     // }
        // );

        $(".filters-btn").click(function () {
            $(this).toggleClass("active");
            $(".filters-block").toggleClass("d-none");
            if (!$(".filters-block").hasClass("d-none")) {
                var deptVal = $("#department").val();
                if (!deptVal || !deptVal.length) loadPositionOptions(null);
            }
        });

        // Participant Selection: Everyone = all resort employees, non-editable, hide filters
        $("input[name='selectParticipants']").on("change", function () {
            var value = $(this).val();
            if (value === "Everyone") {
                $(".participant-filters-actions").addClass("d-none");
                $(".participant-filters-block").addClass("d-none");
                $(".filters-btn").removeClass("active");
                $("#searchEmployee").prop("disabled", true).addClass("bg-light");
                $("#everyoneLabel").removeClass("d-none");
                loadAllResortEmployees();
            } else {
                $(".participant-filters-actions").removeClass("d-none");
                $("#searchEmployee").prop("disabled", false).removeClass("bg-light");
                $("#everyoneLabel").addClass("d-none");
                loadSelectiveEmployees();
            }
        });

        function loadAllResortEmployees() {
            $.ajax({
                url: "{{ route('Survey.getAllEmployees') }}",
                type: "GET",
                success: function (response) {
                    var employeeList = $("#employeeList");
                    employeeList.empty();
                    if (response.success && response.data && response.data.length > 0) {
                        response.data.forEach(function (e) {
                            var html = '<div class="d-flex employee-item">' +
                                '<div class="img-circle userImg-block"><img src="' + (e.profileImg || '') + '" alt="user"></div>' +
                                '<div class="employee-details"><h6 class="employee-name">' + (e.EmployeeName || '') + '</h6><p class="position-name">' + (e.positionName || '') + '</p></div>' +
                                '<div class="form-check no-label"><input class="form-check-input" type="checkbox" name="Emp_id[]" value="' + (e.Emp_id || '') + '" checked disabled></div>' +
                                '</div>';
                            employeeList.append(html);
                        });
                    } else {
                        employeeList.append("<p class='text-muted'>No employees found for this resort.</p>");
                    }
                },
                error: function () {
                    $("#employeeList").empty().append("<p class='text-danger'>Failed to load employees.</p>");
                    toastr.error("Failed to load employees.", "Error", { positionClass: "toast-bottom-right" });
                }
            });
        }

        /** Selective: load all employees with checkboxes enabled (no filters applied); user can use Filters to narrow down. */
        function loadSelectiveEmployees() {
            $.ajax({
                url: "{{ route('Survey.getAllEmployees') }}",
                type: "GET",
                success: function (response) {
                    var employeeList = $("#employeeList");
                    employeeList.empty();
                    if (response.success && response.data && response.data.length > 0) {
                        response.data.forEach(function (e) {
                            var html = '<div class="d-flex employee-item">' +
                                '<div class="img-circle userImg-block"><img src="' + (e.profileImg || '') + '" alt="user"></div>' +
                                '<div class="employee-details"><h6 class="employee-name">' + (e.EmployeeName || '') + '</h6><p class="position-name">' + (e.positionName || '') + '</p></div>' +
                                '<div class="form-check no-label"><input class="form-check-input" type="checkbox" name="Emp_id[]" value="' + (e.Emp_id || '') + '"></div>' +
                                '</div>';
                            employeeList.append(html);
                        });
                    } else {
                        employeeList.append("<p class='text-muted'>No employees found for this resort.</p>");
                    }
                },
                error: function () {
                    $("#employeeList").empty().append("<p class='text-danger'>Failed to load employees.</p>");
                    toastr.error("Failed to load employees.", "Error", { positionClass: "toast-bottom-right" });
                }
            });
        }

        
           
        window.Parsley.addValidator('endgreaterthanstart', {
        requirementType: 'string',
        validateString: function (endDateValue, startDateSelector) {
            var startDateStr = $(startDateSelector).val();
            var endDate = moment(endDateValue, 'DD/MM/YYYY', true);  
            var startDate = moment(startDateStr, 'DD/MM/YYYY', true);  

            if (!startDate.isValid() || !endDate.isValid()) {
                return true; // Skip validation if dates are invalid
            }

            return endDate.isAfter(startDate, 'day'); // Ensure End Date > Start Date
        },
        messages: {
            en: 'End Date must be greater than Start Date.'
        }
    });

    // Apply the custom validator to the End Date field
    $('#endDate').attr('data-parsley-endgreaterthanstart', '#startDate_step_3');
    
    });  

    $(document).on("click", ".AddMore", function() {
            var que_type = $(".que_type").val();
            var nos  =$("#increment").val();
          
            if(!isNaN(que_type))
            {
                Swal.fire({
                        title: 'Error!',
                        text: "Please Select Option Type",
                        icon: 'error'
                    })
            }
            else
            {
                let appendstring='';
                if(que_type=="text") 
                {
                    appendstring = `<div class="row gx-md-4 gx-3 g-2">
                        <div class="col-6 select_option select_text" data-id="${nos}">
                            <input type="text" class="form-control" placeholder="Question" 
                                name="AddQuestion[text][${nos}][]" required
                                data-parsley-required="true" 
                                data-parsley-required-message="Question is required"
                                data-parsley-group="block-0">
                        </div>
                        <div class="col-auto align-self-center">
                            <div class="d-flex align-items-center">
                                <label class="form-label mb-0 me-3">Compulsory Question</label>
                                <div class="form-check form-switch form-switchTheme">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        name="AddquestionReq[${nos}][]" id="flexSwitchCheckDefault" >
                                    <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }
                else if(que_type == "multiple") 
                {
                    appendstring = `<div class="col-12 select_option select_multiple">
                        <div class="row gx-md-4 gx-3 g-2">
                            <div class="col-md-4">
                                <input type="text" class="form-control" placeholder="Question"
                                    name="AddQuestion[multiple][${nos}][]" required
                                    data-parsley-required="true"
                                    data-parsley-required-message="Question is required"
                                    data-parsley-group="block-0">
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-10">
                                <input type="number" class="form-control total-options" data-id="${nos}"
                                    placeholder="Total option number" required
                                    data-parsley-required="true"
                                    data-parsley-type="integer"
                                    data-parsley-min="1"
                                    data-parsley-required-message="Total options must be at least 1"
                                    data-parsley-group="block-0"
                                    data-que_type="multiple"
                                    >
                                <ol class="listingNo-wrapper wrapper_${nos} mt-2 d-none"></ol>
                            </div>
                            <div class="col-auto align-self-center">
                                <div class="d-flex align-items-center">
                                    <label class="form-label mb-0 me-3">Compulsory Question</label>
                                    <div class="form-check form-switch form-switchTheme">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            name="AddquestionReq[${nos}][]" id="flexSwitchCheckDefault">
                                        <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }
                else if(que_type == "Radio") 
                {
                    appendstring = `<div class="col-12 select_option select_multiple">
                            <div class="row gx-md-4 gx-3 g-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" placeholder="Question"
                                        name="AddQuestion[radio][${nos}][]" required
                                        data-parsley-required="true"
                                        data-parsley-required-message="Question is required"
                                        data-parsley-group="block-0">
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-10">
                                    <input type="number" class="form-control total-options" data-id="${nos}"
                                        placeholder="Total Radio option number" required
                                        data-parsley-required="true"
                                        data-parsley-type="integer"
                                        data-parsley-min="1"
                                        data-parsley-required-message="Total options must be at least 1"
                                        data-parsley-group="block-0"
                                          data-que_type="Radio">
                                    <ol class="listingNo-wrapper wrapper_${nos} mt-2 d-none"></ol>
                                </div>
                                <div class="col-auto align-self-center">
                                    <div class="d-flex align-items-center">
                                        <label class="form-label mb-0 me-3">Compulsory Question</label>
                                        <div class="form-check form-switch form-switchTheme">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                name="AddquestionReq[${nos}][]" id="flexSwitchCheckDefault">
                                            <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                }
                else if(que_type =="Rating") 
                {
                    appendstring = `<div class="col-12 select_option select_multiple">
                        <div class="row gx-md-4 gx-3 g-2">
                            <div class="col-md-4">
                                <input type="text" class="form-control" placeholder="Question"
                                    name="AddQuestion[Rating][${nos}][]" required
                                    data-parsley-required="true"
                                    data-parsley-required-message="Question is required"
                                    data-parsley-group="block-0">
                            </div>
                            <div class="col-auto">
                                <div class="negPosi-block total-options_${nos}">
                                    <a href="#" class="btn btn-neg">Negative</a>
                                    <a href="#" class="btn">1</a>
                                    <a href="#" class="btn">2</a>
                                    <a href="#" class="btn">3</a>
                                    <a href="#" class="btn">4</a>
                                    <a href="#" class="btn">5</a>
                                    <a href="#" class="btn">6</a>
                                    <a href="#" class="btn">7</a>
                                    <a href="#" class="btn">8</a>
                                    <a href="#" class="btn">9</a>
                                    <a href="#" class="btn">10</a>
                                    <a href="#" class="btn btn-posi">Positive</a>
                                </div>
                            </div>
                            <div class="col-auto align-self-center">
                                <div class="d-flex align-items-center">
                                    <label class="form-label mb-0 me-3">Compulsory Question</label>
                                    <div class="form-check form-switch form-switchTheme">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            name="AddquestionReq[${nos}][]" id="flexSwitchCheckDefault">
                                        <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }


                
                    $(".AppendHerer").append(` <div class="talentAc-block mb-3 " id="remove_id_${nos}">
                        <div class="title mb-2">
                        <label for="question_type" class="form-label"> QUESTION ${nos}</label>
                            <button type="button" class="btn btn-danger btn-sm remove-btn"  data-id="${nos}"><i class="fa-solid fa-xmark"></i></i></button>
                        </div> ${appendstring}
                    </div>`);
                    nos  = 1  + parseInt(nos);
                  
                        $("#increment").val(nos);
            }
            $(this).parsley().validate();
        });
        $(document).on('input', '.total-options', function () {
                const location_id = $(this).attr('data-id');
                const que_type = $(this).attr('data-que_type'); // Fetch latest value using .attr()
                const olElement = $('.wrapper_' + location_id); // Ensure proper selection
                const totalOptions = parseInt($(this).val());

                console.log(que_type, location_id, !isNaN(totalOptions) && totalOptions > 0);

                if (!isNaN(totalOptions) && totalOptions > 0) {
                    olElement.removeClass('d-none').empty(); // Remove d-none and clear previous options

                    let inputName = que_type === "Radio" ? `RadioOption[${location_id}][]` : 
                                    que_type === "multiple" ? `CheckBoxOption[${location_id}][]` : '';

                    if (inputName) {
                        for (let i = 0; i < totalOptions; i++) {
                            const li = $('<li>');
                            const input = $('<input>', {
                                type: 'text',
                                class: 'form-control',
                                name: inputName,
                                placeholder: `${que_type} Option ${i + 1}`
                            });
                            li.append(input);
                            olElement.append(li);
                        }
                    }
                } else {
                    olElement.addClass('d-none').empty();
                }

                $(this).parsley().validate(); // Validate dynamically added elements
            });

    $(document).on("click",".remove-btn",function(suc){
        let id = $(this).data('id');

            $("#remove_id_"+id).remove();
            idnew = id - 1;
            if(idnew == 0)
            {
                idnew = 1;
            }

            $("#que_type_1").val('text').trigger('change');
            $("#increment").val(idnew);
    });

    $("#searchEmployee").on("keyup", function () {
        let searchValue = $(this).val().toLowerCase().trim();
        filterdata();
    });


    function loadPositionOptions(deptIds, callback) {
        var $pos = $("#position");
        $pos.empty();
        if (!deptIds || !deptIds.length) {
            $.ajax({
                url: "{{ route('resort.get.position') }}",
                type: "post",
                data: { "_token": "{{ csrf_token() }}" },
                success: function(data) {
                    if (data.success && data.data && data.data.length) {
                        data.data.forEach(function(p) { $pos.append('<option value="'+p.id+'">'+p.position_title+'</option>'); });
                    }
                    $pos.trigger('change');
                    if (typeof callback === 'function') callback();
                },
                error: function() { $pos.trigger('change'); if (typeof callback === 'function') callback(); }
            });
            return;
        }
        var allPositions = {};
        var done = 0;
        deptIds.forEach(function(deptId) {
            $.ajax({
                url: "{{ route('resort.get.position') }}",
                type: "post",
                data: { deptId: deptId, "_token": "{{ csrf_token() }}" },
                success: function(data) {
                    if (data.success && data.data && data.data.length) {
                        data.data.forEach(function(p) { allPositions[p.id] = p; });
                    }
                    done++;
                    if (done === deptIds.length) {
                        $.each(allPositions, function(id, p) {
                            $pos.append('<option value="'+p.id+'">'+p.position_title+'</option>');
                        });
                        $pos.trigger('change');
                        if (typeof callback === 'function') callback();
                    }
                },
                error: function() { done++; if (done === deptIds.length) { $pos.trigger('change'); if (typeof callback === 'function') callback(); } }
            });
        });
    }

    $(document).on('change', '#department', function() {
        var deptIds = $(this).val();
        loadPositionOptions(deptIds, function() { filterdata(); });
    });
    $(document).on('change', '#position, #employment_grade, #gender', function() {
        filterdata();
    });



    $(document).on("click",".FilterSubmit",function(){
        filterdata();
    });
    $(document).on("click",".ClearFilter",function(){
        $("#department").val(null).trigger('change');
        $("#employment_grade").val(null).trigger('change');
        $("#gender").val('').trigger('change');
        $("#searchEmployee").val('');
        loadPositionOptions(null, function() {
            $("#position").val(null);
            loadSelectiveEmployees();
        });
    });

 
    function filterdata() {
            var searchValue = $("#searchEmployee").val();
            var department = $("#department").val();
            var position = $("#position").val();
            var employment_grade = $("#employment_grade").val();
            var gender = $("#gender").val();
            var payload = {
                _token: "{{ csrf_token() }}",
                searchValue: searchValue || '',
                department: Array.isArray(department) ? department : (department ? [department] : []),
                position: Array.isArray(position) ? position : (position ? [position] : []),
                employment_grade: Array.isArray(employment_grade) ? employment_grade : (employment_grade ? [employment_grade] : []),
                gender: gender ? gender : ''
            };
            $.ajax({
                url: "{{ route('Performance.Meeting.GetPerformanceEmp') }}",
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload),
                success: function (response) {
                    var employeeList = $("#employeeList");
                    employeeList.empty();
                    if (response.success && response.data) {
                        var data = response.data;
                        if (!data.length || data === 0) {
                            employeeList.append("<p class='text-muted'>No results found. Try different filters.</p>");
                        } else {
                            data.forEach(function (e) {
                                var employeeHtml = '<div class="employee-item"><div class="d-flex">' +
                                    '<div class="img-circle userImg-block"><img src="' + (e.profileImg || '') + '" alt="user"></div>' +
                                    '<div><h6 class="employee-name">' + (e.EmployeeName || '') + '</h6><p class="position-name">' + (e.positionName || '') + '</p></div>' +
                                    '<div class="form-check no-label"><input class="form-check-input" type="checkbox" name="Emp_id[]" value="' + (e.Emp_id || '') + '" checked></div>' +
                                    '</div></div>';
                                employeeList.append(employeeHtml);
                            });
                        }
                    } else {
                        employeeList.append("<p class='text-muted'>No results found. Try different filters.</p>");
                        if (response.message) {
                            toastr.warning(response.message, "Info", { positionClass: "toast-bottom-right" });
                        }
                    }
                },
                error: function (response) {
                    $("#employeeList").empty().append("<p class='text-muted'>Filter failed. Showing all employees.</p>");
                    loadSelectiveEmployees();
                    var errs = (response.responseJSON && response.responseJSON.errors) ? response.responseJSON.errors : null;
                    if (errs) {
                        var msg = typeof errs === 'string' ? errs : (errs.message || Object.values(errs).flat().join(' '));
                        toastr.error(msg, "Error", { positionClass: "toast-bottom-right" });
                    }
                }
            });
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
                $(".select2t-none").select2();
                $(".select2t-none").on('change', function() { $(this).parsley().validate(); });
                $(".select2t-none").on('select2:select', function() { $(this).trigger('change'); });

                // Multi-select filters (Participant Selection step)
                $(".select2-multi").each(function() {
                    var $el = $(this);
                    $el.select2({
                        allowClear: true,
                        closeOnSelect: false,
                        placeholder: $el.data('placeholder') || 'Select...',
                        width: '100%'
                    });
                });
            }
        }

         // Initialize Parsley Validation
        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#msform').parsley({
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

        // Initialize All Validations and Plugins
        function initializeFormValidation() {
            initSelect2AndValidation();
            initParsleyValidation();
        }

        // Call initialization when document is ready
        $(document).ready(initializeFormValidation);
    });
    

</script>
@endsection
