@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #performance-meetings-list-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #performance-meetings-list-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="performance-meetings-list-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('Performance.Meeting.index') }}" class="btn perf-btn-accent">
                            <i class="fa-solid fa-plus me-1"></i> Create Meeting
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search">
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
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
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    .participant-card { padding: 12px; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e0e0e0; }
    .participant-card.accepted { background: #e8f5e9; border-color: #28a745; }
    .participant-card.declined { background: #ffeaea; border-color: #dc3545; }
    .participant-card.pending { background: #fff8e1; border-color: #EFB408; }
    .participant-card .profile-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .reason-box { background: #fff; border: 1px solid #dc3545; border-radius: 6px; padding: 8px 12px; margin-top: 8px; font-size: 13px; }
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        var table = $('#meetingsTable').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: false,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            order: [[1, 'desc']],
            ajax: {
                url: '{{ route("Performance.Meeting.listData") }}',
                type: 'GET',
                data: function(d) {
                    d.search = { value: $('.search').val() };
                }
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
                { data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false },
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Custom search box handler
        var searchTimer;
        $('.search').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                table.ajax.reload();
            }, 400);
        });
    });
</script>
@endsection
