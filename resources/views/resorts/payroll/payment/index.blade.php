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
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="table-payment" class="table data-Table table-payment  w-100">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Shopkeeper Name </th>
                            <th>Purchase Date </th>
                            <th>Product Quantity </th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Your data rows will be inserted here by DataTables -->
                    </tbody>
                   
                </table>
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
    loadPaymentTable();

    $('#searchInput').on('keyup change', function () {
        loadPaymentTable();
    });

    $(document).on('click', '.consent-payment', function() {
        var paymentID = $(this).data('id');
       
       $.ajax({
            url: "{{ route('payroll.payment.consent-confirm') }}",
            type: "POST",
            data: {
                paymentID: paymentID,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                toastr.error("An error occurred while resending consent.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });
});

function loadPaymentTable() {
    if ($.fn.DataTable.isDataTable('#table-payment')) {
        $('#table-payment').DataTable().destroy();
    }
    var employee_id = "{{ $employee_id ?? 0 }}";
    $('#table-payment').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 10,
        processing: true,
        serverSide: true,
        order:[[7,'desc']],
        "ajax": {
            url: "{{ route('payroll.payment.consent', ':employee_id') }}".replace(':employee_id', employee_id),
            data: function (d) {
                d.searchTerm = $('#searchInput').val();
            },
            type: "GET",
        },
        "columns": [
            { data: 'order_id' },
            { data: 'shopkeeper_name' },
            { data: 'purchased_date' },
            { data: 'quantity' },
            { data: 'price' },
            { data: 'status' },
            { data: 'action', orderable: false, searchable: false },
            { data: 'created_at', orderable: false, searchable: false }
        ],
    });
}
</script>
@endsection