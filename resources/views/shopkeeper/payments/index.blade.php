@extends('shopkeeper.layouts.app')
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
                            <span>&nbsp;</span>
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
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="dateRangeAb datepicker"  id="datapicker">
                                <div>
                                    <!-- Hidden input field to attach the calendar to -->
                                    <input type="text" class="form-control dateRangeAb datepicker" name="hiddenInput" id="hiddenInput">
                                </div>
                                <p id="startDate" class="d-none">Start Date:</p>
                                <p id="endDate" class="d-none">End Date:</p>
                            </div>                        
                        </div>
                        <div class="col-auto ms-md-auto">
                            <button type="button" id="payment-download-btn" class="btn btn-themeSkyblue btn-sm">
                                <i class="fa-solid fa-download me-1"></i> Download
                            </button>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="payment-history" class="table w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Emp ID</th>
                            <th>Name</th>
                            <th>Purchase Date</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>QR</th>
                        </tr>
                    </thead>
                    <tbody>
            
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="payment-qr-modal" tabindex="-1" aria-labelledby="paymentQrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentQrModalLabel">Payment QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted small mb-2">Scan with mobile app for payment details</p>
                    <img id="payment-qr-modal-img" src="" alt="QR Code" style="max-width: 280px; width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .dateRangeAb{position: relative;}
    .dateRangeAb .daterangepicker {
        position: absolute !important;
        background-color: #fff;
        width: 100%;
        /* min-width: 350px; */
    }
    .dateRangeAb .form-control {
        background-image: url('{{ URL::asset("resorts_assets/images/calendar.svg") }}');
        background-position: right 10px center;
        background-repeat: no-repeat;
    }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: moment().startOf('month'),  // First day of the current month
            endDate: moment().endOf('month'),
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: {
                format: "DD-MM-YYYY", // Ensure the format matches your date parsing logic
            }
        });

        $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
            $("#startDate").text("Start Date: " + picker.startDate.format("DD-MM-YYYY"));
            $("#endDate").text("End Date: " + picker.endDate.format("DD-MM-YYYY"));
            
            PaymentHistory();
        });
        PaymentHistory();

        $(document).on('keyup', '.search', function() {
            PaymentHistory();
        });

        $(document).on('click', '.payment-qr-icon', function() {
            var paymentId = $(this).data('payment-id');
            var url = "{{ route('shopkeeper.payment.qr-image', '') }}/" + paymentId;
            $('#payment-qr-modal-img').attr('src', url);
            var qrModal = new bootstrap.Modal(document.getElementById('payment-qr-modal'));
            qrModal.show();
        });

        $('#payment-download-btn').on('click', function(e) {
            e.preventDefault();
            var dateRange = $('#hiddenInput').val();
            var dates = dateRange.split(' - ');
            var startDate = moment(dates[0], 'DD-MM-YYYY').format('YYYY-MM-DD');
            var endDate = moment(dates[1], 'DD-MM-YYYY').format('YYYY-MM-DD');
            var searchTerm = $('.search').val() || '';
            var url = "{{ route('dashboard.payment.download') }}?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate);
            if (searchTerm) url += "&search_term=" + encodeURIComponent(searchTerm);
            window.location.href = url;
        });
    });
    function PaymentHistory()
    {
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY");
        var endDate = moment(dates[1], "DD-MM-YYYY");

        // Generate all dates within the range
       
        startDate = startDate.format("YYYY-MM-DD");
        endDate = endDate.format("YYYY-MM-DD");

        if ($.fn.DataTable.isDataTable('#payment-history'))
        {
            $('#payment-history').DataTable().destroy();
        }

        var productTable = $('#payment-history').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order:[[10, 'desc']], 
            ajax: {
                url: "{{ route('shopkeeper.payment.list') }}",
                type: 'GET',
                data: function(d) {
                    d.searchTerm = $('.search').val();
                    d.start_date = startDate;
                    d.end_date = endDate;
                }
            },
            columns: [
                { data: 'order_id', name: 'order_id', className: 'text-nowrap' },
                { data: 'Emp_id', name: 'Emp_id', className: 'text-nowrap' },
                { data: 'name', name: 'name', className: 'text-nowrap' },
                { data: 'purchased_date', name: 'purchased_date', className: 'text-nowrap' },
                { data: 'product', name: 'product', className: 'text-nowrap' },
                { data: 'quantity', name: 'quantity', className: 'text-nowrap' },
                { data: 'price', name: 'price', className: 'text-nowrap' },
                { data: 'currency_type', name: 'currency_type', className: 'text-nowrap' },
                { data: 'status', name: 'status', orderable: false, searchable: false, render: function(data, type, row) { if (type === 'display' && data) { var $wrap = $('<div>').html(data); return $wrap[0]; } return data || '—'; } },
                { data: 'qr_code', name: 'qr_code', orderable: false, searchable: false, className: 'text-center' },
                { data: 'created_at', visible: false, searchable: false },
            ]
        });
    }
</script>
@endsection