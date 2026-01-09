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

            <div>
                <form id="learning-request-form">
                    <div class="card">
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-4 col-lg-5 ">
                                <div class="bg-themeGrayLight sumDisEmp-block">
                                    <div class="card-title mb-md-3">
                                        <h3>Select Employee</h3>
                                    </div>
                                    <input type="search" class="form-control" id="searchInput" placeholder="Search" />

                                    <div class="overflow-auto pe-1" id="employeeList">
                                        @if($employees)
                                            @foreach($employees as $employee)
                                            <div class="d-flex employee-item">
                                                <div class="img-circle userImg-block ">
                                                    <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id)}}" alt="user">
                                                </div>
                                                <div class="employee-info">
                                                    <h6 class="employee-name">{{$employee->resortAdmin->full_name}}</h6>
                                                    <p class="employee-position">{{$employee->position->position_title}}</p>
                                                </div>
                                                <div class="form-check no-label">
                                                    <input class="form-check-input" type="checkbox" value="{{$employee->id}}">
                                                </div>
                                            </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-8 col-lg-7 ">
                                <div class="row gx-md-4 g-3 ">
                                    <div class="col-lg-12">
                                        <label for="suggested_Learning" class="form-label">SUGGESTED LEARNING <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="suggested_Learning" name="suggested_Learning">
                                            <option value="">Select Learning</option>
                                            @if($programs)
                                                @foreach($programs as $program)
                                                    <option value="{{$program->id}}">{{$program->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="div-suggested_Learning"></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="reason" class="form-label">Reason <span class="req_span">*</span></label>
                                        <input type="text" id="reason" name="reason" class="form-control" placeholder="Reason">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="training_manager" class="form-label">L&D Manager <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="learning_manager" name="learning_manager">
                                            <option value="">Select Learning Manager</option>
                                            @if($learningManagers)
                                                @foreach($learningManagers as $manager)
                                                    <option value="{{$manager->id}}">{{$manager->resortAdmin->full_name}} ({{$manager->position->position_title}})</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="start_date" class="form-label">START DATE <span class="req_span">*</span></label>
                                        <input type="text" id="start_date" name="start_date" class="form-control datepicker"
                                            placeholder="Select Start Date">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="end_date" class="form-label">END DATE <span class="req_span">*</span></label>
                                        <input type="text" id="end_date" name="end_date" class="form-control datepicker"
                                            placeholder="Select End Date">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection

@section('import-css')
<style>
</style>
@endsection

@section('import-scripts')
<script>
    
    $(document).ready(function () {
        $(".datepicker").datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            todayHighlight: true
        });

        $('.select2t-none').select2();

        $.validator.addMethod("greaterThan", function (value, element, param) {
            let startDate = $(param).val();
            if (!startDate) return true; // Allow empty start date to avoid premature errors

            let startParts = startDate.split("-"); // Convert dd-mm-yyyy to yyyy-mm-dd
            let endParts = value.split("-");

            let startFormatted = new Date(startParts[2], startParts[1] - 1, startParts[0]);
            let endFormatted = new Date(endParts[2], endParts[1] - 1, endParts[0]);

            return endFormatted >= startFormatted; // Ensure end date is greater or equal to start date
        }, "End date must be greater than or equal to the start date.");

        $.validator.addMethod("customDate", function(value, element) {
            // Check if the value matches dd-mm-yyyy format using regex
            return this.optional(element) || /^\d{2}-\d{2}-\d{4}$/.test(value);
        }, "Please enter a valid date in dd-mm-yyyy format.");


        $("#learning-request-form").validate({
            rules: {
                suggested_Learning: {
                    required: true,
                },
                reason: {
                    required: true,
                },
                learning_manager: {
                    required: true,
                },
                start_date: {
                    required: true,
                    customDate: true, // Use the custom date validation
                },
                end_date: {
                    required: true,
                    customDate: true, // Use the custom date validation
                    greaterThan: "#start_date", // Custom rule applied here
                },
            },
            messages: {
                suggested_Learning: {
                    required: "Please select the suggested learning program.",
                },
                reason: {
                    required: "Please enter a reason.",
                },
                learning_manager: {
                    required: "Please Select a L&D Manager.",
                },
                start_date: {
                    required: "Please select a start date.",
                },
                end_date: {
                    required: "Please select an end date.",
                },
            },
            submitHandler: function (form) {
                var formData = new FormData(form);

                let selectedEmployees = [];
                $(".employee-item input[type='checkbox']:checked").each(function () {
                    selectedEmployees.push($(this).val());
                });

                if (selectedEmployees.length === 0) {
                    toastr.error("Please select at least one employee.", "Error",{
                        positionClass: 'toast-bottom-right',
                    });
                    return false;
                }

                formData.append("employee_ids", JSON.stringify(selectedEmployees));

                $.ajax({
                    url: "{{ route('learning.request.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                        toastr.success(response.msg, "Success",{
                            positionClass: 'toast-bottom-right',
                        });
                            $("#learning-request-form")[0].reset();
                        } else {
                            toastr.error(response.msg, "Error",{
                                positionClass: 'toast-bottom-right',
                            });
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON.errors;
                        var errorMessages = "";
                        $.each(errors, function (key, error) {
                            errorMessages += error + "<br>";
                        });
                        toastr.error(errorMessages, "Error",{
                            positionClass: 'toast-bottom-right',
                        });
                    },
                });
            },
            errorPlacement: function(error, element) {
                if( element.attr("name") == "suggested_Learning" ) {
                    error.insertAfter( "#div-suggested_Learning" );
                } else if (element.attr("name") == "learning_manager") {
                    error.insertAfter(element.next('.select2')); // Place error after select2 container
                } else{
                    error.insertAfter(element);
                }

            },
            errorElement: 'span'
        });
    });
    
    document.addEventListener("DOMContentLoaded", function () {
        let searchInput = document.getElementById("searchInput");
        let employeeList = document.getElementById("employeeList");
        let employeeItems = document.querySelectorAll(".employee-item");
        
        // Create a no results message element
        let noResultsElement = document.createElement("span");
        noResultsElement.className = "no-results-message d-none";
        noResultsElement.textContent = "No employees found matching your search";
        noResultsElement.style.display = "block";
        noResultsElement.style.padding = "10px";
        noResultsElement.style.textAlign = "center";
        noResultsElement.style.fontStyle = "italic";
        
        // Add the no results message after the last employee item
        employeeList.appendChild(noResultsElement);

        searchInput.addEventListener("input", function () {
            let filter = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            employeeItems.forEach(function (employee) {
                let name = employee.querySelector(".employee-name").textContent.toLowerCase();
                let position = employee.querySelector(".employee-position").textContent.toLowerCase();

                if (name.includes(filter) || position.includes(filter)) {
                    employee.classList.add("d-flex");
                    employee.classList.remove("d-none");
                    visibleCount++;
                } else {
                    employee.classList.remove("d-flex");  
                    employee.classList.add("d-none");
                }
            });
            
            // Show or hide the no results message based on search results
            if (visibleCount === 0 && filter !== "") {
                noResultsElement.classList.remove("d-none");
            } else {
                noResultsElement.classList.add("d-none");
            }
        });
    });
</script>
@endsection