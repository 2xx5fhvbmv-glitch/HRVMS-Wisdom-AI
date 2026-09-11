@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #nonpermanentbudgetcost-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #nonpermanentbudgetcost-hero { padding-bottom: 0; }
    }
    #add-nonpermanentcostmodal .modal-body { max-height: 70vh; overflow-y: auto; overflow-x: hidden; }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="nonpermanentbudgetcost-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.budget.config') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Configuration
                    </a>
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
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
                                    <button class="btn wfp-btn-secondary btn-sm" id="clearFilter">Clear Filter</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Costs — Casual &amp; Intern</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="#" class="btn btn-sm wfp-btn-primary " data-bs-toggle="modal"
                                            data-bs-target="#add-nonpermanentcostmodal">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="nonpermanent-costs-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Particulars</th>
                                    <th class="text-nowrap">Amount</th>
                                    <th class="text-nowrap">Amount Unit</th>
                                    <th class="text-nowrap">Type</th>
                                    <th class="text-nowrap">Frequency</th>
                                    <th class="text-nowrap">Applies To</th>
                                    <th class="text-nowrap">Details</th>
                                    <th class="text-nowrap">Status</th>
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

<!-- Add Cost Modal -->
<div class="modal fade" id="add-nonpermanentcostmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Add Cost</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <form id="addNonpermanentCostForm" data-parsley-validate>
                        @csrf
                        <div class="form-group mb-20">
                            <label class="form-label" for="cost_title">Cost Name <span class="req_span">*</span></label>
                            <input type="text" id="cost_title" name="cost_title" class="form-control"
                                placeholder="e.g. Agency Placement Fee"
                                required
                                data-parsley-required-message="Please enter a cost name."
                                data-parsley-pattern="^[a-zA-Z0-9\s\-_/\.]+$"
                                data-parsley-pattern-message="Only letters, numbers, spaces, hyphens (-), underscores (_), periods (.), and slashes (/) are allowed.">
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label" for="particulars">Particulars</label>
                            <input type="text" id="particulars" name="particulars" class="form-control"
                                placeholder="Add Particulars"
                                data-parsley-pattern="^[a-zA-Z0-9\s\-_/\.]*$"
                                data-parsley-pattern-message="Only letters, numbers, spaces, hyphens (-), underscores (_), periods (.), and slashes (/) are allowed.">
                        </div>

                        <div class="form-group mb-20 row">
                            <div class="col-md-6">
                                <label class="form-label" for="amount">Add Amount <span class="req_span">*</span></label>
                                <input type="number" min="0" max="9999999.99" step="any" id="amount" name="amount" class="form-control" placeholder="Add Amount" required
                                    data-parsley-required-message="Please enter a valid amount." data-parsley-min="0" data-parsley-min-message="Amount cannot be negative."
                                    data-parsley-type="number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="amount_unit">Add Amount Unit <span class="req_span">*</span></label>
                                <select id="amount_unit" name="amount_unit" class="form-select select2t-none"
                                    required data-parsley-required-message="Please select an amount unit."
                                    data-parsley-errors-container="#div-nonperm-amount-unit">
                                    <option value="">Select Amount Unit</option>
                                    <option value="USD">USD</option>
                                    <option value="MVR">MVR</option>
                                    <option value="%">%</option>
                                </select>
                                <div id="div-nonperm-amount-unit"></div>
                            </div>
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label" for="cost_type">Cost Type</label>
                            <select id="cost_type" name="cost_type" class="form-select select2t-none">
                                <option value="">Select Type</option>
                                <option value="Fixed">Fixed</option>
                                <option value="Variable">Variable</option>
                            </select>
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label" for="frequency">Add Frequency <span class="req_span">*</span></label>
                            <select id="frequency" name="frequency"
                                data-parsley-errors-container="#div-nonperm-frequency"
                                required data-parsley-required-message="Please select a frequency."
                                class="form-select select2t-none" required>
                                <option value="">Select frequency</option>
                                <option value="Daily">Daily</option>
                                <option value="Month">Month</option>
                                <option value="Quarter">Quarter</option>
                                <option value="Year">Year</option>
                                <option value="One time cost">One time cost</option>
                            </select>
                            <div id="div-nonperm-frequency"></div>
                        </div>

                        {{-- Casual and Intern share this screen but don't always
                             share every line item (e.g. a daily agency rate is
                             Casual-only; a stipend is Intern-only). --}}
                        <div class="form-group mb-20">
                            <label class="form-label" for="applies_to">Applies To <span class="req_span">*</span></label>
                            <select id="applies_to" name="applies_to"
                                data-parsley-errors-container="#div-applies-to"
                                required data-parsley-required-message="Please select who this cost applies to."
                                class="form-select select2t-none" required>
                                <option value="">Select</option>
                                <option value="Both">Both Casual &amp; Intern</option>
                                <option value="Casual">Casual only</option>
                                <option value="Intern">Intern only</option>
                            </select>
                            <div id="div-applies-to"></div>
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label" for="details">Select Details</label>
                            <select id="details" name="details" class="form-select select2t-none">
                                <option value="">Select Details</option>
                                <option value="Xpat Only">For Xpat Only</option>
                                <option value="Locals Only">For Locals Only</option>
                                <option value="Both">For Both Xpat And Locals</option>
                                <option value="Muslim Only">Muslim Only</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="status">Select Status <span class="req_span">*</span></label>
                            <select id="status" name="status"
                                data-parsley-errors-container="#div-nonperm-status"
                                required data-parsley-required-message="Please select a status."
                                class="form-select select2t-none" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div id="div-nonperm-status"></div>
                        </div>

                        <div class="modal-footer justify-content-end">
                            <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm wfp-btn-primary">Submit</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function()
{
    $('#addNonpermanentCostForm').parsley();
    NonpermanentCostTable();

    $('#addNonpermanentCostForm').submit(function(e) {
        e.preventDefault();

        if ($(this).parsley().isValid())
        {
            $.ajax({
                url: "{{ route('resort.budget.nonpermanent.storecost') }}",
                type: "POST",
                data: $('#addNonpermanentCostForm').serialize(),
                success: function(response) {
                    if(response.success == true) {
                        $('#add-nonpermanentcostmodal').modal('hide');
                        $("#nonpermanent-costs-table").DataTable().ajax.reload();
                        $('#addNonpermanentCostForm')[0].reset();
                        $('#addNonpermanentCostForm').find('select').val(null).trigger('change');

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

    $(document).on("click", "#nonpermanent-costs-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var costId = $(this).data('cost-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentParticulars = $row.find("td:nth-child(2)").text().trim();
        var currentAmount = $row.find("td:nth-child(3)").text().trim();
        var currentAmountUnit = $row.find("td:nth-child(4)").text().trim();
        var currentType = $row.find("td:nth-child(5)").text().trim();
        var currentFrequency = $row.find("td:nth-child(6)").text().trim();
        var currentAppliesTo = $row.find("td:nth-child(7)").text().trim();
        var currentDetails = $row.find("td:nth-child(8)").text().trim();
        var currentStatus = $row.find("td:nth-child(9)").text().trim().toLowerCase();

        var editRowHtml = `
        <form class="parsley-validate-form" data-parsley-validate>
            <td class="py-1"><input type="text" class="form-control" value="${currentName}" /></td>
            <td class="py-1"><input type="text" class="form-control" value="${currentParticulars}" /></td>
            <td class="py-1">
                <input type="number" class="form-control" value="${currentAmount}" required
                    data-parsley-type="number" data-parsley-min="0" data-parsley-max="9999999.99"
                    data-parsley-required-message="Amount is required"
                    data-parsley-min-message="Amount must be non-negative" />
            </td>
            <td class="py-1">
                <select class="form-select select2t-none">
                    <option ${currentAmountUnit === "USD" ? "selected" : ""} value="USD">USD</option>
                    <option ${currentAmountUnit === "MVR" ? "selected" : ""} value="MVR">MVR</option>
                    <option ${currentAmountUnit === "%" ? "selected" : ""} value="%">%</option>
                </select>
            </td>
            <td class="py-1">
                <select class="form-select select2t-none">
                    <option ${currentType === "Fixed" ? "selected" : ""} value="Fixed">Fixed</option>
                    <option ${currentType === "Variable" ? "selected" : ""} value="Variable">Variable</option>
                </select>
            </td>
            <td class="py-1">
                <select class="form-select select2t-none">
                    <option ${currentFrequency === "Daily" ? "selected" : ""} value="Daily">Daily</option>
                    <option ${currentFrequency === "Month" ? "selected" : ""} value="Month">Month</option>
                    <option ${currentFrequency === "Quarter" ? "selected" : ""} value="Quarter">Quarter</option>
                    <option ${currentFrequency === "Year" ? "selected" : ""} value="Year">Year</option>
                    <option ${currentFrequency === "One time cost" ? "selected" : ""} value="One time cost">One time cost</option>
                </select>
            </td>
            <td class="py-1">
                <select class="form-select select2t-none">
                    <option ${currentAppliesTo === "Both" ? "selected" : ""} value="Both">Both</option>
                    <option ${currentAppliesTo === "Casual" ? "selected" : ""} value="Casual">Casual</option>
                    <option ${currentAppliesTo === "Intern" ? "selected" : ""} value="Intern">Intern</option>
                </select>
            </td>
            <td class="py-1">
                <select class="form-select select2t-none">
                    <option ${currentDetails === "Xpat Only" ? "selected" : ""} value="Xpat Only">For Xpat Only</option>
                    <option ${currentDetails === "Locals Only" ? "selected" : ""} value="Locals Only">For Locals Only</option>
                    <option ${currentDetails === "Both" ? "selected" : ""} value="Both">For Both</option>
                    <option ${currentDetails === "Muslim Only" ? "selected" : ""} value="Muslim Only">Muslim Only</option>
                </select>
            </td>
            <td class="py-1">
                <select class="form-select select2t-none">
                    <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                    <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                </select>
            </td>
            <td class="py-1">
                <a href="#" class="btn wfp-btn-primary update-row-btn" data-cost-id="${costId}">Submit</a>
            </td>
        </form>
        `;

        $row.html(editRowHtml);
    });

    $(document).on("click", "#nonpermanent-costs-table .update-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var costId = $(this).data('cost-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedParticulars = $row.find("input").eq(1).val();
        var updatedAmount = $row.find("input").eq(2).val();
        var updatedAmountUnit = $row.find("select").eq(0).val();
        var updatedType = $row.find("select").eq(1).val();
        var updatedFrequency = $row.find("select").eq(2).val();
        var updatedAppliesTo = $row.find("select").eq(3).val();
        var updatedDetails = $row.find("select").eq(4).val();
        var updatedStatus = $row.find("select").eq(5).val();

        $.ajax({
            url: "{{ route('resort.budget.nonpermanent.inlinecostupdate', '') }}/" + costId,
            type: "PUT",
            data: {
                cost_title: updatedName,
                particulars: updatedParticulars,
                amount: updatedAmount,
                amount_unit: updatedAmountUnit,
                cost_type: updatedType,
                frequency: updatedFrequency,
                applies_to: updatedAppliesTo,
                details: updatedDetails,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) {
                    $("#nonpermanent-costs-table").DataTable().ajax.reload();
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
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        $.each(messages, function(index, message) {
                            toastr.error(message, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        });
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
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

    $(document).on('click', '#nonpermanent-costs-table .delete-row-btn', function (e) {
        e.preventDefault();
        var costId = $(this).data('cost-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Are you sure?',
            text: ' Do you really want to delete these records? this process cannot be undone.',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('resort.budget.nonpermanent.destroycost', '') }}/" + costId,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#nonpermanent-costs-table').DataTable().ajax.reload();
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

    let nonpermCostSearchTimeout;
    $(document).on('input', '.search', function () {
        clearTimeout(nonpermCostSearchTimeout);
        nonpermCostSearchTimeout = setTimeout(() => {
            NonpermanentCostTable();
        }, 300);
    });
});

function NonpermanentCostTable()
{
    if ($.fn.DataTable.isDataTable('#nonpermanent-costs-table'))
    {
        $('#nonpermanent-costs-table').DataTable().destroy();
    }
    $('#nonpermanent-costs-table').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 15,
        processing: true,
        serverSide: true,
        order: [[10, 'asc']],
        ajax: {
            url: '{{ route("resort.budget.nonpermanent.costlist") }}',
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
            { data: 'applies_to', name: 'applies_to', className: 'text-nowrap' },
            { data: 'details', name: 'details', className: 'text-nowrap' },
            { data: 'status', name: 'status', className: 'text-nowrap' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'created_at', visible: false, searchable: false },
        ]
    });

    $('#clearFilter').on('click', function() {
        $('.search').val('');
        NonpermanentCostTable();
    });
}
</script>
@endsection
