
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
                        <div class="dropdown d-inline-block me-1">
                            <button class="btn btn-sm btn-themeSkyblue dropdown-toggle" type="button" id="predefinedReportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Predefined Reports
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="predefinedReportsDropdown">
                                <li><a class="dropdown-item" href="{{ route('resort.report.wfp.index') }}">Workforce Planning Reports</a></li>
                                <li><a class="dropdown-item" href="{{ route('resort.report.payroll.index') }}">Payroll Reports</a></li>
                                <li><a class="dropdown-item" href="{{ route('resort.report.visa.index') }}">Visa / Immigration Reports</a></li>
                            </ul>
                        </div>
                        <a href="{{ route('reports.create') }}" class="btn btn-sm btn-theme @if(Common::checkRouteWisePermission('resort.report.index',config('settings.resort_permissions.create')) == false) d-none @endif">Custom Report</a>
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

    $(document).on('click', '.report-delete-btn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this report?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('resort/report') }}/" + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                        if ($.fn.DataTable.isDataTable('#ReportList')) {
                            $('#ReportList').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete report', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>
@endsection