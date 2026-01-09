@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                       {{-- <span>Notifications</span> --}}
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
               
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <!-- <div class="d-flex justify-content-start align-items-center">
                                        <h3>{{ $page_title }}</h3>
                                    </div> -->
                                </div>
                            </div>
                        </div>

                        <table id="IndexGrievanceDelegationRule" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Module Name </th>
                                    <th class="text-nowrap"> Type </th>
                                    <th> Message</th>
                                    <th> Status </th>
                                    <th class="text-nowrap">Action</th>
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
    var DisciplineryCategory = $('#IndexGrievanceDelegationRule').DataTable({
        "searching": false,
        "bLengthChange": true,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 10,
        "processing": true,
        "serverSide": true,
        "order": [[5, 'desc']], // Sorting by created_at
        "ajax": '{{ route("resort.Mark.NotificationList") }}',
        "columns": [
            { data: 'module', name: 'module', className: 'text-nowrap' },
            { data: 'type', name: 'type', className: 'text-nowrap' },
            { data: 'message', name: 'message', className: 'text-nowrap' },
            { data: 'status', name: 'status', className: 'text-nowrap' },
            { data: 'Action', name: 'Action', orderable: false, searchable: false },
            { data: 'created_at', visible: false, searchable: false }, // hidden sort base
        ]
    });

    $(document).on('click', '.delete-row-btn', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest("tr");
        var main_id = $(this).data('cat-id');

        Swal.fire({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('resort.Mark.NotificationDestory', ':id') }}".replace(':id', main_id),
                    dataType: "json",
                }).done(function(result) {
                    if (result.success) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#IndexGrievanceDelegationRule').DataTable().ajax.reload();

                    } else {
                        toastr.error(result.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }).fail(function(jqXHR) {
                    toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }
        });
    });
</script>
@endsection
