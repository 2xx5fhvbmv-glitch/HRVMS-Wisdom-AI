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
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <form id="add-new-vacancy" >
                    <div class="card">
                        <div class="row g-md-4 g-3 mb-4">
                            <div class="col-sm-6 ">
                                <label for="select-budgeted" class="form-label">BUDGETED OR OUT OF BUDGET?</label>
                                <!-- <select class="form-select select2t-none" id="select-budgeted"
                                    aria-label="Default select example" name="budgeted">
                                    <option value="Budgeted">Budgeted</option>
                                    <option value="Out Of Budgeted">Out Of Budgeted</option>
                                </select> -->
                                <select id="vacancy_status" class="form-select select2t-none" name="budgeted">
                                    <option value="Budgeted">Budgeted</option>
                                    <option value="Out of Budget">Out of Budget</option>
                                </select>
                            </div>
                            <div class="col-sm-6 d-md-inline-block d-none">

                            </div>
                            <div class="col-sm-6 ">
                                <label for="txt-department" class="form-label">DEPARTMENT</label>
                                <input type="text" class="form-control" name="department" id="txt-department" placeholder="DEPARTMENT" value="{{$department_details[0]->name}}" disabled>
                                <input type="hidden" class="form-control" name="dept_id" id="dept_id"  value="{{$department_details[0]->id}}" readonly>
                            </div>
                            <div class="col-sm-6 ">
                                <label for="txt-required-starting-date" class="form-label">REQUIRED STARTING DATE</label>
                                <input type="text" class="form-control datepicker" name="required_starting_date" id="txt-required-starting-date" placeholder="REQUIRED STARTING DATE">
                            </div>
                        </div>

                        <div>
                            <div class="col-12">
                                <div class="card-title ">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Position Details</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-4 g-3">
                                <div class="col-sm-6 ">
                                    <label for="txt-position-title" class="form-label">POSITION TITLE</label>
                                    <select name="position" id="position" class="form-control form-select">
                                        @if($resort_positions)
                                            <option value="">Select Position</option>
                                            @foreach($resort_positions as $position)
                                                <option value="{{$position->id}}" data-budgeted="{{ in_array($position->id, $budgetedPositionIds) ? '1' : '0' }}">{{$position->position_title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="txt-position-title" class="form-label">Required No of Vacancy</label>
                                    <input type="number" name="Total_position_required" id="Total_position_required" class="form-control" min="1"/>
                                    <div id="vacancy-validation-msg" style="display:none; margin-top:5px;"></div>
                                    <small id="vacancy-manning-info" class="text-muted" style="display:none; margin-top:3px;"></small>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="txt-reporting-to" class="form-label">REPORTING TO</label>
                                    <select name="reporting_to" id="reporting_to" class="form-control form-select">
                                        @if($reportingEmployees)
                                            <option value="">Select Reporting To</option>
                                            @foreach($reportingEmployees as $emp)
                                                <option value="{{$emp->id}}" {{ $emp_details[0]->id == $emp->id ? 'selected' : '' }}>{{$emp->first_name}}   {{$emp->last_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="txt-rank" class="form-label">RANK</label>
                                    <input type="text" class="form-control" id="txt-rank" placeholder="RANK" name="rank" disabled>
                                    <input type="hidden" class="form-control" id="rank_id" name="rank_id">
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="select-division" class="form-label">DIVISION</label>
                                    <input type="text" class="form-control" id="txt-division" name="division" placeholder="DIVISION" value="{{$resort_divisions[0]->name}}" disabled>
                                    <input type="hidden" class="form-control" id="division_id" name="division_id" value="{{$resort_divisions[0]->id}}">
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="select-selection" class="form-label">SECTION</label>
                                    <input type="text" class="form-control" id="txt-section" name="section" placeholder="SECTION" value="{{ $sectionName }}" disabled>
                                    <input type="hidden" class="form-control" id="section_id" name="section_id" value="{{ $sectionId }}">
                                </div>
                                <div class="col-12">
                                    <label for="select-selection" class="form-label">EMPLOYEE TYPE</label>
                                    <ul class="nav mt-2 ">
                                        <li class="form-radio">
                                            <input class="form-radio-input" type="radio" value="Permanant" id="radio-permanant" name="employee_type" checked>
                                            <label class="form-radio-label" for="radio-permanant">
                                                Permanant
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Casual/Agency" id="radio-casual-Agency" name="employee_type">
                                            <label class="form-radio-label" for="radio-casual-Agency">
                                                Casual/Agency
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Trainee / Intern" id="radio-trainee-intern" name="employee_type">
                                            <label class="form-radio-label" for="radio-trainee-intern">
                                                Trainee / Intern
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Replacement" id="radio-replacement" name="employee_type">
                                            <label class="form-radio-label" for="radio-replacement">
                                                Replacement
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Temporary / Project"
                                                id="radio-temporary-project" name="employee_type">
                                            <label class="form-radio-label" for="radio-temporary-project">
                                                Temporary / Project
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-12" id="temp-div" style="display: none;">
                                    <div id="" class="row g-md-4 g-3 row-cols-xl-5 row-cols-md-3  row-cols-sm-2 row-cols-1">
                                        <div class="col txt-service-provider" id="service-provider-container">
                                            <!-- Textbox for adding a new service provider -->
                                            <div>
                                                <!-- Textbox for new service provider -->
                                                <label for="new_service_provider">New Service Provider</label>
                                                <input type="text" name="new_service_provider" id="new_service_provider" placeholder="Enter new service provider" class="form-control">
                                            </div>

                                            <div>
                                                <!-- Selectbox for existing service providers -->
                                                <label for="service_provider">Select Service Provider</label>
                                                <select name="service_provider" id="service_provider" class="form-select">
                                                    <option value="">-- Select a service provider --</option>
                                                    @foreach($serviceProviders as $provider)
                                                        <option value="{{ $provider->name }}">{{ $provider->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col">
                                            <label for="txt-duration" class="form-label">DURATION</label>
                                            <input type="text" class="form-control" name="duration" id="txt-duration" placeholder="e.g. 3 Months, 6 Months, 1 Year">
                                        </div>

                                        <div class="col txt-salary">
                                            <label for="txt-budget-salary" class="form-label">Amount Unit <span class="req_span">*</span></label>
                                            <select name="amount_unit" id="amount_unit" required class="form-select">
                                                <option value="MVR">MVR</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </div>
                                        <div class="col txt-salary">
                                            <label for="select-salary" class="form-label">SALARY</label>
                                            <input type="text" name="salary" id="salary" class="form-control" placeholder="SALARY"/>
                                        </div>
                                        <div class="col txt-food">
                                            <label for="select-food" class="form-label">FOOD</label>
                                            <input type="text" name="food" id="food" class="form-control" placeholder="FOOD"/>
                                        </div>
                                        <div class="col txt-accommodation">
                                            <label for="txt-accommodation" class="form-label">ACCOMMODATION</label>
                                            <input type="text" class="form-control" name="accommodation" id="txt-accommodation" placeholder="ACCOMMODATION">
                                        </div>
                                        <div class="col txt-transporatation">
                                            <label for="txt-transporatation" class="form-label">TRANSPORTATION</label>
                                            <input type="text" class="form-control" name="transportation" id="txt-TRANSPORTATION" placeholder="TRANSPORTATION">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="replacement-employee" style="display: none;">
                            <div class="col-md-4 col-sm-6 mb-3">
                                <label for="txt-employee-name" class="form-label">Employee Name</label>
                                <select name="employee_name" id="txt-employee-name" class="form-control form-select select2t-none">
                                    <option value="">Select Employee</option>
                                    @if(isset($departmentEmployees))
                                        @foreach($departmentEmployees as $emp)
                                            <option value="{{ $emp->first_name }} {{ $emp->last_name }}">{{ $emp->first_name }} {{ $emp->last_name }} - {{ $emp->position_title ?? '' }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div id="permanent-div">
                            {{-- Budget, Funding & Benefits - commented out for now --}}
                            {{-- <div class="col-12">
                                <div class="card-title mt-md-4 mt-3">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Budget, Funding & Benefits</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-3 g-2">
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-budget-salary" class="form-label">Amount Unit <span class="req_span">*</span></label>
                                    <select name="amount_unit" id="amount_unit" required class="form-select">
                                        <option value="MVR">MVR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-budget-salary" class="form-label">BUDGET SALARY <span class="req_span">*</span></label>
                                    <input type="text" class="form-control" name="budget_salary" id="txt-budget-salary" placeholder="BUDGET SALARY" required>
                                </div>
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-acommocation2" class="form-label">ACOMMODATION</label>
                                    <input type="text" class="form-control" name="budgeted_accommodation" id="txt-acommocation2" placeholder="ACOMMODATION">
                                </div>
                                <div class="col-md-3 col-sm-6  ">
                                    <label for="txt-rank" class="form-label">SERVICE CHARGE</label>
                                    <ul class="d-flex navalign-items-center">
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="service_charge" value="YES" id="flexCheckservicechares-yes" checked>
                                            <label class="form-check-label" for="flexCheckservicechares-yes">
                                                Yes
                                            </label>
                                        </li>
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="service_charge" value="NO"
                                                id="flexCheckservicechares-no">
                                            <label class="form-check-label" for="flexCheckservicechares-no">
                                                No
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-proposed-salary" class="form-label">PROPOSED SALARY <span class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="txt-proposed-salary"
                                        placeholder="Proposed Salary" name="proposed_salary" required>
                                </div>
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-allowances" class="form-label">ALLOWANCES</label>
                                    <input type="text" class="form-control" name="allowance" id="txt-allowances" placeholder="Allowances">
                                </div>
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-uniform" class="form-label">UNIFORM</label>
                                    <ul class="d-flex nav align-items-center">
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="uniform" value="YES" id="flexCheckUNIFORM-yes" checked>
                                            <label class="form-check-label" for="flexCheckUNIFORM-yes">
                                                Yes
                                            </label>
                                        </li>
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="uniform" value="NO"  id="flexCheckUNIFORM-no">
                                            <label class="form-check-label" for="flexCheckUNIFORM-no">
                                                No
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-4 col-sm-6 ">
                                    <label for="txt-Medical" class="form-label">MEDICAL</label>
                                    <input type="text" class="form-control" name="medical" id="txt-Medical" placeholder="Medical">
                                </div>
                                <div class="col-md-4 col-sm-6 ">
                                    <label for="txt-Insurance" class="form-label">INSURANCE</label>
                                    <input type="text" class="form-control" name="insurance" id="txt-Insurance" placeholder="Insurance">
                                </div>
                                <div class="col-md-4 col-sm-6 ">
                                    <label for="txt-Pension" class="form-label">PENSION</label>
                                    <input type="text" class="form-control" name="pension" id="txt-Pension" placeholder="Pension">
                                </div>
                            </div> --}}

                            <div class="row g-md-3 g-2">
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-transport" class="form-label">For Local</label>
                                    <ul class="d-flex nav align-items-center">
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="is_required_local" value="Yes" id="is_local-yes" >
                                            <label class="form-check-label" for="is_local-yes">
                                                Yes
                                            </label>
                                        </li>
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="is_required_local" value="No"  id="is_local-no" checked>
                                            <label class="form-check-label" for="is_local-no">
                                                No
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="card-title mt-md-4 mt-3">
                                <div class="row justify-content-start align-items-center g-">
                                    <div class="col">
                                        <h3>Recruitment</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="recruitement[]" value="Online job posting" id="recruitment1" checked>
                                <label class="form-check-label" for="recruitment1">
                                    Online job posting
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="recruitement[]" value="Recruiter" id="recruitment2">
                                <label class="form-check-label" for="recruitment2">
                                    Recruiter
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="recruitement[]" value="Agency" id="recruitment3">
                                <label class="form-check-label" for="recruitment3">
                                    Agency
                                </label>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="col-md-4 col-sm-6 ">
                                <label for="txt-rank" class="form-label">Status</label>
                                <ul class="d-flex nav align-items-center">
                                    <li class="form-check ">
                                        <input class="form-check-input" type="radio" name="status" value="Active" id="flexCheckstatus-active" checked>
                                        <label class="form-check-label" for="flexCheckstatus-active">
                                            Active
                                        </label>
                                    </li>
                                    <li class="form-check ">
                                        <input class="form-check-input" type="radio" name="status" value="Inactive"  id="flexCheckstatus-inactive">
                                        <label class="form-check-label" for="flexCheckstatus-inactive">
                                            Inactive
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-footer row justify-content-between g-3">
                            <div class="col-auto">
                                <div class="d-flex align-items-center">
                                    <a href="#" class="btn btn-themeBlue btn-sm">Save As Draft</a>
                                    <!-- <a href="#" class="text-theme text-underline fw-600 mx-sm-3 mx-2">View</a>
                                    <a href="#" class="text-theme text-underline fw-600">Download</a> -->
                                </div>
                            </div>

                            <div class="col-auto ms-auto">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <script type="text/javascript">
        // new DataTable('#example');
        $(document).ready(function () {
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
            });

            $('#service_provider').on('change', function() {
                toggleInput();
            });

            $('#new_service_provider').on('input', function() {
                toggleInput();
            });

            // Function to toggle between select box and textbox
            function toggleInput() {
                const inputField = $('#new_service_provider');
                const selectBox = $('#service_provider');

                // Ensure elements exist
                if (inputField.length === 0 || selectBox.length === 0) {
                    console.error('Input or select element is missing.');
                    return;
                }

                const inputValue = inputField.val()?.trim(); // Safe navigation to prevent undefined
                const selectValue = selectBox.val();

                if (inputValue) {
                    selectBox.val('').prop('disabled', true);
                    inputField.prop('disabled', false);
                } else if (selectValue) {
                    inputField.val('').prop('disabled', true);
                    selectBox.prop('disabled', false);
                } else {
                    inputField.prop('disabled', false);
                    selectBox.prop('disabled', false);
                }
            }

            $('#position').on('change', function() {
                var positionId = $(this).val();
                if (positionId) {
                    $.ajax({
                        url: '{{ route("resort.getRank") }}',
                        type: 'GET',
                        data: { positionId: positionId },
                        success: function(response) {
                            $('#txt-rank').val(response.rank || ''); // Set the rank if available, else empty
                            $('#rank_id').val(response.rank_id)
                        },
                        error: function() {
                            console.error("An error occurred while fetching the rank.");
                        }
                    });
                } else {
                    $('#txt-rank').val(''); // Clear rank field if no position selected
                    $('#rank_id').val('');
                }
            });

            document.querySelectorAll('input[name="employee_type"]').forEach((radio) => {
                radio.addEventListener('change', function() {
                    const employmentType = this.value;

                    // Div elements
                    const permanentDiv = document.getElementById('permanent-div');
                    const tempDiv = document.getElementById('temp-div');
                    const replacementEmployee = document.getElementById('replacement-employee');

                    // Reset visibility
                    permanentDiv.style.display = 'none';
                    tempDiv.style.display = 'none';
                    replacementEmployee.style.display = 'none';

                    // Show/hide based on selection
                    if (employmentType === 'Permanant' || employmentType === 'Replacement') {
                        permanentDiv.style.display = 'block';
                    }
                    if (employmentType === 'Replacement') {
                        replacementEmployee.style.display = 'block';
                    }
                    if (employmentType === 'Casual/Agency' || employmentType === 'Trainee / Intern' || employmentType === 'Temporary / Project') {
                        tempDiv.style.display = 'block';
                        toggleInput();
                    }
                });
            });

            $('#add-new-vacancy').validate({
                rules: {
                    "budgeted": { required: true },
                    "department": { required: true },
                    "required_starting_date": { required: true },
                    "position": { required: true },
                    "reporting_to": { required: true },
                    "rank": { required: true },
                    "division": { required: true },
                    "section": { required: true },
                    "employee_type": { required: true },
                    "Total_position_required" :{ required: true },
                    "employee_name": {
                        required: function() {
                            return $("input[name='employee_type']:checked").val() === "Replacement";
                        }
                    }
                },
                messages: {
                    "budgeted": { required: "Budgeted field is required." },
                    "department": { required: "Department field is required." },
                    "required_starting_date": { required: "Required Starting date is required." },
                    "position": { required: "Position field is required." },
                    "reporting_to": { required: "Required To field is required." },
                    "rank": { required: "Rank field is required." },
                    "division": { required: "Division field is required." },
                    "section": { required: "Section field is required." },
                    "Total_position_required": { required: "Required No of Vacancy field is required." },
                    "employee_name": { required: "Employee Name is required when employee type is Replacement." }
                },
                submitHandler: function(form) {
                    // Prepare data to submit
                    var formData = $(form).serialize();

                    // Perform AJAX request
                    $.ajax({
                        url: '{{ route("resort.vacancies.store") }}', // Adjust route as needed
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        beforeSend: function() {
                            // Optional: show loading spinner or disable submit button
                            // $('#submit-button').attr('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                // If the save was successful, handle success response
                                toastr.success(response.msg, "Success", { positionClass: 'toast-bottom-right' });

                                window.location.href = '{{ route("resort.recruitement.hrdashboard") }}'; // Redirect to index page
                            } else {
                                // Handle specific errors returned from server if needed
                                toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        },
                        complete: function() {
                            // Optional: hide loading spinner or re-enable submit button
                            // $('#submit-button').attr('disabled', false);
                        }
                    });
                },
                errorPlacement: function(error, element) {
                    // Correctly handle Select2 error placement
                    if (element.hasClass("select2-hidden-accessible")) {
                        error.insertAfter(element.next('.select2')); // Adjust this line
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    // Highlight the Select2 elements properly
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                    } else {
                        $(element).addClass('is-invalid');
                    }
                },
                unhighlight: function(element) {
                    // Remove highlight from Select2 elements
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                    } else {
                        $(element).removeClass('is-invalid');
                    }
                }
            });

            var vacancyValidationTimer = null;

            function updateVacancyStatus(positionId, requestedVacancy) {
                $.ajax({
                    url: '{{route("resort.vacancies.getstatus")}}',
                    method: 'POST',
                    data: {
                        position_id: positionId,
                        requested_vacancy: requestedVacancy
                    },
                    success: function(response) {
                        const selectBox = $('#vacancy_status');
                        selectBox.val(response.status).trigger('change.select2');

                        $('#txt-budget-salary').val(response.budgeted_salary);
                        $('#txt-proposed-salary').val(response.proposed_salary);
                        $('#txt-Pension').val(response.pension);
                        $('#txt-allowances').val(response.allowance);
                        $('#txt-Medical').val(response.medical);
                        $('#txt-acommocation2').val(response.accommodation);
                        $('#txt-Insurance').val(response.insurance);

                        // Show manning info
                        var infoHtml = 'Approved: ' + response.headcount +
                            ' | Filled: ' + response.filledcount +
                            ' | Vacant: ' + response.vacantCount +
                            ' | Active Vacancies: ' + response.existingVacancies +
                            ' | Available: ' + response.availableSlots;
                        $('#vacancy-manning-info').html(infoHtml).show();

                        // Validate requested vacancy against available slots
                        var msgDiv = $('#vacancy-validation-msg');
                        var input = $('#Total_position_required');
                        var requested = parseInt(requestedVacancy) || 0;

                        if (response.availableSlots <= 0 && response.status === 'Budgeted') {
                            msgDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle"></i> No available vacant slots for this position. All ' + response.vacantCount + ' vacant position(s) already have active vacancy requests.</span>').show();
                            input.addClass('is-invalid');
                        } else if (requested > response.availableSlots && response.status === 'Budgeted') {
                            msgDiv.html('<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Requested (' + requested + ') exceeds available slots (' + response.availableSlots + '). This will be marked as Out of Budget.</span>').show();
                            input.removeClass('is-invalid');
                        } else if (response.status === 'Out of Budget') {
                            msgDiv.html('<span class="text-warning"><i class="fas fa-info-circle"></i> This vacancy is Out of Budget. Approved headcount: ' + response.headcount + ', Vacant: ' + response.vacantCount + ', Available: ' + response.availableSlots + '</span>').show();
                            input.removeClass('is-invalid');
                        } else {
                            msgDiv.html('<span class="text-success"><i class="fas fa-check-circle"></i> Within budget. ' + response.availableSlots + ' slot(s) available.</span>').show();
                            input.removeClass('is-invalid');
                        }
                    },
                    error: function()
                    {
                        toastr.error('Error fetching vacancy status.', 'Error', { positionClass: 'toast-bottom-right'});
                    }
                });
            }

            // Real-time validation on keyup with debounce
            $('#Total_position_required').on('input', function() {
                clearTimeout(vacancyValidationTimer);
                var self = this;
                vacancyValidationTimer = setTimeout(function() {
                    const positionId = $('#position').val();
                    const requestedVacancy = $(self).val();
                    if (positionId && requestedVacancy && parseInt(requestedVacancy) > 0) {
                        updateVacancyStatus(positionId, requestedVacancy);
                    } else {
                        $('#vacancy-validation-msg').hide();
                        $('#vacancy-manning-info').hide();
                        $(self).removeClass('is-invalid');
                    }
                }, 400); // 400ms debounce
            });

            // Also trigger when position changes
            $('#position').on('change', function() {
                const positionId = $(this).val();
                const requestedVacancy = $('#Total_position_required').val();
                if (positionId && requestedVacancy && parseInt(requestedVacancy) > 0) {
                    updateVacancyStatus(positionId, requestedVacancy);
                } else {
                    $('#vacancy-validation-msg').hide();
                    $('#vacancy-manning-info').hide();
                }
            });

            // Filter position dropdown based on budget status selection
            function filterPositionsByBudget(budgetStatus) {
                const positionSelect = $('#position');
                positionSelect.val('').trigger('change');

                positionSelect.find('option').each(function() {
                    const option = $(this);
                    if (!option.val()) return; // Skip "Select Position" placeholder

                    if (budgetStatus === 'Budgeted') {
                        // Show only budgeted positions
                        if (option.data('budgeted') == 1) {
                            option.prop('disabled', false).show();
                        } else {
                            option.prop('disabled', true).hide();
                        }
                    } else {
                        // Out of Budget - show all positions
                        option.prop('disabled', false).show();
                    }
                });
            }

            // Trigger filter on budget status change
            $('#vacancy_status').on('change', function() {
                filterPositionsByBudget($(this).val());
            });

            // Apply filter on page load based on default selection
            filterPositionsByBudget($('#vacancy_status').val());

        });
    </script>
@endsection
