@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #announcements-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #announcements-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="announcements-hero">
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
                            <select class="form-select dd-native-select" id="categoryFilter" >
                                <option value="">Announcement Type</option>
                                @if($categories)
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#categoryFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Announcement Type</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Announcement Type">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Announcement Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($categories)
                                            @foreach($categories as $category)
                                                <div class="dd-item" role="option" data-value="{{$category->id}}"><span class="dd-nm">{{$category->name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="deptFilter">
                                <option value="">Departments</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#deptFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Departments</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($departments)
                                            @foreach($departments as $dept)
                                                <div class="dd-item" role="option" data-value="{{$dept->id}}"><span class="dd-nm">{{$dept->name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-auto col-md-4 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="statusFilter">
                                <option value="">By Status</option>
                                <option value="Draft">Draft</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Published">Published</option>
                            </select>
                            <div class="dd" data-target="#statusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">By Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">By Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Draft"><span class="dd-nm">Draft</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Scheduled"><span class="dd-nm">Scheduled</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Published"><span class="dd-nm">Published</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
       getAnnouncement();
       flatpickr('#dateFilter', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });

       $('#toggleArchivedAnnouncements').change(function () {
            getAnnouncement();
        });

        $('#searchInput,#categoryFilter,#dateFilter,#statusFilter,#deptFilter').on('keyup change', function () {
            getAnnouncement();
        });

        $(document).on('click', '.archive-announcement', function () {
            let announcementId = $(this).data('id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Are you sure you want to archive this announcement?',
                text: 'This cannot be undone.',
                confirmText: 'Yes, archive it!',
                cancelText: 'Cancel'
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

            wisdomConfirm({
                role: 'positive',
                title: 'Restore this announcement?',
                text: 'It will be moved back to the active list.',
                confirmText: 'Yes, Restore'
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
                    // Forward ?empId=<base64> from the URL when the page is
                    // opened from the Employee Detail "Announcement" tab so
                    // the list scopes to announcements for that employee.
                    const empIdFromUrl = new URLSearchParams(window.location.search).get('empId');
                    if (empIdFromUrl) { d.empId = empIdFromUrl; }
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
@include('resorts._dropdown_script')
@endsection