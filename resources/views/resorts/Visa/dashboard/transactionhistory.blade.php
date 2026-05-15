@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

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
                        <span>Visa Management</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('visa.hr.dashboard') }}" class="btn btn-sm btn-theme">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <h3>{{ $page_title }}</h3>
                                </div>
                            </div>
                        </div>

                        <table id="TransactionHistoryList" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Date</th>
                                    <th class="text-nowrap">From Wallet</th>
                                    <th class="text-nowrap">To Wallet</th>
                                    <th class="text-nowrap">Amount ({{ Common::GetResortCurrencySymbol() }})</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#TransactionHistoryList')) {
        $('#TransactionHistoryList').DataTable().clear().destroy();
    }

    $('#TransactionHistoryList').DataTable({
        searching: true,
        bLengthChange: true,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 25,
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: '{{ route("resort.visa.TransectionHistory") }}',
            type: 'GET'
        },
        columns: [
            { data: 'Date', name: 'Date', className: 'text-nowrap' },
            { data: 'FromWallet', name: 'FromWallet', className: 'text-nowrap' },
            { data: 'ToWallet', name: 'ToWallet', className: 'text-nowrap' },
            { data: 'Amount', name: 'Amount', className: 'text-nowrap' },
            { data: 'created_at', visible: false, searchable: false }
        ]
    });
});
</script>
@endsection
