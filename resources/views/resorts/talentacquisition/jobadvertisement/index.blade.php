@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('resort.ta.configration') }}" class="btn btn-theme">Back to Configuration</a>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                                <div class="input-group">
                                    <input type="search" class="form-control search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <table class="table table-jobAdTemplates w-100">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>File Name</th>
                                <th>Uploaded By</th>
                                <th>Uploaded At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endsection

    @section('import-css')
    @endsection

    @section('import-scripts')
    <script type="text/javascript">
    $(document).ready(function() {

        $('.table-jobAdTemplates tbody').empty();
        var jobAdTable = $('.table-jobAdTemplates').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route("resort.ta.jobadvertisment.getList") }}',
                type: 'GET',
                data: function(d) {
                    var searchTerm = $('.search').val();
                    d.searchTerm = searchTerm;
                }
            },
            columns: [
                { data: 'Preview', name: 'Preview', className: 'text-nowrap', orderable: false, searchable: false },
                { data: 'FileName', name: 'FileName', className: 'text-nowrap' },
                { data: 'UploadedBy', name: 'UploadedBy', className: 'text-nowrap', orderable: false, searchable: false },
                { data: 'UploadedAt', name: 'UploadedAt', className: 'text-nowrap' },
                { data: 'action', name: 'Action', orderable: false, searchable: false },
            ]
        });

        $('.search').on('keyup', function() {
            jobAdTable.ajax.reload();
        });

        $(document).on('click', '.delete-row-btn', function() {
            let id = $(this).data('id');

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
                        url: "{{ route('resort.ta.jobadvertisment.destroy', '') }}/" + id,
                        dataType: "json",
                    }).done(function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            jobAdTable.ajax.reload();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(xhr) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

    });
    </script>
    @endsection
