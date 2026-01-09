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
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Initiate Promotion</a></div> -->
                </div>
            </div>

            <div class="card card-salaryIncrementManag">
                <div class="row g-2 mb-2">
                    <div class="col-lg-4 col-sm-6 col">
                        <div class="input-group">
                            <input type="search" class="form-control " id="search_tearm" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    {{-- <div class="col-auto"><a href="#" class="btn btn-themeGrayLight">Filter</a></div> --}}
                </div>
                <div class="salaryIncrementManag-bgBlock bg-themeGrayLight mb-md-4 mb-3">
                    <div class="row g-lg-4 g-2">
                        <div class="col-xl-4 col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="exclude_disciplinary" id="excludeDisciplinary" value="1" >
                                <label class="form-check-label" for="excludeDisciplinary">Exclude employees with active disciplinary actions</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="exclude_probation" id="excludeProbation" value="1" >
                                <label class="form-check-label" for="excludeProbation">Exclude employees on probation</label>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="exclude_recent_promotion" id="excludeRecentPromotion" value="1" >
                                <label class="form-check-label" for="excludeRecentPromotion">Exclude employees who recently got a promotion</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="exclude_no_training" id="excludeNoTraining" value="1" >
                                <label class="form-check-label" for="excludeNoTraining">Exclude employees who have not completed mandatory onboarding training</label>
                            </div>
                        </div>
                        {{-- <div class="col-xl-4 col-md-6 align-self-center">
                            <div class="row g-2">
                                <div class="col"><input type="text" class="form-control" placeholder="Custom Filter">
                                </div>
                                <div class="col-auto"><a href="#" class="btn btn-themeBlue">Add</a></div>
                            </div>
                        </div> --}}
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="appealReviewDecision" id="selectEmp"
                        value="option1">
                    <label class="form-check-label" for="selectEmp">Select All Employees</label>
                </div>

                <div class="salaryIncrementManageForm-bgBlock bg-themeGrayLight mb-md-4 mb-3 d-none">
                    <h6 class="fw-600 mb-2">Bulk Action: <span id="employeeCount"></span> Employees</h6>
                    {{-- <div class="row g-md-3 g-2"> --}}
                        <form action="{{route('people.salary-increment.index')}}" method="GET" class="row g-md-3 g-2 salary-increment-bulk-form" data-parsley-validate>
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                <select class="form-select select2t-none" name="increment_type" id="select_build" data-parsley-required-message="Please select increment type" required data-parsley-errors-container="#incrementTypeError">
                                    <option selected disabled>Increment Type</option>
                                    @foreach ($incrementTypes as $increment_type)
                                        <option value="{{$increment_type->name}}">{{$increment_type->name}}</option>
                                    @endforeach
                                </select>
                                <div id="incrementTypeError"></div>
                            </div>
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                 <select class="form-select select2t-none pay-increase-type" name="pay_increase_type" required data-parsley-required-message="Please select pay increase type" data-parsley-errors-container="#payIncreaseTypeError">
                                    <option selected value="">Pay Increase Type</option>
                                    @foreach ($payIncreaseTypes as $key => $type)
                                        <option value="{{$key}}" >{{$type}}</option>
                                    @endforeach
                                </select>
                                <div id="payIncreaseTypeError"></div>
                            </div>
                            
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                <input type="number" class="form-control" name="value" placeholder="Enter value" required min="0" max="999999.99" data-parsley-required-message="Please Enter Amout/Percentage Increment"/>
                            </div>
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                <input type="text" class="form-control datepicker" name="effected_date" placeholder="Effective Date" required data-parsley-required-message="Please Select Effective Date"/>
                            </div>
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                <input type="text" class="form-control" name="remark" placeholder="Remark" required data-parsley-required-message="Please Enter remarks"/>
                            </div>
                        <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                            <button href="#" class="btn btn-themeBlue w-100" type="submit">Apply To Selected</button></div>
                        </form>
                    {{-- </div> --}}
                </div>

                
                    <div id="employeeGird">
                    </div>

                <div class="card-footer">
                    <div class="row g-2">
                        <div class="col-auto ms-auto"> <a href="#" class="btn btn-themeBlue btn-sm" id="NextButton">Next</a></div>
                    </div>
                    {{-- <div class="row g-2">
                        <div class="col-auto ms-auto"> <a href="#" class="btn btn-themeBlue btn-sm" id="bulkSubmitBtn">Submit</a></div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.select2t-none').select2();
    
        $(".datepicker").datepicker({
            format: 'dd/mm/yyyy', 
            autoclose: true,   
            todayHighlight: true,
            startDate: new Date() // Disables all past dates

        });

             $('#selectEmp').on('change', function () {
                const isChecked = $(this).is(':checked');
                if (isChecked) {
                    $('.salaryIncrementManageForm-bgBlock').removeClass('d-none'); // Show the div
                } else {
                    $('.salaryIncrementManageForm-bgBlock').addClass('d-none'); // Hide the div
                    
                    $('.salaryIncrementManageForm-bgBlock').find('input, select').each(function () {
                        if ($(this).is('select')) {
                            $(this).val('').trigger('change'); // Reset select fields and trigger change for select2
                        } else {
                            $(this).val(''); // Reset input fields
                        }
                    });

                    $('.employee-increment-form').each(function () {
                        $(this).find('input, select').each(function () {
                            if ($(this).is('select')) {
                                $(this).val('').trigger('change'); // Reset select fields and trigger change for select2
                            } else {
                                $(this).val(''); // Reset input fields
                            }
                        });
                    });
                }
            });

               $('.filter-checkbox').on('change', function () {  
                    let filters = {};
                    $('.filter-checkbox').each(function () {
                        filters[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
                    });
                    loadEmployeeGridView(filters);
                });

                $('#search_tearm').on('keyup', function () {
                    let searchTerm = $(this).val();
                    let filters = {
                        search: searchTerm
                    };
                    $('.filter-checkbox').each(function () {
                        filters[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
                    });
                    loadEmployeeGridView(filters);
                });

       function loadEmployeeGridView(filters = {}, page = 1) {
            filters.page = page; // append page to filters

            $.ajax({
                url: "{{ route('people.salary-increment.employee.grid-view') }}",
                type: "GET",
                data: filters,
                success: function (response) {
                    if (response.success) {
                        $('#employeeGird').html(response.html);
                        $('#employeeCount').text(response.employee_count);
                        initializeSalaryIncrementManageDiv();
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error loading employee list:", error);
                }
            });
        }

        // Initial load
        loadEmployeeGridView();

        // Delegate pagination link clicks
        $(document).on('click', '#employeeGird .pagination a', function (e) {
            e.preventDefault();
            const page = $(this).attr('href').split('page=')[1];
            const filters = $('#yourFilterForm').serializeArray(); // Replace with actual filter form ID if exists

            let filterObj = {};
            filters.forEach(item => {
                filterObj[item.name] = item.value;
            });

            loadEmployeeGridView(filterObj, page);
        });

        function initializeSalaryIncrementManageDiv(){
            const containers = document.querySelectorAll(".salaryIncrementManag-inner");
    
            function adjustHeights() {
                containers.forEach(container => {
                    const frontBlock = container.querySelector(".salaryIncrementManag-block.front");
                    const backBlock = container.querySelector(".salaryIncrementManag-block.back");
    
                    if (frontBlock && backBlock) {
                        // Remove any fixed height first to allow natural content height
                        frontBlock.style.height = "auto";
                        backBlock.style.height = "auto";
                        container.style.height = "auto";
    
                        // Get updated heights
                        const frontHeight = frontBlock.offsetHeight;
                        const backHeight = backBlock.offsetHeight;
                        const maxHeight = Math.max(frontHeight, backHeight);
    
                        // Apply the max height to both blocks and the main container
                        container.style.height = `${maxHeight}px`;
                        frontBlock.style.height = `${maxHeight}px`;
                        backBlock.style.height = `${maxHeight}px`;
                    }
                });
            }
    
            adjustHeights();
    
            window.addEventListener("resize", adjustHeights);
    
            $(".datepicker").datepicker({
                format: 'dd/mm/yyyy', 
                autoclose: true,   
                todayHighlight: true,
                startDate: new Date() // Disables all past dates
            }).on('changeDate', function() {
                $(this).parsley().validate();
            });

            // Flip effect for each card
            document.querySelectorAll('.flipBtn').forEach((btn, index) => {
                btn.addEventListener('click', function () {
                    containers[index].classList.add('is-flipped');
                    setTimeout(adjustHeights, 300); // Ensure height updates after flip animation
                });
            });
    
            document.querySelectorAll('.flipBtnBack').forEach((btn, index) => {
                btn.addEventListener('click', function () {
                    containers[index].classList.remove('is-flipped');
                    setTimeout(adjustHeights, 300); // Ensure height updates after flip animation
                });
            });
        }
    });
    

    $(document).ready(function () {
        $('.salary-increment-bulk-form').on('submit', function (e) {
            e.preventDefault();

            $('.salary-increment-bulk-form').parsley();

            let formData = $(this).serializeArray();
            let data = {};
            let hasError = false;

            formData.forEach(function (field) {
                data[field.name] = field.value;

                if (!field.value || field.value.trim() === '') {
                    toastr.error(`The field ${field.name} cannot be empty.`, "Validation Error", {
                    positionClass: 'toast-bottom-right'
                    });
                    hasError = true;
                }
            });

            if (hasError) {
                return; 
            }

            // Apply data to each employee's increment form
            let count = 0; // Initialize counter
            $('.employee-increment-form').each(function () {
                $(this).find('.increment-type').val(data.increment_type).trigger('change');
                $(this).find('.pay-increase-type').val(data.pay_increase_type).trigger('change');
                $(this).find('.value').val(data.value);
                $(this).find('.effective-date').val(data.effected_date);
                $(this).find('.remark').val(data.remark);
                count++; // Increment counter for each form
            });

            toastr.success(`Applied increment data to ${count} employee forms.`, "Success", {
                positionClass: 'toast-bottom-right'
            });
        });
    });

    $('#NextButton').click(function (e) {
        e.preventDefault();

        let payload = [];

        $('.employee-increment-form').each(function () {
            let $form = $(this);

            // Validate with Parsley
            if (!$form.parsley().validate()) {
                hasValidationError = true;
                return; // Skip processing this form
            }

            let data = {
                emp_id: $form.find('input[name="emp_id"]').val(),
                increment_type: $form.find('select[name="increment_type"]').val(),
                pay_increase_type: $form.find('select[name="pay_increase_type"]').val(),
                value: $form.find('input[name="value"]').val(),
                effective_date: $form.find('input[name="effective_date"]').val(),
                remark: $form.find('input[name="remark"]').val(),
            };

            if (data.increment_type ||data.pay_increase_type || data.value || data.effective_date || data.remark) {
                if (data.increment_type && data.value && data.effective_date) {
                    payload.push(data);
                } else {
                     
                    toastr.error("Please fill all required fields for employee ID: " + data.emp_id, "Validation Error",{
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });

        if (payload.length === 0) {
            toastr.error("Please fill at least one increment form correctly.", "Validation Error",{
                positionClass: 'toast-bottom-right'
            });
            return;
        }

        $.ajax({
            url: "{{ route('people.salary-increment.summary-store') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                increments: payload
            },
            
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    window.location.href = response.redirect_url;
                
                }
            },
            error: function (xhr) {
                let err = 'An error occurred.';
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    err = Object.values(errors).map(e => e[0]).join('<br>');
                }
                toastr.error(err, "Error");
            }
        });
    });

</script>
@endsection

