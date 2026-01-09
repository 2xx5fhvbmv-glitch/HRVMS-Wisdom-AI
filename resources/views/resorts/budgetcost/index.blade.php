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
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                         <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                        

                    </div>    
                    </div>
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Costs</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="#" class="btn btn-sm btn-theme " data-bs-toggle="modal"
                                            data-bs-target="#add-costmodal">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="costs-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Particulars</th>
                                    <th class="text-nowrap">Amount</th>
                                    <th class="text-nowrap">Amount Unit</th>
                                    <th class="text-nowrap">Type</th>
                                    <th class="text-nowrap">Frequnecy</th>
                                    <th class="text-nowrap">Details</th>
                                    <th class="text-nowrap">Status</th>
                                    <th class="text-nowrap">Is Payroll Allowance</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Division Modal -->
<div class="modal fade" id="add-costmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Add Cost</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <form id="addCostForm" data-parsley-validate>
                        @csrf
                        <div class="form-group mb-2">
                            <label  class="form-label cost-name " for="cost-select">Select Cost Name  <span class="req_span">*</span></label>
                            <select id="cost-select" name="cost" 
                            data-parsley-errors-container="#div-cost" 
                            required data-parsley-required-message="Please select a cost."
                            class="form-select select2t-none">
                                <option value="">Select Cost Name</option>
                                <option value="Recruitment Cost">Recruitment Cost</option>
                                <option value="Operational Cost">Operational Cost</option>
                            </select>
                            <div id="div-cost"></div>

                        </div>

                        <label class="mx-auto d-block text-center mb-2">OR</label>

                        <div class="form-group mb-20">
                            <label  class="form-label New-cost-name" for="cost-select">New Cost Name  <span class="req_span">*</span></label>

                            <input type="text" id="new-cost-name" name="cost_name" class="form-control"
                                placeholder="Add Name of Cost"
                                required
                                  required data-parsley-required-message="Please enter a cost name."
                                data-parsley-pattern="^[a-zA-Z0-9\s\-_/\.]+$"
                                data-parsley-pattern-message="Only letters, numbers, spaces, hyphens (-), underscores (_), periods (.), and slashes (/) are allowed.">
                        </div>

                        <div class="form-group mb-20">
                            <label  class="form-label " for="cost-select">Particulars  <span class="req_span">*</span></label>    
                        <input type="text" id="particulars" name="particulars" class="form-control"
                                placeholder="Add Particulars"
                                required
                                data-parsley-required-message="Please enter a particulars."

                                data-parsley-pattern="^[a-zA-Z0-9\s\-_/\.]+$"
                                data-parsley-pattern-message="Only letters, numbers, spaces, hyphens (-), underscores (_), periods (.), and slashes (/) are allowed.">
                        </div>

                        <div class="form-group mb-20 row">
                            <div class="col-md-6">
                                <label  class="form-label " for="cost-select">Add Amount  <span class="req_span">*</span></label>
                                <input type="number" min="0" max="9999999.99" step="0.01" id="amount" name="amount" class="form-control" placeholder="Add Amount" required step="any"  data-parsley-required-message="Please enter a valid amount." data-parsley-min="0" data-parsley-min-message="Amount cannot be negative." 
                                data-parsley-type="number">
                            </div>
                                <div class="col-md-6">
                                    <label  class="form-label " for="cost-select">Add Amount Unit <span class="req_span">*</span></label>    

                                    <select id="amount_unit" name="amount_unit" class="form-select select2t-none" 
                                        required data-parsley-required-message="Please select an amount unit."
                                        data-parsley-errors-container="#div-amount-unit">
                                        <option value="">Select Amount Unit</option>
                                        <option value="USD">USD</option>
                                        <option value="MVR">MVR</option>
                                        <option value="%">%</option>
                                    </select>
                                    <div id="div-amount-unit"></div>
                                </div>
                        </div>

                        <div class="form-group mb-20">
                            <label  class="form-label " for="cost-select">Cost Type  <span class="req_span">*</span></label>    

                            <select id="costtype-select" name="cost_type"
                               data-parsley-errors-container="#div-costtype" 
                            required data-parsley-required-message="Please select a cost type."
                            class="form-select select2t-none" required>
                                <option value="">Select Type</option>
                                <option value="Fixed">Fixed</option>
                                <option value="Variable">Variable</option>
                            </select>
                           <div id="div-costtype"></div>

                        </div>

                        {{-- <div class="form-group mb-20">
                            <label  class="form-label " for="cost-select">Add Frequency  <span class="req_span">*</span></label>    

                            <input type="text" id="frequency" name="frequency" class="form-control" placeholder="Add Frequency"
                                required
                                data-parsley-required-message="Please enter a frequency."
                                data-parsley-pattern="^[a-zA-Z0-9\s\-_/\.]+$"
                                data-parsley-pattern-message="Only letters, numbers, spaces, hyphens (-), underscores (_), periods (.), and slashes (/) are allowed.">
                        </div> --}}

                        <div class="form-group mb-20">
                            <label  class="form-label " for="cost-select">Add Frequency  <span class="req_span">*</span></label>    
                            <select id="frequency" name="frequency"
                               data-parsley-errors-container="#div-frequency" 
                            required data-parsley-required-message="Please select a frequency."
                            class="form-select select2t-none" required>
                                <option value="">Select frequency</option>
                                <option value="Daily">Daily</option>
                                <option value="Month">Month</option>
                                <option value="Quarter">Quarter</option>
                                <option value="Year">Year</option>
                                <option value="One time cost">One time cost</option>
                            </select>
                           <div id="div-frequency"></div>

                        </div>

                        <div class="form-group mb-20">
                            
                            <label  class="form-label " for="cost-select">Select Details<span class="req_span">*</span></label>    
                            <select id="details-select" name="details"
                               data-parsley-errors-container="#div-details" 
                               required data-parsley-required-message="Please select a details."
                             class="form-select select2t-none" required>
                                <option value="">Select Details</option>
                                <option value="Xpat Only">For Xpat Only</option>
                                <option value="Locals Only">For Locals Only</option>
                                <option value="Both">For Both Xpat And Locals</option>
                                <option value="Muslim Only">Muslim Only</option>
                            </select>
                            <div id="div-details"></div>

                        </div>

                        <div class="form-group mb-3">
                            <label  class="form-label " for="cost-select">Select Status<span class="req_span">*</span></label>    

                            <select id="status-select" name="status" 
                               data-parsley-errors-container="#div-status" 
                               required data-parsley-required-message="Please select a status."
                               class="form-select select2t-none" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div id="div-status"></div>

                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label" for="payroll-allowance">Is Payroll Allowance<span class="req_span">*</span></label>    
                            <select id="payroll-allowance" name="is_payroll_allowance" 
                               data-parsley-errors-container="#div-payroll" 
                               required data-parsley-required-message="Please select if this is a payroll allowance."
                               class="form-select select2t-none" required>
                                <option value="">Select Option</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                            <div id="div-payroll"></div>
                        </div>

                        <div class="modal-footer justify-content-end">
                            <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                        </div>
                    </form>

            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
