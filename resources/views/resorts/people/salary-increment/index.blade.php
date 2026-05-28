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
                                {{-- The placeholder option MUST have value="" so Select2
                                     renders it as a placeholder. Without it the field
                                     showed blank/broken because Select2 couldn't latch
                                     onto the selected-disabled option. --}}
                                <select class="form-select select2t-none" name="increment_type" id="select_build" data-parsley-required-message="Please select increment type" required data-parsley-errors-container="#incrementTypeError">
                                    <option value="">Increment Type</option>
                                    @foreach ($incrementTypes as $increment_type)
                                        <option value="{{$increment_type->name}}">{{$increment_type->name}}</option>
                                    @endforeach
                                </select>
                                <div id="incrementTypeError"></div>
                            </div>
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                {{-- "Pay Increase Type" placeholder option removed per
                                     request. The select now defaults to Fixed and
                                     lets HR switch to Percentage when needed. --}}
                                <select class="form-select select2t-none pay-increase-type" name="pay_increase_type" data-parsley-errors-container="#payIncreaseTypeError">
                                    @foreach ($payIncreaseTypes as $key => $type)
                                        <option value="{{$key}}" {{ $key === 'Fixed' ? 'selected' : '' }}>{{$type}}</option>
                                    @endforeach
                                </select>
                                <div id="payIncreaseTypeError"></div>
                            </div>
                            
                            <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                <div class="input-group value-input-group">
                                    {{-- Currency prefix (system symbol) shown when Fixed is selected --}}
                                    <span class="input-group-text currency-prefix">{{ Common::GetResortCurrencySymbol() }}</span>
                                    <input type="number" class="form-control value" name="value" placeholder="Enter fixed value" required min="0" max="999999.99" data-parsley-required-message="Please Enter Amout/Percentage Increment"/>
                                    {{-- Percentage suffix shown when Percentage is selected --}}
                                    <span class="input-group-text percent-suffix d-none">%</span>
                                </div>
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
<style>
    /* Salary Increment cards + bulk-action bar — keep Select2 dropdowns at
       the same font-size and height as the plain form-control inputs in
       the same form. Select2 defaults render larger than Bootstrap, which
       makes "Increment Type" / "Pay Increase Type" look mismatched. */
    .salaryIncrementManag-block .select2-container,
    .salaryIncrementManageForm-bgBlock .select2-container {
        width: 100% !important;
    }
    .salaryIncrementManag-block .select2-container--default .select2-selection--single,
    .salaryIncrementManageForm-bgBlock .select2-container--default .select2-selection--single {
        height: 38px;
        border-radius: 6px;
        border-color: #ced4da;
    }
    .salaryIncrementManag-block .select2-container--default .select2-selection--single .select2-selection__rendered,
    .salaryIncrementManageForm-bgBlock .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 14px;
        line-height: 36px;
        color: #495057;
        padding-left: 12px;
        padding-right: 28px;
    }
    .salaryIncrementManag-block .select2-container--default .select2-selection--single .select2-selection__arrow,
    .salaryIncrementManageForm-bgBlock .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    /* Match form-control + form-select font-size to the selects above.
       Includes input[type] variants and placeholder pseudo so number/text
       inputs and their placeholder ("Amount/Percentage") render at the
       same 14px as everything else. */
    .salaryIncrementManag-block .form-control,
    .salaryIncrementManag-block .form-select,
    .salaryIncrementManag-block input[type="text"],
    .salaryIncrementManag-block input[type="number"],
    .salaryIncrementManag-block input[type="date"],
    .salaryIncrementManageForm-bgBlock .form-control,
    .salaryIncrementManageForm-bgBlock .form-select,
    .salaryIncrementManageForm-bgBlock input[type="text"],
    .salaryIncrementManageForm-bgBlock input[type="number"],
    .salaryIncrementManageForm-bgBlock input[type="date"] {
        font-size: 14px !important;
        height: 38px !important;
        line-height: 1.4 !important;
        padding: 6px 12px !important;
    }
    .salaryIncrementManag-block .form-control::placeholder,
    .salaryIncrementManag-block input::placeholder,
    .salaryIncrementManageForm-bgBlock .form-control::placeholder,
    .salaryIncrementManageForm-bgBlock input::placeholder {
        font-size: 14px !important;
        color: #6c757d;
    }
