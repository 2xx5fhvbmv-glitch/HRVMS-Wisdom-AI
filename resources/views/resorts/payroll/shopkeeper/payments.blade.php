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
                    <a href="{{ route('shopkeepers.index') }}" class="btn btn-themeSkyblue btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to Shopkeepers</a>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card dashboard-boxcard timeAttend-boxcard mb-30">
                    <p class="fw-600 mb-0">Total Payable Amount <span class="text-muted small">(by date range)</span></p>
                    <strong id="total-payable-amount">$0.00</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="dateRangeAb datepicker" id="datapicker">
                            <div>
                                <input type="text" class="form-control dateRangeAb datepicker" name="hiddenInput" id="hiddenInput">
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" id="payment-download-btn" class="btn btn-themeSkyblue btn-sm">
                            <i class="fa-solid fa-download me-1"></i> Download
                        </button>
                    </div>
                </div>
            </div>
            <table id="payment-table" class="table w-100">
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
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .dateRangeAb{position: relative;}
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
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
            opens: 'right',
            parentEl: '#datapicker',
            locale: { format: "DD-MM-YYYY" }
        });
        $("#hiddenInput").on('apply.daterangepicker', function () {
            PaymentList();
        });
        PaymentList();
        $(document).on('keyup', '.search', function() {
            PaymentList();
        });
        $('#payment-download-btn').on('click', function() {
            var dateRange = $("#hiddenInput").val();
            var dates = dateRange ? dateRange.split(' - ') : [];
            var startDate = dates[0] ? moment(dates[0], "DD-MM-YYYY").format("YYYY-MM-DD") : '';
            var endDate = dates[1] ? moment(dates[1], "DD-MM-YYYY").format("YYYY-MM-DD") : '';
            var searchTerm = $('.search').val() || '';
            var url = "{{ route('resort.shopkeeper.payments.export', ['id' => $shopkeeper->id]) }}?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate) + "&search_term=" + encodeURIComponent(searchTerm);
            window.location.href = url;
        });
    });

    function PaymentList() {
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange ? dateRange.split(' - ') : [];
        var startDate = dates[0] ? moment(dates[0], "DD-MM-YYYY").format("YYYY-MM-DD") : '';
        var endDate = dates[1] ? moment(dates[1], "DD-MM-YYYY").format("YYYY-MM-DD") : '';

        if ($.fn.DataTable.isDataTable('#payment-table')) {
            $('#payment-table').DataTable().destroy();
        }

        $('#payment-table').DataTable({
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 15,
            processing: true,
            serverSide: true,
            order: [[8, 'desc']],
            ajax: {
                url: "{{ route('resort.shopkeeper.payments.list', ['id' => $shopkeeper->id]) }}",
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('.search').val();
                    d.start_date = startDate;
                    d.end_date = endDate;
                },
                dataSrc: function (json) {
                    var total = (json.total_amount != null) ? parseFloat(json.total_amount) : json.data.reduce(function (sum, p) { return sum + parseFloat(p.price || 0); }, 0);
                    $('#total-payable-amount').text('$' + total.toFixed(2));
                    return json.data;
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
                { data: 'status', name: 'status', orderable: false, searchable: false },
            ]
        });
    }
</script>
@endsection