$(document).ready(function() 
{
    $('#addCostForm').parsley();
         CostTable();
    
    
    // Function to update validation rules based on user input
    function updateValidation() {
        var selectedCost = $('#cost-select').val();
        var costName = $('#new-cost-name').val().trim();

        var $costSelect = $('#cost-select');
        var $costNameInput = $('#new-cost-name');

        if (selectedCost && !costName) {
            // "Select Cost Name" is filled; make "Add Name of Cost" optional
            $(".New-cost-name").hide();
            $(".cost-name").show();

            $costNameInput.removeAttr('required')
                          .removeAttr('data-parsley-required-message')
                          .parsley().reset();
        } else if (!selectedCost && costName) {
                        $(".cost-name").hide();
            $(".New-cost-name").show();
            // "Add Name of Cost" is filled; make "Select Cost Name" optional
            $costSelect.removeAttr('required')
                       .removeAttr('data-parsley-required-message')
                       .parsley().reset();
        } else {
            // Neither or both fields are filled; make both required
            $costSelect.attr('required', 'required')
                       .attr('data-parsley-required-message', 'Please select a cost.')
                       .parsley().reset();
            $costNameInput.attr('required', 'required')
                          .attr('data-parsley-required-message', 'Please enter a cost name.')
                          .parsley().reset();
        }
    }

    // Event listeners for changes in the select and input fields
    $('#cost-select').on('change', function() {
        updateValidation();
    });

    $('#new-cost-name').on('input', function() {
        updateValidation();
    });

    // Initial validation setup
    updateValidation();

    
    toggleCostInput();



    // On change of the select box
    $('#cost-select').on('change', function() {
        toggleCostInput();
    });

        // On input in the textbox
    $('#new-cost-name').on('input', function() {
        toggleCostInput();
    });

    // Function to toggle between select box and textbox
    function toggleCostInput() {
        let selectValue = $('#cost-select').val();
        let textboxValue = $('#new-cost-name').val().trim();

        if (selectValue) {
            // If a division is selected, disable the textbox
            $('#new-cost-name').prop('disabled', true);
        } else {
            // If no division is selected, enable the textbox
            $('#new-cost-name').prop('disabled', false);
        }

        if (textboxValue) {
            // If the textbox has input, disable the select box
            $('#cost-select').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#cost-select').prop('disabled', false);
        }
    }


 
    $('#addCostForm').submit(function(e) {
        e.preventDefault();

        if ($(this).parsley().isValid()) 
        {
                $.ajax({
                    url: "{{ route('resort.budget.storecost') }}",
                    type: "POST",
                    data: $('#addCostForm').serialize(),
                    success: function(response) {
                        if(response.success == true) {
                            $('#add-costmodal').modal('hide');
                            $("#costs-table").DataTable().ajax.reload();

                            // Reset the form fields
                            $('#addCostForm')[0].reset();

                            // Optionally reset select2 dropdowns or other dynamic elements if needed
                            $('#addCostForm').find('select').val(null).trigger('change');

                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                });
        }
    });

    $(document).on("click", "#costs-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var costId = $(this).data('cost-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentParticulars = $row.find("td:nth-child(2)").text().trim();
        var currentAmount = $row.find("td:nth-child(3)").text().trim();
        var currentAmountUnit = $row.find("td:nth-child(4)").text().trim();
        var currentType = $row.find("td:nth-child(5)").text().trim();
        var currentFrequency = $row.find("td:nth-child(6)").text().trim();
        var currentDetails = $row.find("td:nth-child(7)").text().trim();
        var currentStatus = $row.find("td:nth-child(8)").text().trim().toLowerCase();
        var currentisPayrollAllowance = $row.find("td:nth-child(9)").text().trim();
        var editRowHtml = `
        <form class="parsley-validate-form" data-parsley-validate>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentName === "Recruitment Cost" ? "selected" : ""} value="Recruitment Cost">Recruitment Cost</option>
                        <option ${currentName === "Operational Cost" ? "selected" : ""} value="Operational Cost">Operational Cost</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" name="particulars" class="form-control" value="${currentParticulars}" />
                </div>
            </td>
            <td class="py-1">
                <input type="number" 
                    name="amount" 
                    class="form-control" 
                    value="${currentAmount}" 
                    required 
                    data-parsley-type="number" 
                    data-parsley-min="0" 
                    data-parsley-max="9999999.99" 
                    data-parsley-required-message="Amount is required" 
                    data-parsley-type-message="Amount must be a number" 
                    data-parsley-min-message="Amount must be non-negative" 
                    data-parsley-max-message="Amount must be less than 10 million" />            
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentAmountUnit === "USD" ? "selected" : ""} value="USD">USD</option>
                        <option ${currentAmountUnit === "MVR" ? "selected" : ""} value="MVR">MVR</option>
                        <option ${currentAmountUnit === "%" ? "selected" : ""} value="%">%</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
               <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentType === "Fixed" ? "selected" : ""} value="Fixed">Fixed</option>
                        <option ${currentType === "Variable" ? "selected" : ""} value="Variable">Variable</option>
                    </select>
                </div>
            </td>
             <td class="py-1">
                {{-- <input type="text" name="frequency" class="form-control" value="${currentFrequency}" /> --}}

                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentFrequency === "Daily" ? "selected" : ""} value="Daily">Daily</option>
                        <option ${currentFrequency === "Month" ? "selected" : ""} value="Month">Month</option>
                        <option ${currentFrequency === "Quarter" ? "selected" : ""} value="Quarter">Quarter</option>
                        <option ${currentFrequency === "Year" ? "selected" : ""} value="Year">Year</option>
                        <option ${currentFrequency === "One time cost" ? "selected" : ""} value="One time cost">One time cost</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
               <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentDetails === "Xpat Only" ? "selected" : ""} value="Xpat Only">For Xpat Only</option>
                        <option ${currentDetails === "Locals Only" ? "selected" : ""} value="Locals Only">For Locals Only</option>
                        <option ${currentDetails === "Both" ? "selected" : ""} value="Both">For Both</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentisPayrollAllowance === "0" ? "selected" : ""} value="0">No</option>
                        <option ${currentisPayrollAllowance === "1" ? "selected" : ""} value="1">Yes</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn btn-theme update-row-btn" data-cost-id="${costId}">Submit</a>
            </td>
        </form>
        `;

        $row.html(editRowHtml);
    });

    // Handle click on update button
    $(document).on("click", "#costs-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

       
        // var $form = $(this).closest("form.parsley-validate-form");

        // if (!$form.length) {
        //     console.warn("Form not found.");
        //     return;
        // }

        // if (typeof $form.parsley !== "function") {
        //     console.error("Parsley is not loaded.");
        //     return;
        // }

        // $form.parsley().validate();

        // if (!$form.parsley().isValid()) {
        //     return;
        // }

        var $row = $(this).closest("tr");

        // Get updated values
        var costId = $(this).data('cost-id');
        var updatedName = $row.find("select").eq(0).val();
        var updatedParticulars = $row.find("input").eq(0).val();
        var updatedAmount = $row.find("input").eq(1).val();
        var updatedAmountUnit = $row.find("select").eq(1).val();
        var updatedType = $row.find("select").eq(2).val();
        // var updatedFrequency = $row.find("input").eq(3).val();
        var updatedFrequency = $row.find("select").eq(3).val();
        var updatedDetails = $row.find("select").eq(4).val();
        var updatedStatus = $row.find("select").eq(5).val();
        var updatedisPayrollAllowance = $row.find("select").eq(6).val();

        // Perform the AJAX update request here
        $.ajax({
            url: "{{ route('resort.budget.inlinecostupdate', '') }}/" + costId,
            type: "PUT",
            data: {
                cost_title: updatedName,
                particulars: updatedParticulars,
                amount: updatedAmount,
                amount_unit: updatedAmountUnit,
                cost_type: updatedType,
                frequency: updatedFrequency,
                details: updatedDetails,
                status: updatedStatus,
                is_payroll_allowance: updatedisPayrollAllowance
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key
                    // Determine the status class and label
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);

                    var isPayrollAllowanceClass = updatedisPayrollAllowance === '1' ? 'text-success' : 'text-danger';
                    var isPayrollAllowanceLabel = updatedisPayrollAllowance === '1' ? 'Yes' : 'No';

                    // Update the row with new values
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedParticulars}</td>
                        <td class="text-nowrap">${updatedAmount}</td>
                        <td class="text-nowrap">${updatedAmountUnit}</td>
                        <td class="text-nowrap">${updatedType}</td>
                        <td class="text-nowrap">${updatedFrequency}</td>
                        <td class="text-nowrap">${updatedDetails}</td>
                        <td class="text-nowrap ${statusClass}">${statusLabel}</td>
                        <td class="text-nowrap ${isPayrollAllowanceClass}">${isPayrollAllowanceLabel}</td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cost-id="${costId}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-cost-id="${costId}">
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
            error: function(xhr, status, error) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Loop through the errors and display each one
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        $.each(messages, function(index, message) {
                            toastr.error(message, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        });
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Fallback for other types of errors
                    toastr.error(xhr.responseJSON.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("Something went wrong.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }

        });

    });

    // Confirmation dialog before delete
    $(document).on('click', '#costs-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var costId = $(this).data('cost-id');
        Swal.fire({
            title: 'Are you sure?',
            text: ' Do you really want to delete these records? this process cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('resort.budget.destroycost', '') }}/" + costId,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        $('#costs-table').DataTable().ajax.reload();
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

    let costSearchTimeout;
    $(document).on('input', '.search', function () {
        clearTimeout(costSearchTimeout);
        costSearchTimeout = setTimeout(() => {
            CostTable();
        }, 300); // Wait 300ms after the user stops typing
    });
});
    function CostTable()
    {
        if ($.fn.DataTable.isDataTable('#costs-table')) 
        {
            $('#costs-table').DataTable().destroy();
        }
        var costTable = $('#costs-table').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 15,
            processing: true,
            serverSide: true,
            order:[[10, 'asc']],
            ajax: {
                        url: '{{ route("resort.budget.costlist") }}',
                        type: 'GET',
                        data: function(d) {
                            d.search = $(".search").val();
                        }
                    },

            columns: [
                { data: 'cost_title', name: 'cost_title', className: 'text-nowrap' },
                { data: 'particulars', name: 'particulars', className: 'text-nowrap' },
                { data: 'amount', name: 'amount', className: 'text-nowrap' },
                { data: 'amount_unit', name: 'amount_unit', className: 'text-nowrap' },
                { data: 'cost_type', name: 'cost_type', className: 'text-nowrap' },
                { data: 'frequency', name: 'frequency', className: 'text-nowrap' },
                { data: 'details', name: 'details', className: 'text-nowrap' },
                { data: 'status', name: 'status', className: 'text-nowrap' },
                { data: 'is_payroll_allowance', name: 'is_payroll_allowance', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });

        $('#clearFilter').on('click', function() {
            $('.search').val('');
           
        CostTable();
        });
    }


</script>
@endsection
