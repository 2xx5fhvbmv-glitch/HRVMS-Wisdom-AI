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
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('Performance.Meeting.index') }}" class="btn btn-theme">
                            <i class="fa-solid fa-plus me-1"></i> Create Meeting
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <table class="table table-meetingsList w-100" id="meetingsTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location / Link</th>
                        <th>Accepted</th>
                        <th>Declined</th>
                        <th>Pending</th>
                        <th>Total</th>
                    </tr>
                </thead>
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
        var table = $('#meetingsTable').DataTable({
            searching: true,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            order: [[1, 'desc']],
            ajax: {
                url: '{{ route("Performance.Meeting.listData") }}',
                type: 'GET'
            },
            columns: [
                { data: 'title', name: 'title', className: 'text-nowrap' },
                { data: 'date', name: 'date', className: 'text-nowrap' },
                { data: 'time', name: 'time', className: 'text-nowrap', orderable: false },
                { data: 'location_link', name: 'location_link', className: 'text-nowrap', orderable: false },
                { data: 'accepted', name: 'accepted', className: 'text-center', orderable: false },
                { data: 'declined', name: 'declined', className: 'text-center', orderable: false },
                { data: 'pending', name: 'pending', className: 'text-center', orderable: false },
                { data: 'total', name: 'total', className: 'text-center', orderable: false },
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    });
</script>
@endsection
