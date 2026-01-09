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
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="payment-history" class="table w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Emp ID</th>
                            <th>Name </th>
                            <th>Purchase Date </th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            
                    </tbody>
                </table>
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
            order:[[8, 'desc']], 
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
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'created_at', visible: false, searchable: false },
            ]
        });
    }
</script>
@endsection