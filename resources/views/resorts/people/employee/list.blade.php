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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto  ms-auto">
                        <a class="btn btn-theme" href="{{route('people.employees.create')}}">Add New Employee</a>
                    </div>
                </div>
            </div>
            <div class="card card-empListPeopleEmp">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="search-input" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="deptFilter">
                                <option value="">Departments</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="positionFilter">
                                <option value="">Positions</option>
                                @if($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter">
                                <option value="">Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Terminated">Terminated</option>
                                <option value="Resigned">Resigned</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                        <div class="col-xl-1 col-md-2 col-sm-3 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                        

                        
                        <div class="col-auto ms-auto">
                            <button id="delete-selected" class="btn btn-danger btn-sm">Delete</button>

                            <button id="exportSelectedEmployees" class="btn btn-themeBlue btn-sm">Export</button>
                        </div>
                       
                        <div class="col-auto">
                            <a href="javascript:void();" class="btn btn-grid active">
                                <img src="{{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon">
                            </a>
                            <a href="javascript:void();" class="btn btn-list">
                                <img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-none">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-applicants" id="employeeListTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllDataTable" /></th>
                                    <th>Id</th>
                                    <th>Employee Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Employment Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="grid-main">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label>
                                Show 
                                <select id="gridPageSize" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                entries
                            </label>
                        </div>
                        <div class="form-check mb-0 d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" id="selectAllEmployees" value="option1">
                            <label class="form-check-label mx-2" for="selectAllEmployees"> Select All Employees </label>
                        </div>
                    </div>

                    <div id="grid-employee-container" class="row g-md-4 g-3 mb-4"></div>
                    <div id="grid-pagination"></div>

                </div>
                <div id="selectAllNotice" class="mt-2 d-none">
                    All <span id="totalEmployees">0</span> employees are selected across all pages.
                    <a href="#" id="clearAllSelection">Clear selection</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="assignTeamModal" tabindex="-1" aria-labelledby="assignTeamLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="assignTeamForm">
                @csrf
                <input type="hidden" name="emp_id" id="empIdInput">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign to Team</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="team_id">Select Team <span class="req_span">*</span></label>
                            <select name="team_id" class="form-select select2t-none" required>
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="role_id">Select Role <span class="req_span">*</span></label>
                            <select name="role_id" class="form-select select2t-none" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="statusChangeForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title">Change Status<span class="req_span">*</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="emp_id" id="modal-emp-id">

                <select name="status" id="modal-status" class="form-select select2-modal" required>
                    <option value="">Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Resigned">Resigned</option>
                    <option value="On Leave">On Leave</option>
                    <option value="Suspended">Suspended</option>
                </select>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
            </form>
        </div>
    </div>
@endsection

@section('import-css')
@endsection


@section('import-scripts')
<script>
    let employeeGridUrl = "{{ route('people.employees.grid') }}";
    let selectedEmployees = new Set();
    let selectAllAcrossPages = false;

    $(document).ready(function () {
        loadGridView();
        $('.select2t-none').select2();

        // Toggle View
        $(".btn-grid").addClass("active");
        $(".grid-main").removeClass("d-none").addClass("d-block");
        $(".list-main").addClass("d-none");

        $(".btn-list").click(function () {
            $(this).addClass("active");
            $(".list-main").removeClass("d-none").addClass("d-block");
            $(".btn-grid").removeClass("active");
            $(".grid-main").addClass("d-none");
            getEmpTable();
        });

        $(".btn-grid").click(function () {
            $(this).addClass("active");
            $(".grid-main").removeClass("d-none").addClass("d-block");
            $(".btn-list").removeClass("active");
            $(".list-main").addClass("d-none");
            loadGridView();
        });

        // Filters and search
        $(document).on('keyup', '#search-input', function () {
            $('.btn-grid').hasClass('active') ? loadGridView() : getEmpTable();
        });

        $('#deptFilter, #positionFilter, #statusFilter').on('change', function () {
            $('.btn-grid').hasClass('active') ? loadGridView() : getEmpTable();
        });

        // Pagination
        $(document).on('click', '#grid-pagination .pagination a', function (e) {
            e.preventDefault();
            let page = $(this).attr('href').split('page=')[1];
            loadGridView(page);
        });

        $(document).on('change', '#selectAllDataTable', function () {
            const isChecked = $(this).is(':checked');
            $('.employee-checkbox').each(function () {
                const id = $(this).val();
                $(this).prop('checked', isChecked);
                isChecked ? selectedEmployees.add(id) : selectedEmployees.delete(id);
            });

            if (isChecked) {
                selectAllAcrossPages = false; // for list view, keep it local by default
            }
        });

        // Select all across all pages
        $('#selectAllEmployees').on('change', function () {
            const isChecked = $(this).is(':checked');

            if (isChecked) {
                selectAllAcrossPages = true;
                $('#selectAllNotice').removeClass('d-none');

                $.ajax({
                    url: '{{ route("people.employees.all-ids") }}',
                    method: 'GET',
                    data: {
                        department_id: $('#deptFilter').val(),
                        position_id: $('#positionFilter').val(),
                        status: $('#statusFilter').val(),
                        searchTerm: $('#search-input').val()
                    },
                    success: function (response) {
                        selectedEmployees = new Set(response.ids);
                        $('#totalEmployees').text(response.total);
                        $('.employee-checkbox').prop('checked', true);
                    }
                });
            } else {
                selectAllAcrossPages = false;
                selectedEmployees.clear();
                $('#selectAllNotice').addClass('d-none');
                $('.employee-checkbox').prop('checked', false);
            }
        });

        $('#gridPageSize').on('change', function () {
            loadGridView(1); // Reset to page 1 when pageSize changes
        });
        // Individual checkbox change
        $(document).on('change', '.employee-checkbox', function () {
            const id = $(this).val();
            if ($(this).is(':checked')) {
                selectedEmployees.add(id);
            } else {
                selectedEmployees.delete(id);
                selectAllAcrossPages = false;
                $('#selectAllEmployees').prop('checked', false);
                $('#selectAllNotice').addClass('d-none');
            }
        });

          $(document).on('click', '.change-status', function (e) {
            e.preventDefault();
            
            const empId = $(this).data('id');
            const currentStatus = $(this).data('status');

            $('#modal-emp-id').val(empId);

            $('#statusChangeModal').modal('show');
        });

        $('#statusChangeForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{route("people.employee.change-status")}}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success("Status Updated!", "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        const empId = $('#modal-emp-id').val();
                        const newStatus = $('#modal-status').val();

                        getEmpTable(); 
                        loadGridView(); // Reload Grid View

                    
                        // ✅ Close modal if using Bootstrap modal
                        $('#statusChangeModal').modal('hide');
                    }
                },
                error: function(xhr) {
                    // alert('Failed to update status');
                    toastr.error("Failed to update status!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on('click','.add-to-team-btn',function(){
            let empId = $(this).data('emp-id');
            $('#empIdInput').val(empId);
            $('#assignTeamModal').modal('show');
        });

        $('#assignTeamForm').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('people.employees.assign-team') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    $('#assignModal').modal('hide');
                    if (response.status === 'success') {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Optionally refresh or update the team list
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    toastr.error("Something went wrong. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

         $(document).on('click', '.delete-employee', function(e) {
            e.preventDefault();
            const empId = $(this).data('emp-id');

            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to delete this employee!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('employee.delete') }}", // Adjust route name
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: empId
                        },
                        success: function(response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                if ($('.btn-grid').hasClass('active')) {
                                    loadGridView(); // Reload Grid View
                                } else {
                                    getEmpTable();  // Reload List View
                                }
                            });
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });


        // Clear selection link
        $('#clearAllSelection').on('click', function (e) {
            e.preventDefault();
            selectedEmployees.clear();
            selectAllAcrossPages = false;
            $('#selectAllEmployees').prop('checked', false);
            $('#selectAllNotice').addClass('d-none');
            $('.employee-checkbox').prop('checked', false);
        });

        // Export selected
        $('#exportSelectedEmployees').on('click', function () {
            if (selectedEmployees.size === 0) {
                alert('Please select at least one employee to export.');
                return;
            }

            const ids = Array.from(selectedEmployees);

            $.ajax({
                url: '{{ route("people.employees.export") }}',
                method: 'POST',
                data: {
                    employee_ids: ids,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                xhrFields: { responseType: 'blob' },
                success: function (response, status, xhr) {
                    const filename = xhr.getResponseHeader('Content-Disposition')?.split('filename=')[1]?.replaceAll('"', '') || 'employees-export.xlsx';
                    const url = window.URL.createObjectURL(response);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
            });
        });

        // Status change, team assign, delete, etc. remain unchanged...

        $('#clearFilter').on('click', function () {
            selectedEmployees.clear();
            selectAllAcrossPages = false;
            $('#selectAllEmployees').prop('checked', false);
            $('#selectAllNotice').addClass('d-none');
            $('#search-input').val('');
            $('#deptFilter').val('').trigger('change');
            $('#statusFilter').val('').trigger('change');
            $('#positionFilter').val('').trigger('change');
            loadGridView();
            getEmpTable();
        });
    });

    function loadGridView(page = 1) {
        let pageSize = $('#gridPageSize').val();
        $.ajax({
            url: `${employeeGridUrl}?page=${page}&pageSize=${pageSize}`,
            data: {
                department_id: $('#deptFilter').val(),
                position_id: $('#positionFilter').val(),
                status: $('#statusFilter').val(),
                searchTerm: $('#search-input').val(),
            },
            success: function (res) {
                $('#grid-employee-container').html(res.html);
                $('#grid-pagination').html(res.pagination);

                $('.employee-checkbox').each(function () {
                    const id = $(this).val();
                    if (selectAllAcrossPages) {
                        $(this).prop('checked', true);
                        selectedEmployees.add(id); // ensure consistency
                    } else {
                        $(this).prop('checked', selectedEmployees.has(id));
                    }
                });

                if (selectAllAcrossPages) {
                    $('#selectAllEmployees').prop('checked', true);
                    $('#selectAllNotice').removeClass('d-none');
                    $('#totalEmployees').text(selectedEmployees.size);
                } else {
                    const allChecked = $('.employee-checkbox').length &&
                    $('.employee-checkbox:checked').length === $('.employee-checkbox').length;
                    $('#selectAllEmployees').prop('checked', allChecked);
                }
            }
        });
    }

    function getEmpTable() {
        if ($.fn.dataTable.isDataTable('#employeeListTable')) {
            $('#employeeListTable').DataTable().destroy();
        }
        
        $('#employeeListTable').DataTable({
            searching: false,
            bLengthChange: true,
            bInfo: true,
            scrollX: true,
            processing: true,
            serverSide: true,
            order: [[8, 'desc']],
            ajax: {
                url: '{{ route("people.employees.list") }}',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.searchTerm = $('#search-input').val();
                    d.status = $('#statusFilter').val();
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'Emp_id' },
                { data: 'applicant',orderable: false },
                { data: 'position',orderable: false },
                { data: 'department',orderable: false },
                { data: 'status' },
                { data: 'employment_type' },
                { data: 'action', orderable: false, searchable: false },
                { data:'created_at',visible:false,searchable:false},
            ],
            drawCallback: function (settings) {
                // Restore checkbox state after pagination/sorting
                $('.employee-checkbox').each(function () {
                    const id = $(this).val();
                    if (selectAllAcrossPages) {
                        $(this).prop('checked', true);
                        selectedEmployees.add(id);
                    } else {
                        $(this).prop('checked', selectedEmployees.has(id));
                    }
                });

                if (selectAllAcrossPages) {
                    $('#selectAllEmployees').prop('checked', true);
                    $('#selectAllNotice').removeClass('d-none');
                    $('#totalEmployees').text(selectedEmployees.size);
                } else {
                    const allChecked = $('.employee-checkbox').length &&
                        $('.employee-checkbox:checked').length === $('.employee-checkbox').length;
                    $('#selectAllEmployees').prop('checked', allChecked);
                }
            }
        });
    }
</script>
@endsection