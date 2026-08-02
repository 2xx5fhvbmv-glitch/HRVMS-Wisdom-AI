@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('offline-interview.create') }}" class="btn ta-btn-accent btn-sm">
                        <i class="fa-solid fa-plus"></i> New Offline Interview
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 col-6">
                        <div class="input-group">
                            <input type="search" class="form-control" id="searchInput" placeholder="Search by name / email / passport / position" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-4 col-6">
                        <select class="form-select select2t-none" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="Draft">Draft</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Selected">Selected</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Withdrawn">Withdrawn</option>
                        </select>
                    </div>
                </div>
            </div>

            <table id="offlineInterviewList" class="table data-Table w-100">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Email</th>
                        <th>Passport No</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Step</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@section('import-css')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
@endsection

@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    $('.select2t-none').select2();

    loadOfflineInterviewList();

    $('#searchInput, #statusFilter').on('keyup change', function () {
        $('#offlineInterviewList').DataTable().ajax.reload(null, false);
    });

    // Delete (only for non-finalised rows).
    $(document).on('click', '.offline-iv-delete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete this offline interview?',
            text: 'The draft and any uploaded files will be removed. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ url("/resort/offline-interview") }}/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function (resp) {
                    if (resp.success) {
                        toastr.success(resp.message || 'Deleted.', 'Success', { positionClass: 'toast-bottom-right' });
                        $('#offlineInterviewList').DataTable().ajax.reload(null, false);
                    } else {
                        toastr.error(resp.message || 'Could not delete.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete.';
                    toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
});

function loadOfflineInterviewList() {
    if ($.fn.DataTable.isDataTable('#offlineInterviewList')) {
        $('#offlineInterviewList').DataTable().destroy();
    }
    $('#offlineInterviewList').DataTable({
        searching: false,
        lengthChange: false,
        info: true,
        scrollX: true,
        pageLength: 10,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("offline-interview.index") }}',
            data: function (d) {
                d.search = $('#searchInput').val();
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'candidate_name', name: 'candidate_name' },
            { data: 'email', name: 'email' },
            { data: 'passport', name: 'passport' },
            { data: 'position', name: 'position' },
            { data: 'department', name: 'department' },
            { data: 'step', name: 'step', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'created_at_fmt', name: 'created_at_fmt' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });
}
</script>
@endsection
