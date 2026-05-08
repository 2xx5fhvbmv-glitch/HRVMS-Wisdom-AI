@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Grievance & Disciplinary</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="appealsTable" class="table w-100">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Appeal No</th>
                            <th class="text-nowrap">Grievance ID</th>
                            <th class="text-nowrap">Category</th>
                            <th class="text-nowrap">Submitted By</th>
                            <th class="text-nowrap">Submitted</th>
                            <th>Status</th>
                            <th>Hearings</th>
                            <th>Decision</th>
                            <th class="text-nowrap">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
$(function () {
    if ($.fn.dataTable.isDataTable('#appealsTable')) {
        $('#appealsTable').DataTable().destroy();
    }
    $('#appealsTable').DataTable({
        bAutoWidth: false,
        scrollX: true,
        processing: true,
        serverSide: true,
        order: [[4, 'desc']],
        ajax: '{{ route("GrievanceAndDisciplinery.Appeals.List") }}',
        columns: [
            { data: 'AppealNo',    name: 'AppealNo',    className: 'text-nowrap' },
            { data: 'GrievanceId', name: 'GrievanceId', className: 'text-nowrap' },
            { data: 'Category',    name: 'Category',    className: 'text-nowrap' },
            { data: 'Submitter',   name: 'Submitter',   className: 'text-nowrap' },
            { data: 'SubmittedAt', name: 'SubmittedAt', className: 'text-nowrap' },
            { data: 'Status',      name: 'Status' },
            { data: 'Hearings',    name: 'Hearings' },
            { data: 'Decision',    name: 'Decision',    className: 'text-nowrap' },
            { data: 'Action',      name: 'Action', orderable: false, searchable: false },
        ]
    });
});
</script>
@endsection