</style>
@endsection

@section('import-scripts')
<script>
    // Swap the value input's placeholder based on the pay-increase-type select
    // (Percentage → "Enter percentage", Fixed → "Enter fixed value"). Bound
    // once to the document so it works for dynamically-injected cards too.
    function applyValuePlaceholder($select) {
        const choice = ($select.val() || 'Fixed').toString();
        const $form  = $select.closest('form');
        const $value = $form.find('input[name="value"]').first();
        if (!$value.length) return;
        // The input-group wrapping the value field has BOTH a currency prefix
        // span (shown for Fixed) and a % suffix span (shown for Percentage).
        const $group         = $value.closest('.value-input-group');
        const $currencyPrefix = $group.find('.currency-prefix');
        const $percentSuffix  = $group.find('.percent-suffix');
        if (choice === 'Percentage') {
            $value.attr('placeholder', 'Enter percentage').attr('max', '100');
            $currencyPrefix.addClass('d-none');
            $percentSuffix.removeClass('d-none');
        } else {
            $value.attr('placeholder', 'Enter fixed value').attr('max', '999999.99');
            $currencyPrefix.removeClass('d-none');
            $percentSuffix.addClass('d-none');
        }
    }
    $(document).on('change', '.pay-increase-type', function () {
        applyValuePlaceholder($(this));
        // Re-evaluate the budget warning whenever the type flips —
        // switching Fixed ↔ Percentage changes how the increment amount
        // is interpreted, so the same value can suddenly cross/uncross
        // the budgeted-salary threshold.
        checkBudgetExceed($(this).closest('form'));
    });

    // Budget-exceed check for a single employee card. Mirrors the
    // promotion module's "Salary exceeded budget" yellow banner: if the
    // proposed new salary (current + increment) is higher than the
    // position's budgeted salary, show the warning. The form carries
    // `data-current-salary` and `data-budgeted-salary` so the JS does
    // not need to round-trip to the server.
    function checkBudgetExceed($form) {
        if (!$form || !$form.length) return;
        const current  = parseFloat($form.data('current-salary')  || 0);
        const budgeted = parseFloat($form.data('budgeted-salary') || 0);
        const type     = ($form.find('select[name="pay_increase_type"]').val() || 'Fixed').toString();
        const value    = parseFloat($form.find('input[name="value"]').val() || 0);
        const $warn    = $form.find('.budget-exceed-warning');
        const $text    = $form.find('.budget-exceed-text');

        if (!(budgeted > 0) || !(value > 0)) { $warn.addClass('d-none'); return; }

        const increment = (type === 'Percentage') ? (current * value / 100) : value;
        const newSalary = current + increment;
        if (newSalary > budgeted) {
            $text.text(
                'Salary exceeded budget — proposed $' + newSalary.toFixed(2) +
                ' is higher than the budgeted $' + budgeted.toFixed(2) +
                ' for this position.'
            );
            $warn.removeClass('d-none');
        } else {
            $warn.addClass('d-none');
        }
    }
    // Re-run the check whenever the amount/percentage value changes.
    $(document).on('input change', '.employee-increment-form input[name="value"]', function () {
        checkBudgetExceed($(this).closest('form'));
    });

    $(document).ready(function () {
        $('.select2t-none').select2();
        // Initialise placeholder on first render for every form on the page.
        $('.pay-increase-type').each(function () { applyValuePlaceholder($(this)); });

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
                        // Re-init Select2 on newly-injected dropdowns and
                        // apply the value-input placeholder for each card.
                        $('#employeeGird .select2t-none').select2();
                        $('#employeeGird .pay-increase-type').each(function () { applyValuePlaceholder($(this)); });
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
                const $f = $(this);
                $f.find('.increment-type').val(data.increment_type).trigger('change');
                $f.find('.pay-increase-type').val(data.pay_increase_type).trigger('change');
                $f.find('.value').val(data.value);
                $f.find('.effective-date').val(data.effected_date);
                $f.find('.remark').val(data.remark);
                // Bulk-apply doesn't fire input events on `.value`, so the
                // per-card budget warning needs to be re-evaluated manually.
                checkBudgetExceed($f);
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

