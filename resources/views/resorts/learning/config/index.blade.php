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
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Learning Program Categories</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <form name="learning-category" id="learning-category">
                            @csrf
                            <div class="row align-items-center g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <input type="text" name="category" id="category" class="form-control" placeholder="Learning Program Category"/>  
                                </div>
                                <div class="col-sm-6">
                                    <div class="inputCustom-color"> Color Theme
                                        <input type="color" name="color" id="color" value="#A264F7" style="top:50%"  data-parsley-required="true"
                                        data-parsley-errors-container="#color-error" >
                                    </div>
                                    <div id="color-error"></div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                        <div class="row g-1 category-list">
                            <div class="col-12">
                                <table class="table table-sm categoryTable"  id="categoryTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Category</th>
                                            <th scope="col">Color</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Learning Program Setup</h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('learning.programs.index')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>
                        <form id="learning-program-setup">
                            @csrf
                            <div class="row g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <input type="text" class="form-control" placeholder="Learning Program Name" name="name" id="name">
                                </div>
                                <div class="col-12">
                                    <textarea class="form-control" name="description" id="description" placeholder="Description" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="objectives" id="objectives" class="form-control" placeholder="Objectives And Goals">
                                </div>
                                <div class="col-12">
                                    <select class="form-select select2t-none" name="category" id="category" aria-label="Default select example">
                                        <option selected>Select Category</option>
                                        @if($categories)
                                            @foreach($categories as $cat)
                                                <option value="{{$cat->id}}">{{$cat->category}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="select-selection" class="form-label">Target Audience TYPE</label>
                                    <ul class="nav mt-2 ">
                                        <li class="form-radio">
                                            <input class="form-radio-input" type="radio" value="departments" id="radio-permanant" name="audience_type" checked>
                                            <label class="form-radio-label" for="radio-permanant">
                                                Specific Departments
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="grades" id="radio-casual-Agency" name="audience_type">
                                            <label class="form-radio-label" for="radio-casual-Agency">
                                                Grades
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="positions" id="radio-trainee-intern" name="audience_type">
                                            <label class="form-radio-label" for="radio-trainee-intern">
                                                Positions
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="employees" id="radio-replacement" name="audience_type">
                                            <label class="form-radio-label" for="radio-replacement">
                                               Employees
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="col-12 target-dropdown" id="department-dropdown">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="target_audiance[]" id="target_department" multiple>
                                        <option value="">Select Target Department</option>
                                        @if($departments)
                                            @foreach($departments as $dept)
                                                <option value="{{$dept->id}}">{{$dept->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-12 target-dropdown" id="position-dropdown" style="display: none;">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="target_audiance[]" id="target_positions" multiple>
                                        <option value="">Select Target Position</option>
                                        @if($positions)
                                            @foreach($positions as $pos)
                                                <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-12 target-dropdown" id="employee-dropdown" style="display: none;">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="target_audiance[]" id="target_employees" multiple>
                                        <option  value="">Select Target Employees</option>
                                        @if($employees)
                                            @foreach($employees as $emp)
                                                <option value="{{$emp->id}}">{{$emp->resortAdmin->full_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-12 target-dropdown" id="grade-dropdown" style="display: none;">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="target_audiance[]" id="target_grades" multiple>
                                        <option value="">Select Target Grades</option>
                                        @if($grades)
                                            @foreach ($grades as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <input type="number" name="hours" id="hours" class="form-control" placeholder="Hours"/>
                                </div>
                                <div class="col-sm-6">
                                    <input type="number" name="days" id="days" class="form-control" placeholder="Days"/>
                                </div>
                                <div class="col-sm-6">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="frequency" id="frequency">
                                        <option selected value="">Frequency</option>
                                        <option value="one-time">One-time</option>
                                        <option value="recurring">Recurring</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="annually">Annually</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="delivery_mode" id="delivery_mode">
                                        <option selected>Delivery Mode</option>
                                        <option value="face-to-face">Face-to-Face</option>
                                        <option value="online">Online</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                </div>
                                <div class="col-xxl-6">
                                    <select class="form-select select2t-none" aria-label="Default select example" name="trainer" id="trainer">
                                        <option selected>Select Trainer</option>
                                        @if($trainers)
                                            @foreach($trainers as $v)
                                                <option value="{{$v->id}}">{{$v->resortAdmin->full_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-xxl-6">
                                    <div class="uploadFile-btn me-0">
                                        <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm"
                                            onclick="document.getElementById('learning_material').click();">
                                            Upload Relevant Learning Materials
                                        </a>
                                        <input type="file" id="learning_material" name="learning_material[]"
                                            accept=".pdf,.ppt,.pptx," style="opacity: 0; position: absolute; z-index: -1;" onchange="displayFileName()" multiple >
                                        <div id="fileNameLearningMaterial" style="margin-top: 10px; color: #333;"></div>
                                    </div>
                                   
                                </div>
                                <div class="col-12">
                                    <input type="text" placeholder="Specify if any prior training or qualifications are required before attending" name="prior_qualification" id="prior_qualification" class="form-control"/>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>                  
                </div>
                <div class="col-lg-6 ">
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Mandatory Learning Programs</h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('mandatory.learning.get')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>
                        
                        <form id="mandatory-programs-form">
                            <div class="mandatoryLearning-main">
                                <div class="mandatoryLearning-block">
                                    <div class="row g-2 mb-md-4 mb-3 program-group">
                                        <div class="col-12">
                                            <select class="form-select select2t-none mandatory_programs" name="programs[0][mandatory_program]" aria-label="Default select example">
                                                <option value="">Mandatory Learning program</option>
                                                @if($programs)
                                                    @foreach($programs as $program)
                                                        <option value="{{$program->id}}">{{$program->name}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <select class="form-select select2t-none mandatory_department" name="programs[0][mandatory_department]" aria-label="Default select example">
                                                <option value="">Select Department</option>
                                                @if($departments)
                                                    @foreach($departments as $dept)
                                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <select class="form-select select2t-none mandatory_position" name="programs[0][mandatory_position]" aria-label="Default select example">
                                                <option value="">Select Position</option>
                                                @if($positions)
                                                    @foreach($positions as $pos)
                                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <select class="form-select select2t-none notify_before_days" aria-label="Default select example" name="programs[0][notify_before_days]">
                                                <option value="">Notify Before how many Days</option>
                                                @for($i=1;$i<=7;$i++)
                                                    <option value="{{$i}}">{{$i}}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="button" class="btn btn-danger btn-sm remove-btn" style="display:none;">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button type="button" class="btn btn-themeSkyblue btn-sm blockAdd-btn" id="add-more">Add More</button>
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Probationary Learning Programs</h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('probationary.learning.get')}}" class="a-link">View Existing</a>
                                </div>
                            </div>
                        </div>

                        <form id="probationary-programs-form">
                            <div class="row g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <select class="form-select select2t-none" name="probationary_programs" id="probationary_programs" aria-label="Default select example">
                                        <option value="">Learning required during an employee’s probation period</option>
                                        @if($programs)
                                            @foreach($programs as $program)
                                                <option value="{{$program->id}}">{{$program->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div id="div-probationary_programs"></div>
                                </div>
                                <div class="col-12">
                                    <input type="number" id="completion_days" name="completion_days" class="form-control" placeholder="Completion Days">
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Feedback Forms</h3>
                        </div>
                        <div class="row g-2 mb-md-4 mb-3">
                            <div class="col-12">
                                <a href="{{route('feedback-form.create')}}" class="btn btn-themeSkyblue btn-sm">Create Feedback Form
                                    Template</a>
                            </div>
                        </div>
                    </div>

                    <div class="card card-evaluationSet mb-30">
                        <div class="card-title">
                            <h3>Evaluation Settings</h3>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <a href="{{route('evaluation-form.create')}}" class="btn btn-themeSkyblue btn-sm">Create Evaluation Form</a>
                        </div>
                        <form id="evaluation_settings">
                            @csrf
                            
                            <div class="mb-md-4 mb-3">
                                <label>Set Rules for Automatic Reminders:</label>
                                <select class="form-control" name="evaluation_reminder" id="evaluation_reminder">
                                    <option value="after_24_hours" {{$attenndanceParameters->evaluation_reminder == "after_24_hours" ? "Selected" : ""}}>Reminder after 24 hours</option>
                                    <option value="after_3_days" {{$attenndanceParameters->evaluation_reminder == "after_3_days" ? "Selected" : ""}}>Reminder after 3 days</option>
                                    <option value="after_7_days" {{$attenndanceParameters->evaluation_reminder == "after_7_days" ? "Selected" : ""}}>Reminder after 7 days</option>
                                </select>
                            </div>

                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm" id="submitEvaluationSettings">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card card-attendancePara mb-30">
                        <div class="card-title">
                            <h3>Attendance Parameters</h3>
                        </div>
                        <form id="attendance_parameters">
                            <div class="row align-items-center g-2 mb-md-4 mb-3">                            
                                <div class="col-xxl-6">
                                    <input type="number" class="form-control" name="threshold_percentage" id="threshold_percentage" placeholder="Minimum Attendance Percentage" min="0" max="100" value="{{ $attenndanceParameters->threshold_percentage ?? '' }}">
                                </div>
                                <div class="col-xxl-6">
                                    <div class="row align-items-center  g-1">
                                        <div class="col-auto">
                                            <h6>Auto-Notifications To Employees</h6>
                                        </div>
                                        <div class="col-auto ms-auto">
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox"
                                                    name="auto_notifications"
                                                    id="auto_notifications"
                                                    {{ !empty($attenndanceParameters->auto_notifications) && $attenndanceParameters->auto_notifications ? 'checked' : '' }}>
                                                <label class="form-check-label" for="auto_notifications"></label>
                                            </div>
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
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('.select2t-none').select2();
        $('.datepicker').datepicker();
        $('#learning-category').validate({
            rules: {
                category: {
                    required: true,
                }
            },
            messages: {
                category: {
                    required: "Please enter the category.",
                }
            },
            submitHandler: function(form) {
                
                var formData = {
                    _token: $('input[name="_token"]').val(),
                    category: $('#category').val(),
                    color : $('#color').val(),
                };
    
                $.ajax({
                    url: "{{ route('learning.categories.save') }}", // Adjust to your route
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            console.log(response);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#learning-category').get(0).reset();
                            $('#categoryTable').DataTable().ajax.reload();
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

        $('#categoryTable tbody').empty();
        var categoryTable = $('#categoryTable').DataTable({
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
                url: '{{ route("learning.categories.list") }}',
                type: 'GET',
            },
            columns: [
                { data: 'category', name: 'Category', className: 'text-nowrap' },
                { data: 'color', name: 'Color', className: 'text-nowrap' },
                { data: 'action', name: 'Action', orderable: false, searchable: false }
            ]
        });

        $(document).on("click", "#categoryTable .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            // Extract division ID
            var categoryId = $(this).data('category-id');

            var currentCategory = $row.find("td:nth-child(1)").text().trim();
            var currentColor = $row.find("td:nth-child(2)").text().trim();

            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control name" value="${currentCategory}" />
                        </div>
                    </td>

                     <td class="py-1">
                        <div class="inputCustom-color"> Color Theme
                            <input type="color" name="color" id="color" value="${currentColor}" style="top:50%">
                        </div>
                    </td>
                
                    <td class="py-1">
                        <a href="#" class="btn btn-theme update-category-btn" data-category-id="${categoryId}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
        });

        $(document).on("click", "#categoryTable .update-category-btn", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var categoryId = $(this).data('category-id');
            var updatedCategory = $row.find("input").eq(0).val();
            var updatedColor = $row.find("input").eq(1).val();
    
            $.ajax({
                url: "{{ route('learning.category.inlineUpdatecategory', '') }}/" + categoryId,
                type: "PUT",
                data: {
                    category : updatedCategory,
                    color : updatedColor
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key
                        // Update the row with new values
                        var updatedRowHtml = `
                            <td class="text-nowrap">${updatedCategory}</td>
                            <td class="text-nowrap">${updatedColor}</td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-category-id="${categoryId}">
                                        <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                    <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-category-id="${categoryId}">
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

        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('category-id');

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
                        url: "{{ route('learning.category.destroy','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            $row.remove();

                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            $('#categoryTable').DataTable().ajax.reload();
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

        $("input[name='audience_type']").change(function () {
            // Hide all dropdowns first
            $(".target-dropdown").hide();

            // Get the selected value
            let selectedType = $("input[name='audience_type']:checked").val();

            // Show only the related dropdown
            if (selectedType === "departments") {
                $("#department-dropdown").show();
            } else if (selectedType === "grades") {
                $("#grade-dropdown").show();
            } else if (selectedType === "positions") {
                $("#position-dropdown").show();
            } else if (selectedType === "employees") {
                $("#employee-dropdown").show();
            }
        });

        // Trigger change event on page load to ensure the correct dropdown is shown
        $("input[name='audience_type']:checked").trigger("change");

        $('#learning-program-setup').validate({
            rules: {
                name: {
                    required: true,
                },
                description: {
                    required: true,
                },
                objectives: {
                    required: true,
                },
                category: {
                    required: true,
                },
                "target_audiance[]": {  // Corrected syntax
                    required: true,
                },
                hours: {
                    required: true,
                    number: true,
                    min: 1
                },
                days: {
                    required: true,
                    number: true,
                    min: 1
                },
                frequency: {
                    required: true,
                },
                delivery_mode: {
                    required: true,
                },
                trainer: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Please enter the learning program name.",
                },
                description: {
                    required: "Please enter a description.",
                },
                objectives: {
                    required: "Please enter objectives.",
                },
                category: {
                    required: "Please select a category.",
                },
                "target_audiance[]": { // Fixed syntax
                    required: "Please select a target audience.",
                },
                hours: {
                    required: "Please enter hours.",
                    number: "Only numbers are allowed.",
                    min: "Hours must be at least 1."
                },
                days: {
                    required: "Please enter days.",
                    number: "Only numbers are allowed.",
                    min: "Days must be at least 1."
                },
                frequency: {
                    required: "Please select a frequency.",
                },
                delivery_mode: {
                    required: "Please select a delivery mode.",
                },
                trainer: {
                    required: "Please select a trainer.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('learning.programs.save') }}", // Adjust to your route
                    type: "POST",
                    data: formData,
                    processData: false,  // Important for FormData
                    contentType: false,  // Important for FormData
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#learning-program-setup').get(0).reset();
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

        $('#title').change(function () {
            let programId = $(this).val();
            if (programId) {
                $.ajax({
                    url: '{{ route("learning.program.details") }}', // API Route to fetch program details
                    type: 'GET',
                    data: { program_id: programId },
                    success: function (response) {
                        if (response.success) {
                            $('#categoryID').val(response.data.category_id).trigger('change');
                            $('#trainerID').val(response.data.trainer_id).trigger('change');
                            $('#session_frequency').val(response.data.frequency).trigger('change');
                            $('#frequencyHidden').val(response.data.frequency).trigger('change');
                        }
                    },
                    error: function () {
                        alert('Failed to fetch program details');
                    }
                });
            }
        });

        // $('#calendarForm').validate({
        //     rules: {
        //         title: {
        //             required: true,
        //         },
        //         session_date:{
        //             required: true,
        //         },
        //         session_time:{
        //             required: true,
        //         }
        //     },
        //     messages: {
        //         title: {
        //             required: "Please select the learning program title.",
        //         },
        //         session_date: {
        //             required: "Please select session date",
        //         },
        //         session_time: {
        //             required: "Please select session time.",
        //         },
        //     },
        //     submitHandler: function(form) {
        //         var formData = new FormData(form);

        //         $.ajax({
        //             url: "{{ route('learning.calendar.save') }}", // Adjust to your route
        //             type: "POST",
        //             data: formData,
        //             processData: false,  // Important for FormData
        //             contentType: false,  // Important for FormData
        //             success: function(response) {
        //                 if (response.success) {
        //                     toastr.success(response.message, "Success", {
        //                         positionClass: 'toast-bottom-right'
        //                     });
        //                     $('#calendarForm').get(0).reset();
        //                     window.setTimeout(function() {
        //                         window.location.href = response.redirect_url;
        //                     }, 2000);
        //                 } else {
        //                     toastr.error(response.msg, "Error", {
        //                         positionClass: 'toast-bottom-right'
        //                     });
        //                 }
        //             },
        //             error: function(response) {
        //                 var errors = response.responseJSON.errors;
        //                 var errorMessages = '';
        //                 $.each(errors, function(key, error) {
        //                     errorMessages += error + '<br>';
        //                 });
        //                 toastr.error(errorMessages, "Error", {
        //                     positionClass: 'toast-bottom-right'
        //                 });
        //             }
        //         });
        //     },
        //     errorPlacement: function(error, element) {
		// 		if( element.attr("name") == "title" ) {
		// 			error.insertAfter( "#div-title" );
		// 		} else{
		// 			error.insertAfter(element);
		// 		}

		// 	},
		// 	errorElement: 'span'
        // });

        $('#probationary-programs-form').validate({
            rules: {
                probationary_programs: {
                    required: true,
                },
                completion_days: {
                    required: true,
                },
            },
            messages: {
                probationary_programs: {
                    required: "Please select the probationary program.",
                },
                completion_days: {
                    required: "Please enter the completion days.",
                },
            },
            submitHandler: function(form) {
                
                var formData = {
                    _token: $('input[name="_token"]').val(),
                    probationary_programs: $('#probationary_programs').val(),
                    completion_days: $('#completion_days').val()
                };
    
                $.ajax({
                    url: "{{ route('probationary.learning.save') }}", // Adjust to your route
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            console.log(response);
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#probationary-programs-form').get(0).reset();
                            window.setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 2000);
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
            },
            errorPlacement: function(error, element) {
				if( element.attr("name") == "probationary_programs" ) {
					error.insertAfter( "#div-probationary_programs" );
				} else{
					error.insertAfter(element);
				}

			},
			errorElement: 'span'
        });

        $('#attendance_parameters').validate({
            rules: {
                threshold_percentage: {
                    required: true,
                    min: 0,
                    max: 100,
                    number: true
                }
            },
            messages: {
                threshold_percentage: {
                    required: "Please enter the threshold percentage.",
                    min: "Percentage must be at least 0.",
                    max: "Percentage cannot be more than 100.",
                    number: "Please enter a valid number."
                }
            },
            submitHandler: function (form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('learning.attendance-parameters.save') }}", // Adjust to your route
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#attendance_parameters').get(0).reset();
                            $('#categoryTable').DataTable().ajax.reload();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON.errors;
                        var errorMessages = '';
                        $.each(errors, function (key, error) {
                            errorMessages += error + '<br>';
                        });
                        toastr.error(errorMessages, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });

        $('#evaluation_settings').validate({
            rules: {
                evaluation_reminder: {
                    required: true,
                }
            },
            messages: {
                evaluation_reminder: {
                    required: "Please Select Reminder.",
                }
            },
            submitHandler: function (form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('learning.evaluation-reminder.save') }}", // Adjust to your route
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#evaluation_settings').get(0).reset();
                            $('#categoryTable').DataTable().ajax.reload();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON.errors;
                        var errorMessages = '';
                        $.each(errors, function (key, error) {
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

    document.addEventListener('DOMContentLoaded', function () {
        let index = 0; // Track dynamic field index
        const programsMain = document.querySelector('.mandatoryLearning-main');

        document.querySelector('#add-more').addEventListener('click', function () {
            index++; // Increment index for new rows

            const newRow = `
                <div class="row g-2 mb-md-4 mb-3 program-group">
                    <div class="col-12">
                        <select class="form-select select2t-none mandatory_programs" name="programs[${index}][mandatory_program]" aria-label="Default select example">
                            <option value="">Mandatory Learning program</option>
                            @foreach($programs as $program)
                                <option value="{{$program->id}}">{{$program->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select select2t-none mandatory_department" name="programs[${index}][mandatory_department]" aria-label="Default select example">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{$dept->id}}">{{$dept->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select select2t-none mandatory_position" name="programs[${index}][mandatory_position]" aria-label="Default select example">
                            <option value="">Select Position</option>
                            @foreach($positions as $pos)
                                <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select select2t-none notify_before_days" name="programs[${index}][notify_before_days]" aria-label="Default select example">
                            <option value="">Notify Before how many Days</option>
                            @for($i=1;$i<=7;$i++)
                                <option value="{{$i}}">{{$i}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
                    </div>
                </div>`;

            programsMain.insertAdjacentHTML('beforeend', newRow);
            $('.select2t-none').select2(); // Reinitialize select2 for new elements
        });


        // Handle department change event to fetch positions dynamically
        $(document).on('change', '.mandatory_department', function () {
            let departmentId = $(this).val();
            let positionDropdown = $(this).closest('.program-group').find('.mandatory_position');

            if (departmentId) {
                $.ajax({
                    url: "{{ route('get.mandatory.positions') }}", // Route for fetching positions
                    type: "GET",
                    data: { department_id: departmentId },
                    success: function (response) {
                        positionDropdown.empty().append('<option value="">Select Position</option>');
                        if (response.success) {
                            $.each(response.positions, function (key, value) {
                                positionDropdown.append(`<option value="${value.id}">${value.position_title}</option>`);
                            });
                        } else {
                            toastr.error("No positions found for this department.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error("Failed to fetch positions.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            } else {
                positionDropdown.empty().append('<option value="">Select Position</option>');
            }
        });

        // Remove dynamic row
        programsMain.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-btn')) {
                e.target.closest('.program-group').remove();
            }
        });


        // Submit Form with AJAX
        document.querySelector('#mandatory-programs-form').addEventListener('submit', function (e) {
            e.preventDefault();

            // Gather data dynamically
            const rows = document.querySelectorAll('.program-group');
            let programsData = [];

            rows.forEach(row => {
                let mandatory_program = row.querySelector('.mandatory_programs')?.value;
                let mandatory_department = row.querySelector('.mandatory_department')?.value;
                let mandatory_position = row.querySelector('.mandatory_position')?.value;
                let notify_before_days = row.querySelector('.notify_before_days')?.value;

                if (mandatory_program && mandatory_department && mandatory_position && notify_before_days) {
                    programsData.push({
                        mandatory_program,
                        mandatory_department,
                        mandatory_position,
                        notify_before_days
                    });
                }
            });

            // Validate before submission
            if (programsData.length === 0) {
                toastr.error("Please fill in at least one entry.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // AJAX Submission
            $.ajax({
                url: "{{ route('mandatory.learning.save') }}",
                type: "POST",
                data: {
                    programs: programsData,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        document.querySelector('#mandatory-programs-form').reset();
                    } else {
                        toastr.error("Failed to save mandatory programs.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    if (response.responseJSON) {
                        let errors = response.responseJSON.errors;
                        let errs = '';
                        $.each(errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });
    });

    function displayFileName() {
        let input = document.getElementById('learning_material');
        let fileList = input.files;
        let fileNames = [];

        for (let i = 0; i < fileList.length; i++) {
            fileNames.push(fileList[i].name);
        }

        document.getElementById('fileNameLearningMaterial').innerHTML = fileNames.join('<br>');
    }
</script>
@endsection