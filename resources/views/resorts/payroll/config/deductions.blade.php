@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                <div class="col-auto">
                    <a href="{{ route('payroll.configration') }}" class="btn btn-themeSkyblue btn-sm">Back to Configuration</a>
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-5 col-sm-4 col-6">
                                    <div class="input-group">
                                        <input type="search" class="form-control" id="search-input" placeholder="Search" />
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
                                    <h3>All Deductions</h3>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table w-100" id="deductions-table">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Deduction Name</th>
                                        <th class="text-nowrap">Deduction Type</th>
                                        <th class="text-nowrap">Currency</th>
                                        <th class="text-nowrap">Max Limit</th>
                                        <th class="text-nowrap">Limit Type</th>
                                        <th class="text-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="deductions-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDeductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Deduction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDeductionForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_deduction_id">
                    <div class="mb-3">
                        <label class="form-label">Deduction Name</label>
                        <input type="text" class="form-control" id="edit_deduction_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deduction Type</label>
                        <input type="text" class="form-control" id="edit_deduction_type" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-select" id="edit_currency" required>
                            <option value="Rufiyaa">Rufiyaa</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-5">
                            <label class="form-label">Limit Type</label>
                            <select class="form-select" id="edit_maximum_limit_type" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <label class="form-label">Maximum Limit</label>
                            <input type="number" step="0.01" class="form-control" id="edit_maximum_limit" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-themeBlue">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function() {
        loadDeductions();

        $('#search-input').on('keyup', function() {
            loadDeductions();
        });

        $('#clearFilter').on('click', function() {
            $('#search-input').val('');
            loadDeductions();
        });
    });

    function loadDeductions() {
        var search = $('#search-input').val() || '';
        $.ajax({
            url: "{{ route('deductions.list') }}",
            type: "GET",
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '';
                    var filtered = response.data;
                    if (search) {
                        search = search.toLowerCase();
                        filtered = filtered.filter(function(d) {
                            return d.deduction_name.toLowerCase().includes(search) ||
                                   d.deduction_type.toLowerCase().includes(search) ||
                                   d.currency.toLowerCase().includes(search);
                        });
                    }
                    if (filtered.length === 0) {
                        html = '<tr><td colspan="6" class="text-center">No matching deductions found.</td></tr>';
                    } else {
                        filtered.forEach(function(d) {
                            var limitDisplay = d.maximum_limit || '-';
                            var limitTypeDisplay = d.maximum_limit_type === 'fixed' ? 'Fixed' : 'Percentage';
                            if (d.maximum_limit) {
                                if (d.maximum_limit_type === 'percentage') {
                                    limitDisplay += '%';
                                } else {
                                    limitDisplay = (d.currency === 'USD' ? '$' : '') + limitDisplay;
                                }
                            }
                            html += '<tr>' +
                                '<td>' + d.deduction_name + '</td>' +
                                '<td>' + d.deduction_type + '</td>' +
                                '<td>' + d.currency + '</td>' +
                                '<td>' + limitDisplay + '</td>' +
                                '<td>' + limitTypeDisplay + '</td>' +
                                '<td>' +
                                    '<div class="d-flex align-items-center">' +
                                        '<a href="#" class="btn-lg-icon icon-bg-green me-1 edit-deduction-btn" ' +
                                            'data-id="' + d.id + '" ' +
                                            'data-name="' + (d.deduction_name || '').replace(/"/g, '&quot;') + '" ' +
                                            'data-type="' + (d.deduction_type || '').replace(/"/g, '&quot;') + '" ' +
                                            'data-currency="' + d.currency + '" ' +
                                            'data-limit="' + (d.maximum_limit || '') + '" ' +
                                            'data-limit-type="' + (d.maximum_limit_type || 'percentage') + '">' +
                                            '<img src="{{ asset("resorts_assets/images/edit.svg") }}" alt="" class="img-fluid" />' +
                                        '</a>' +
                                        '<a href="#" class="btn-lg-icon icon-bg-red delete-deduction-btn" data-id="' + d.id + '">' +
                                            '<img src="{{ asset("resorts_assets/images/trash-red.svg") }}" alt="" class="img-fluid" />' +
                                        '</a>' +
                                    '</div>' +
                                '</td>' +
                                '</tr>';
                        });
                    }
                    $('#deductions-body').html(html);
                } else {
                    $('#deductions-body').html('<tr><td colspan="6" class="text-center">No deductions found.</td></tr>');
                }
            },
            error: function() {
                $('#deductions-body').html('<tr><td colspan="6" class="text-center text-danger">Failed to load deductions.</td></tr>');
            }
        });
    }

    // Edit deduction
    $(document).on('click', '.edit-deduction-btn', function(e) {
        e.preventDefault();
        $('#edit_deduction_id').val($(this).data('id'));
        $('#edit_deduction_name').val($(this).data('name'));
        $('#edit_deduction_type').val($(this).data('type'));
        $('#edit_currency').val($(this).data('currency'));
        $('#edit_maximum_limit').val($(this).data('limit'));
        $('#edit_maximum_limit_type').val($(this).data('limit-type'));
        $('#editDeductionModal').modal('show');
    });

    // Update deduction
    $('#editDeductionForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#edit_deduction_id').val();
        var $btn = $(this).find('button[type=submit]');
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

        $.ajax({
            url: "{{ url('resort/payroll/deductions') }}/" + id,
            type: "PUT",
            data: {
                deduction_name: $('#edit_deduction_name').val(),
                deduction_type: $('#edit_deduction_type').val(),
                currency: $('#edit_currency').val(),
                maximum_limit: $('#edit_maximum_limit').val(),
                maximum_limit_type: $('#edit_maximum_limit_type').val(),
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    $('#editDeductionModal').modal('hide');
                    loadDeductions();
                } else {
                    toastr.error(response.message || 'Update failed.', "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                $btn.html(originalText).prop('disabled', false);
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errs = '';
                    $.each(xhr.responseJSON.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                }
            }
        });
    });

    // Delete deduction
    $(document).on('click', '.delete-deduction-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('resort/payroll/deductions') }}/" + id,
                    type: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                            loadDeductions();
                        } else {
                            toastr.error(response.message || 'Delete failed.', "Error", { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function() {
                        toastr.error("Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });
</script>
@endsection
