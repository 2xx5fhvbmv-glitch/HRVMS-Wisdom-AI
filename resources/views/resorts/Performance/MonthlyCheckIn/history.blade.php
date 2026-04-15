@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Performance.CreateMonltyCheckIn') }}" class="btn btn-themeBlue btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-collapseNew" id="historyTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Meeting Place</th>
                                <th>Area of Discussion</th>
                                <th>Improvement</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('Performance.MonltyCheckIn.historyData') }}",
        columns: [
            { data: 'employee', orderable: false },
            { data: 'date', orderable: false },
            { data: 'time', orderable: false },
            { data: 'meeting_place', orderable: false },
            { data: 'area', orderable: false },
            { data: 'improvement', orderable: false },
            { data: 'status_badge', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ]
    });
});
</script>
@endsection
