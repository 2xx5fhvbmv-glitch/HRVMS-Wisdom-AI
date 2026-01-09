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
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Announcement</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                        <div class="col-xl-auto col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="categoryFilter" >
                                <option value="">Announcement Type</option>
                                @if($categories)
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="deptFilter">
                                <option value="">Departments</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-auto col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter">
                                <option value="">By Status</option>
                                <option value="Draft">Draft</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Published">Published</option>
                            </select>
                        </div>
                        <div class="col-auto ms-auto">
                            <div class="form-check form-switch form-switchTheme switch-blue">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="toggleArchivedAnnouncements">
                                <label class="form-check-label" for="toggleArchivedAnnouncements">Show Archived</label>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="announcementTable" class="table data-Table  table-announcementsList w-100">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Employee Name</th>
                            <th>Employee ID</th>
                            <th>Department & Position</th>
                            <th>Publication Date</th>
                            <th>Status</th>
                            <th>Actions</th>
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
    $(document).ready(function(){
       getAnnouncement();
       $('.select2t-none').select2();
       $('#dateFilter').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });

       $('#toggleArchivedAnnouncements').change(function () {
            getAnnouncement();
        });

        $('#searchInput,#categoryFilter,#dateFilter,#statusFilter,#deptFilter').on('keyup change', function () {
            getAnnouncement();
        });

        $(document).on('click', '.archive-announcement', function () {
            let announcementId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure you want to archive this announcement?',
                text: 'This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, archive it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#DD6B55'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('people.announcements.archive') }}",
                        method: "POST",
                        data: {
                            id: announcementId,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            if (response.status) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#announcementTable').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function (jqXHR) {
                            toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.restore-announcement', function () {
            let announcementId = $(this).data('id');

            Swal.fire({
                title: 'Restore this announcement?',
                text: 'It will be moved back to active list.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Yes, Restore',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('people.announcements.restore') }}",
                        method: "POST",
                        data: {
                            id: announcementId,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#announcementTable').DataTable().ajax.reload();
                        },
                        error: function () {
                            toastr.error("Something went wrong.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
        });


    })
    function getAnnouncement() {
        if ($.fn.dataTable.isDataTable('#announcementTable')) {
            $('#announcementTable').DataTable().destroy();
        }
        var announcementTable = $('#announcementTable').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[7, 'desc']],
            ajax: {
                url: "{{ route('people.announcements') }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val(); // Pass search term
                    d.category = $('#categoryFilter').val();
                    d.status = $('#statusFilter').val();
                    d.department = $('#deptFilter').val();
                    d.archived = $('#toggleArchivedAnnouncements').is(':checked') ? 1 : 0;
                    let selectedDate = $('.datepicker').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`; // YYYY-MM-DD format
                    } else {
                        d.date = '';
                    }
                },
                type: "GET",
            },
            columns: [
                { data: 'title', name: 'title' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'employee_id', name: 'employee_id' },
                { data: 'department_position', name: 'department_position' },
                { data: 'published_date', name: 'published_date' },
                { data: 'status', name: 'status' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ],
            error: function(xhr, error, code) {
                console.log(xhr.responseText);
            }
        });
    }
</script>
@endsection