@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card">
                    <form id="final-settlement-form" method="POST" data-parsley-validate>
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-4 col-md-6">
                                <label for="select_emp" class="form-label">SELECT EMPLOYEE OR EMPLOYEE ID<span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" name="select_emp" id="select_emp" onchange="getEmpDetails(this.value)" 
                                    data-parsley-required="true" data-parsley-error-message="Please select an employee" data-parsley-errors-container="#select_emp_error">
                                    <option value="">Select Employees</option>    
                                    @if($employees)
                                        @foreach($employees as $emp)
                                            <option value="{{$emp->employee->id}}">{{$emp->employee->Emp_id}} - {{$emp->employee->resortAdmin->full_name}} </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div id="select_emp_error"></div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="empDetails-user">
                                    <div class="img-circle" id="img-circle">
                                        <img src="">
                                    </div>
                                    <div>
                                        <h4> <span class="badge badge-themeNew"></span></h4>
                                        <p></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="resignation_date" class="form-label">RESIGNATION EFFECTIVE DATE<span class="red-mark">*</span></label>
                                <input type="text" id="resignation_date" name="resignation_date" class="form-control" placeholder="RESIGNATION EFFECTIVE DATE"
                                    disabled required >
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="last_day" class="form-label">LAST WORKING DAY<span class="red-mark">*</span></label>
                                <input type="text" id="last_day" name="last_day" class="form-control" placeholder="LAST WORKING DAY" disabled required>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="basic_salary" class="form-label">Basic Salary<span class="red-mark">*</span></label>
                                <input type="number" min="0" max="9999999.99" step="0.01" id="basic_salary" class="form-control" name="basic_salary"
                                    placeholder="Basic salary" data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change" readonly>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="earned_salary" class="form-label">Earning Salary<span class="red-mark">*</span></label>
                                <input type="number" min="0" max="9999999.99" step="0.01" id="earned_salary" class="form-control" name="earned_salary"
                                    placeholder="Earning salary" data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change" readonly>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="pension" class="form-label">PENSION<span class="red-mark">*</span></label>
                                <input type="number" min="0" max="9999999.99" step="0.01" id="pension" class="form-control" name="pension"
                                    placeholder="Pension" data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change" readonly>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="tax" class="form-label">Tax<span class="red-mark">*</span></label>
                                <input type="number" id="tax" min="0" max="9999999.99" step="0.01" class="form-control" name="tax"
                                    placeholder="Tax" data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change" readonly>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="leave_balance" class="form-label">Leave Balance<span class="red-mark">*</span></label>
                                <input type="number" name="leave_balance" id="leave_balance" class="form-control" min="0" max="500" placeholder="Leave Balance" readonly data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change">
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="leave_encashment" class="form-label">LEAVE ENCASHMENT<span class="red-mark">*</span></label>
                                <input type="number" id="leave_encashment" name="leave_encashment" class="form-control" 
                                    min="0" max="9999999.99" step="0.01" placeholder="Leave Encashment"
                                    data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change" readonly>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="service_charge" class="form-label">Service Charge<span class="red-mark">*</span></label>
                                <input type="number" id="service_charge" class="form-control" 
                                    min="0" max="9999999.99" step="0.01" name="service_charge" placeholder="Service Charge"
                                    data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change">
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="loan_payment" class="form-label">LOAN OR ADVANCE PAYMENT?<span class="red-mark">*</span></label>
                                <input type="number" id="loan_payment" name="loan_payment" class="form-control" 
                                    min="0" max="9999999.99" step="0.01" placeholder="Enter Amount"
                                    data-parsley-required="true" data-parsley-type="number"
                                    data-parsley-min="0" data-parsley-trigger="change" readonly>
                            </div>

                        </div>
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                Allowance Breakdown
                            </div>
                            <div class="card-body fullFinal-block">
                                <table class="table" id="allowance-details">
                                    <thead>
                                        <tr>
                                            <th>Allowance ID</th>
                                            <th>Allowance Name</th>
                                            <th>Original Amount</th>
                                            <th>Converted Amount (MVR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total</th>
                                            <th id="total-allowances">0.00 MVR</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">Deduction</div>
                            <div class="fullFinal-block">
                                <div class="row g-md-4 g-3">
                                    <!-- Initial deduction row -->
                                    <div class="col-xl-3 col-sm">
                                        <select class="form-select select2t-none deduction-select" data-parsley-required-if="#deduction-amount-first" data-parsley-trigger="change">
                                            <option value="">Select Deduction</option>
                                            @foreach($deductions as $deduction)
                                                <option value="{{ $deduction->id }}" data-unit="{{ $deduction->currency }}">{{ $deduction->deduction_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-sm">
                                        <input type="number" id="deduction-amount-first" class="form-control deduction-amount" placeholder="Enter Amount" data-parsley-type="number" data-parsley-min="0" data-parsley-trigger="change">
                                    </div>
                                    <div class="col-xl-3 col-sm">
                                        <input type="text" class="form-control amount-unit" placeholder="Amount Unit" readonly>
                                    </div>
                                    <div class="col-xl-3 col-auto align-self-end">
                                        <a href="#" class="btn btn-themeSkyblue btn-sm add-fullFinal add-deduction">Add More</a>
                                    </div>
                                </div>
                            </div>
                            <div class="deductions-container"></div>
                        </div>
                        <input type="hidden" name="last_working_date" id="last_working_date"/>
                        <input type="hidden" name="payroll_start_date" id="payroll_start_date"/>
                        <input type="hidden" name="payment_mode" id="payment_mode"/>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue  @if(Common::checkRouteWisePermission('payslip.fullandfinalsettlement',config('settings.resort_permissions.create')) == false) d-none @endif">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    select.parsley-error + .select2 .select2-selection {
    border-color: #dc3545 !important; /* red border */
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        var $form = $("#final-settlement-form");
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

        // Fix for Select2 + Parsley (force Parsley to trigger on change)
        window.Parsley.on('field:validated', function(fieldInstance) {
            if ($(fieldInstance.$element).hasClass('select2-hidden-accessible')) {
                if (fieldInstance.validationResult !== true) {
                    fieldInstance._ui.$errorsWrapper.show();
                } else {
                    fieldInstance._ui.$errorsWrapper.hide();
                }
            }
        })

        // Use event delegation for deduction selects
        $(document).on('change', '.deduction-select', function() {
            let selectedOption = $(this).find(':selected');
            let deductionUnit = selectedOption.data('unit'); // Get unit from selected deduction
            $(this).closest('.row').find('.amount-unit').val(deductionUnit); // Set unit in the closest amount unit field
        });
    });

    function getEmpDetails(empId){
        if (!empId) return; // If no employee is selected, do nothing

        $.ajax({
            url: "{{route('employee.get.details')}}", // Route to fetch employee details
            type: "GET",
            data: { employee_id: empId },
            success: function(response) {
                if (response.success) {
                    console.log(response.data);
                    // Update Profile Picture
                    $("#img-circle img").attr("src", response.data.profile_picture || "assets/images/user-2.svg");

                    // Update Name and Employee ID
                    $(".empDetails-user h4").html(response.data.full_name + 
                        ` <span class="badge badge-themeNew">#${response.data.emp_id}</span>`);

                    // Update Position & Department
                    $(".empDetails-user p").text(response.data.position + " - " + response.data.department);

                    // Update Resignation & Last Working Day
                    $("#resignation_date").val(response.data.resignation_date || "N/A");
                    $("#last_day").val(response.data.last_working_day || "N/A");
                    $('#last_working_date').val(response.data.last_working_day || "N/A");
                    $('#payroll_start_date').val(response.data.payroll_start || "N/A");
                    $('#payment_mode').val(response.data.payment_mode || "Cash");
                    
                    // Set values and reset validation field by field
                    setFieldValueAndResetValidation('#leave_balance', response.data.leave_balance);
                    setFieldValueAndResetValidation('#basic_salary', response.data.basic_salary_mvr || 0);
                    setFieldValueAndResetValidation('#earned_salary', response.data.earned_salary || 0);
                    setFieldValueAndResetValidation('#pension', response.data.pension || 0);
                    setFieldValueAndResetValidation('#tax', response.data.ewt || 0);
                    setFieldValueAndResetValidation('#leave_encashment', response.data.leave_encashment || 0);
                    setFieldValueAndResetValidation('#loan_payment', response.data.loan_recovery || 0);
                    setFieldValueAndResetValidation('#service_charge', 0);
                    
                  

                    // Populate allowance breakdown
                    let $allowanceTableBody = $("#allowance-details tbody");
                    $allowanceTableBody.empty();

                   // Store allowance data globally or attach to a hidden input
                    let allowanceData = response.data.allowances || [];
                    let totalAllowances = response.data.allowances_mvr || 0;

                    // Inject into table
                    let tbody = $("#allowance-details tbody");
                    tbody.empty();
                    allowanceData.forEach(a => {
                        tbody.append(`<tr>
                            <td>${a.id}</td>
                            <td>${a.name}</td>
                            <td>${a.original_amount}</td>
                            <td>${a.converted_amount} MVR</td>
                        `);
                    });
                    $("#total-allowances").text(totalAllowances + " MVR");

                    // Store allowances as hidden input
                    if ($("#allowances_json").length) {
                        $("#allowances_json").val(JSON.stringify(allowanceData));
                    } else {
                        $("#final-settlement-form").append(`<input type="hidden" name="allowances" id="allowances_json" value='${JSON.stringify(allowanceData)}'>`);
                    }
                    
                    // Re-initialize form validation
                    $('#final-settlement-form').parsley().reset();
                } else {
                    alert("Employee details not found!");
                }
            },
            error: function() {
                alert("Error fetching employee details.");
            }
        });
    }

    // Helper function to set value and reset validation
    function setFieldValueAndResetValidation(selector, value) {
        const field = $(selector);
        field.val(value);
        
        // Reset field validation
        if (field.parsley()) {
            field.parsley().reset();
            
            // If the field has a value and is required, validate it
            if (value && field.attr('data-parsley-required')) {
                field.parsley().validate();
            }
        }
    }

    $(".add-deduction").click(function(e) {
        e.preventDefault();
        $(".deductions-container").append(`
            <div class="fullFinal-block">
                <div class="row g-md-4 g-3 deduction-row">
                    <div class="col-xl-3 col-sm">
                        <select class="form-select select2t-none deduction-select" name="deductionFor[]">
                            <option value="">Select Deduction</option>
                            @foreach($deductions as $deduction)
                                <option value="{{$deduction->id}}" data-unit="{{$deduction->currency}}">{{$deduction->deduction_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-sm">
                        <input type="number" class="form-control deduction-amount" placeholder="Enter Amount" name="deduction_amount[]"
                            data-parsley-type="number" data-parsley-min="0" data-parsley-required-if="#deductionFor" data-parsley-trigger="change">
                    </div>
                    <div class="col-xl-3 col-sm">
                         <input type="text" class="form-control amount-unit" name="amount_unit[]" placeholder="Amount Unit" redaonly>
                     </div>
                    <div class="col-xl-3 col-auto align-self-end">
                        <a href="#" class="btn btn-danger btn-sm remove-deduction">Remove</a>
                    </div>
                </div>
            </div>
        `);
        $('.select2t-none').select2();
    });

    // Remove Deduction
    $(document).on("click", ".remove-deduction", function(e) {
        e.preventDefault();
        $(this).closest(".fullFinal-block").remove();
    });

  
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
                $('#final-settlement-form').parsley({
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

        // Alpha-only Input Handling
        function initAlphaOnlyInputs() {
            $('.alpha-only').on('keyup blur', function() {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
            });
        }

        // Form Submission Handling
        function initFormSubmission() {
            $('#final-settlement-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);

                if (form.parsley().validate()) {
                    let formData = new FormData(this);

                    // Get structured deductions
                    let deductions = [];
                    $(".deduction-row").each(function () {
                        let id = $(this).find('.deduction-select').val();
                        let amount = $(this).find('.deduction-amount').val();
                        let unit = $(this).find('.amount-unit').val();
                        if (id && amount) {
                            deductions.push({ id: id, amount: amount, unit: unit });
                        }
                    });

                    // Add deductions as JSON string
                    formData.append('deductions', JSON.stringify(deductions));

                    // Optional: You can do the same for earnings if needed

                    // Disable button
                    $('#submit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Submitting...');

                    // Ajax request
                    $.ajax({
                        url: '{{ route("final.settlement.store") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            setTimeout(function () {
                                window.location.href = "{{ route('final.settlement.review', ':id') }}".replace(':id', response.final_settlement_id);
                            }, 2000);
                        },
                        error: function(xhr) {
                            let errorMessage = 'Submission failed.';
                            if (xhr.responseJSON?.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                            toastr.error(errorMessage, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $('#submit').prop('disabled', false).html('Submit Application');
                        }
                    });
                } else {
                    return false;
                }
            });
        }


        // Initialize All Validations and Plugins
        function initializeFormValidation() {
            initSelect2AndValidation();
            initParsleyValidation();
            initAlphaOnlyInputs();
            initFormSubmission();
        }

        // Call initialization when document is ready
        $(document).ready(initializeFormValidation);
    });
</script>
@endsection