@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>VISA MANAGEMENT</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <div class="row g-3 align-items-end justify-content-between">
                    <div class="col-auto">
                        <span id="bulkSelectedInfo">0 selected</span>
                        <strong class="ms-2 bulkSelectedTotal">MVR 0.00</strong>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-end gap-2">
                            <div>
                                <label class="form-label mb-1">Receipt Number <span class="red-mark">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="bulkReceipt" placeholder="Enter receipt number">
                            </div>
                            <button type="button" class="btn btn-themeBlue btn-sm" id="bulkPayBtn">Mark Selected as Paid</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-md-4 mb-3">
                <table id="bulk-renewal-table" class="table w-100">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="bulkSelectAll"></th>
                            <th>Employee</th>
                            <th>Fee Type</th>
                            <th>Month</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    var table = $('#bulk-renewal-table').DataTable({
        searching: true,
        bLengthChange: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 25,
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: {
            url: "{{ route('resort.visa.BulkRenewal') }}",
            type: 'GET',
        },
        columns: [
            { data: 'CheckBox', name: 'CheckBox', orderable: false, searchable: false, className: 'text-nowrap' },
            { data: 'EmployeeName', name: 'EmployeeName', className: 'text-nowrap' },
            { data: 'FeeType', name: 'FeeType', className: 'text-nowrap' },
            { data: 'Month', name: 'Month', className: 'text-nowrap' },
            { data: 'DueDate', name: 'DueDate', className: 'text-nowrap' },
            { data: 'Amount', name: 'Amount', className: 'text-nowrap' },
        ],
        drawCallback: function () {
            $('#bulkSelectAll').prop('checked', false);
            updateBulkInfo();
        }
    });

    // Select all on the current page.
    $(document).on('change', '#bulkSelectAll', function () {
        $('#bulk-renewal-table tbody .BulkCheck').prop('checked', $(this).is(':checked'));
        updateBulkInfo();
    });

    $(document).on('change', '.BulkCheck', function () {
        updateBulkInfo();
    });

    function updateBulkInfo() {
        var count = 0, total = 0;
        $('#bulk-renewal-table tbody .BulkCheck:checked').each(function () {
            count++;
            total += parseFloat($(this).data('amt')) || 0;
        });
        $('#bulkSelectedInfo').text(count + ' selected');
        $('.bulkSelectedTotal').text('MVR ' + total.toFixed(2));
    }

    $('#bulkPayBtn').on('click', function () {
        var items = $('#bulk-renewal-table tbody .BulkCheck:checked').map(function () {
            return $(this).val();
        }).get();
        var receipt = $.trim($('#bulkReceipt').val());

        if (items.length === 0) {
            toastr.error('Please select at least one fee to mark as paid.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        if (receipt === '') {
            toastr.error('Receipt number is required.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: "{{ route('resort.visa.BulkRenewalPay') }}",
            type: 'POST',
            data: { items: items, receipt: receipt, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.msg, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#bulkReceipt').val('');
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.msg || 'Failed.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'An error occurred.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection
