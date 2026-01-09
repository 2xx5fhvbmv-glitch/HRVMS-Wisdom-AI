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
                                    {{-- <div class="col-sm-6">
                                        <label for="search" class="form-label">SEARCH</label>
                                        <div class="input-group searchInput-group">
                                            <input type="search" class="form-control " placeholder="Search" />
                                            <i class="fa-solid fa-search"></i>
                                        </div>
                                    </div> --}}
                                    <div class="col-auto">
                                        <div class="position-relative">
                                            <a href="javascript:void(0)"
                                                class="btn btn-themeGrayLight filters-btn">Filters<i
                                                    class="fa-solid fa-angle-down"></i></a>

                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <a href="javascript:void(0)"    class="a-link d-inline-block mb-md-3 mb-3 ClearFilter">Clear Filter</a>
                                    </div>
                                    <div class="col-12">
                                        <div class="filters-block d-none">
                                            <div class="row g-md-4 g-3 align-items-end">
                                          
                                                <div class="col-sm-6">
                                                    <label for="department" class="form-label">DEPARTMENT</label>
                                                    <select class="form-select select2t-none" id="department" name="department" aria-label="Default select example">
                                                        <option selected>Department </option>
                                                        @if( $ResortDepartment->isNotEmpty())
                                                         @foreach ($ResortDepartment  as $d)
                                                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                         @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                               
                                                <div class="col-sm-6">
                                                    <label for="positionFilter" class="form-label">POSITION</label>
                                                    <select class="form-select select2t-none" name="position" id="position"
                                                        aria-label="Default select example">
                                                       
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="employment_type" class="form-label">EMPLOYMENT
                                                        TYPE  (Grade)</label>
                                     
                                                        @php
                                                            $grade = config('settings.eligibilty');
                                                        @endphp
                                                    <select class="form-select select2t-none" name="employment_grade" id="employment_grade" aria-label="Default select example">
                                                        <option disabled selected>Type </option>
                                                        @if( !empty($grade))
                                                            @foreach ($grade  as $k=>$d)
                                                                <option value="{{ $k }}">{{$d}}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                               
                                                <div class="col-auto ">
                                                    <a href=" javascript:void(0)" class="FilterSubmit btn btn-themeBlue ">Submit</a>
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
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                    <option value="Annually">Annually</option>
                                </select>
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="reminderNotification" class="form-label">REMINDER NOTIFICATIONS</label>
                                <select class="form-select select2t-none" name="reminderNotification" id="reminderNotification"  
                                    data-parsley-required="true"
                                    data-parsley-required-message="Please select a reminder notification day"
                                    data-parsley-group="block-2"
                                    data-parsley-trigger="change">
                                    @for($i=1; $i<=10; $i++)
                                    <option value="{{ $i }}">{{$i}} Days Before Expiry</option>
                                    @endfor
                                </select>
                            </div>
                            
                            <div class="col-lg-6">
                                <label for="minimumNumber" class="form-label">MINIMUM NUMBER OF RESPONSES</label>
                                <input type="number" min="1" class="form-control" id="minimumNumber" name="minimum_responses"
                                    placeholder="Set minimum number of responses" data-parsley-required="true"
                                    data-parsley-required-message="Please enter minimum number of responses"
                                    data-parsley-type="number"
                                    data-parsley-min="1"
                                    data-parsley-min-message="Minimum responses must be at least 1"
                                    data-parsley-group="block-2"
                                    data-parsley-trigger="change">
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
                        <a href="javascript:void(0)" class="a-linkTheme me-1 ">Preview</a>
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
        $("#department").select2({
          'placeholder':'Select Department',
        });
        $("#position").select2({
          'placeholder':'Select position',
        });
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
        if ( currentGroup == "block-1" && selectedEmployees.length === 0) 
        {
            toastr.error("Please Apply the Filter before You proceed to the next step and select at least one employee before proceeding.", "Error",
            {
                 positionClass: 'toast-bottom-right'
            });
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
            $(this).toggleClass("active"); // Add class on hover
            $(".filters-block").toggleClass("d-none");
        });

        
           
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


    $(document).on('change', '#department', function() {
        var deptId = $(this).val();

            $.ajax({
                url: "{{ route('resort.get.position') }}",
                type: "post",
                data: {
                    deptId: deptId
                },
                success: function(data) {
                    // Clear the dropdown and add a placeholder option
                    $("#position").empty().append('<option value="">Select Position</option>');

                    if(data.success == true) {
                        // Append new options
                        $.each(data.data, function(key, value) {
                            $("#position").append('<option value="'+value.id+'">'+value.position_title+'</option>');
                        });
                    } else {
                        // If no data, just keep the placeholder
                        $("#position").empty().append('<option value="">Select Position</option>');
                    }
                },
                error: function(response) {
                    toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });



    $(document).on("click",".FilterSubmit",function(){
        var department = $("#department").val();
        var position = $("#position").val();
        var employment_grade = $("#employment_grade").val();

        if (department === "Department" && position ==null && employment_grade==null) 
        {
            toastr.error("Please select at least one option (Department, Position, or Employment Type) before proceeding.", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }
        filterdata();
    });   
    $(document).on("click",".ClearFilter",function(){

        var department = $("#department").val('').trigger('change');
        var position = $("#position").val('').trigger('change');
        var employment_grade = $("#employment_grade").val('').trigger('change');
        filterdata();
    });

 
    function filterdata()
    {
            var searchValue =  $("#searchEmployee").val();
            var department = $("#department").val();
            var position = $("#position").val();
            var employment_grade = $("#employment_grade").val();
            $.ajax({
                url: "{{ route('Performance.Meeting.GetPerformanceEmp') }}",
                type: "POST",
                data: {
                    "_token":"{{ csrf_token() }}",
                    "searchValue":searchValue,
                    "department":department,
                    "position":position,
                    "employment_grade":employment_grade

                },
                success: function (response) {
                    if (response.success) {
                        let employeeList = $("#employeeList"); // Replace with the actual ID of your container
                        employeeList.empty();
                        console.log(response.data === 0,response.data);
                        if (response.data === 0)
                        {
                            employeeList.append("<p>No results found.</p>");
                        }
                        else
                        {
                            response.data.forEach((e) => {
                                let employeeHtml = `<div class="employee-item">
                                                        <div class="d-flex">
                                                            <div class="img-circle userImg-block">
                                                                <img src="${e.profileImg}" alt="user">
                                                            </div>
                                                            <div>
                                                                <h6 class="employee-name">${e.EmployeeName}</h6>
                                                                <p class="position-name">${e.positionName}</p>
                                                            </div>
                                                            <div class="form-check no-label">
                                                                <input class="form-check-input" type="checkbox" name="Emp_id[]" value="${e.Emp_id}">
                                                            </div>
                                                        </div>
                                                    </div>`;
                                employeeList.append(employeeHtml);
                            });
                        }
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: "toast-bottom-right",
                        });
                    }
                },
                error: function (response)
                {
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
