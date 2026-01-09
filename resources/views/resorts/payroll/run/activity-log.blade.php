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
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                   
                </div>
            </div>

            <div class="card card-payrollMain">
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
                <table id="table-activity-log" class="table table-activity-log w-100">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Field</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Update By</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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
        fetchActivityLogs();

        $('#searchInput').on('keyup change', function () {
            fetchActivityLogs();
        });
    });

    function fetchActivityLogs() {
        if ($.fn.DataTable.isDataTable("#table-activity-log")) {
            $("#table-activity-log").DataTable().destroy(); // Destroy existing instance
        }

        $("#table-activity-log").DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[6, 'desc']],
            ajax: {
                url: "{{ route('payroll.getActivityLog', ['payroll_id' => $payroll_id]) }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                },
            },
            columns: [
                { data: 'employee', name: 'employee' }, // Employee Name
                { data: 'field', name: 'field' },       // Field Name
                { data: 'old_value', name: 'old_value' }, // Old Value
                { data: 'new_value', name: 'new_value' }, // New Value
                { data: 'updated_by', name: 'updated_by' }, // Updated By
                { data: 'updated_at', name: 'updated_at' }, // Updated At
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
</script>
@endsection