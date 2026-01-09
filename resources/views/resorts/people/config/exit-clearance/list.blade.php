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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <table id="table-exitclearance-form" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Form Type</th>
                                <th>Department</th>
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
        $(".select2t-none").select2();
        $('#table-exitclearance-form tbody').empty();
        var viewForm = $('#table-exitclearance-form').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            order: [[4, 'asc']],
            ajax: {
                url: '{{ route("people.exit-clearance.list") }}',
                type: 'GET',
                data: function(d) {
                    var searchTerm = $('.search').val();
                    d.searchTerm = searchTerm;
                }
            },
            columns: [
                { data: 'form_name', name: 'form_name', className: 'text-nowrap' },
                { data: 'form_type', name: 'form_type',},
                { data: 'department', name: 'department',},
                { data: 'action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });

        $(document).on('click', '.delete-row-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('people.exit-clearance.destroy', ':id') }}";
            url = url.replace(':id', id);

            Swal.fire({
                title: 'Are you sure you want to delete?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#DD6B55',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: "DELETE",
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right',
                                });
                                $('#table-exitclearance-form').DataTable().ajax.reload();
                            } 
                        },
                        error: function(xhr, status, error) {
                            toastr.error('An error occurred while processing your request.', "Error", {
                                positionClass: 'toast-bottom-right',
                            });
                            console.error('Error:', error);
                        },
                    });
                }
            });
        });
        $('#training').change(function() {
            viewForm.ajax.reload();
        });
        $('.search').on('keyup', function() {
            viewForm.ajax.reload();
        });

    });
    </script>
    @endsection
