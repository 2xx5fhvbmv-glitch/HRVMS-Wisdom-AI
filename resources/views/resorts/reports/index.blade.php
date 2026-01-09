
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                        <span>Report</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-title">
                <div class="row g-1">
                    <div class="col">
                        <h3>Reports</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('reports.create') }}" class="btn btn-sm btn-theme @if(Common::checkRouteWisePermission('resort.report.index',config('settings.resort_permissions.create')) == false) d-none @endif">Create Report</a>
                    </div>
                </div>  
            </div>  
            <div class="card-body">
                <div class="table">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                      
                                <table class="table" id="ReportList">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      
                                    </tbody>
                                </table>
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
    $(document).ready(function(){
        loadSupportlist();
    });

    function loadSupportlist() {
        if ($.fn.DataTable.isDataTable('#ReportList')) {
            $('#ReportList').DataTable().destroy();
        }

        var table = $('#ReportList').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            ordering: true,
            order: [[2, 'desc']], // Order by Created At column (index 2) in descending order
            ajax: {
                url: "{{ route('resort.report.index')}}",
                type: 'GET',
            },
            columns: [
                { data: 'name', name: 'name', orderable: true },
                { data: 'description', name: 'description', orderable: false, searchable: false },
                { data: 'CareatedAt', name: 'CareatedAt', orderable: true, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
                
            ],
        });
    }
</script>
@endsection