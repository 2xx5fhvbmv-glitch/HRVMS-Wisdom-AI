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

            {{-- Payroll Workflow Timeline --}}
            @if(isset($timeline) && $timeline->isNotEmpty())
            <div class="card mb-3">
                <div class="card-title px-3 pt-3">
                    <h3>Payroll Workflow Timeline</h3>
                    @if(isset($payroll))
                        <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}
                        <span class="badge {{ $payroll->status === 'locked' ? 'badge-themeSuccess' : ($payroll->status === 'approved' ? 'badge-themeBlue' : 'badge-themeWarning') }} ms-2">{{ ucfirst(str_replace('_', ' ', $payroll->status)) }}</span></p>
                    @endif
                </div>
                <div class="card-body pt-0">
                    <div class="position-relative" style="padding-left: 30px;">
                        @foreach($timeline as $event)
                            <div class="d-flex mb-3 position-relative">
                                <div class="position-absolute" style="left: -30px; top: 2px;">
                                    @if($event['status'] === 'completed')
                                        <i class="fa-solid {{ $event['icon'] }} text-success" style="font-size: 18px;"></i>
                                    @elseif($event['status'] === 'rejected')
                                        <i class="fa-solid {{ $event['icon'] }} text-danger" style="font-size: 18px;"></i>
                                    @else
                                        <i class="fa-solid {{ $event['icon'] }} text-muted" style="font-size: 18px;"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ $event['action'] }}</strong>
                                            <p class="mb-0 text-muted small">{{ $event['description'] }}</p>
                                            <small class="text-muted">By: {{ $event['user'] }}</small>
                                        </div>
                                        <div class="text-end">
                                            @if($event['date'])
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($event['date'])->format('d M Y, h:i A') }}</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div style="border-left: 2px solid #e0e0e0; height: 15px; margin-left: -21px; margin-bottom: 3px;"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <div class="card card-payrollMain">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-auto">
                            <h3>Attendance Change Log</h3>
                        </div>
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ms-auto">
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