@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #exit-clearance-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #exit-clearance-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="exit-clearance-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
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
                                <input type="search" id="searchInput" class="form-control"
                                    placeholder="Search by Employee Name, ID or Manager Name" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            {{-- "All Departments" uses a sentinel value `ALL`
                                 instead of an empty string. Select2 folds
                                 duplicate `value=""` options into one, which is
                                 why the explicit "All" entry wasn't rendering.
                                 The sentinel is converted back to '' in the
                                 AJAX callback so the controller sees no filter. --}}
                            <select class="form-select dd-native-select" id="deptFilter" data-placeholder="By Department">
                                <option value=""></option>
                                <option value="ALL">All Departments</option>
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#deptFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">By Department</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">By Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="ALL"><span class="dd-nm">All Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($departments)
                                            @foreach($departments as $department)
                                                <div class="dd-item" role="option" data-value="{{ $department->id }}"><span class="dd-nm">{{ $department->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="positionFilter" data-placeholder="By Position">
                                <option value=""></option>
                                <option value="ALL">All Positions</option>
                                @if($positions)
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#positionFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">By Position</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Position">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">By Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="ALL"><span class="dd-nm">All Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($positions)
                                            @foreach($positions as $position)
                                                <div class="dd-item" role="option" data-value="{{ $position->id }}"><span class="dd-nm">{{ $position->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="statusFilter" data-placeholder="By Status">
                                <option></option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <div class="dd" data-target="#statusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">By Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">By Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Pending"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="In Progress"><span class="dd-nm">In Progress</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="On Hold"><span class="dd-nm">On Hold</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Approved"><span class="dd-nm">Approved</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Rejected"><span class="dd-nm">Rejected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Completed"><span class="dd-nm">Completed</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Calendar / date-range filter disabled per request.
                             Backend never honoured `date_range`, and the
                             column it would filter (resignation_date /
                             last_working_day) wasn't decided. Re-enable
                             once the spec is clear. --}}
                        {{-- <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" class="form-control datepicker" id="datapicker" data-placeholder="Date Range"/>
                        </div> --}}
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-lable table-exitInterviewPeopleEmp mb-1" id="exit-clearance-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Resignation Date</th>
                                <th>Last Working Date</th>
                                <th>Status <i class="fa fa-info-circle" title="Click status then view status history"></i></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Exit Clearance Details -->
    <div class="modal fade" id="listDep-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-listDep">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">List of Departments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                   <form action="{{route('people.exit-clearance.assignmentSubmitDepartment')}}" id="exitClearanceAssignForm" method="POST">
                        @csrf
                        <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                            <label for="select_dep" class="form-label">SELECT DEPARTMENTS FOR CLEARANCE</label>
                            <select class="form-select select2t-none" multiple id="select_dep" name="department_ids[]" aria-label="Default select example">
                                
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" >{{ $department->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                                         
                        <div class="col-xl-12 col-md-12 col-sm-12 col-12 mt-2">
                            <label for="select_dep" class="form-label">Due Date</label>
                            <input type="text" class="form-control datepicker" name="deadline_date" id="datapicker_modal" placeholder="Date Range" data-placeholder="Date Range"/>
                        </div>
                   </form>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="javascript:void()" class="btn btn-themeBlue" id="submitExitClearanceForm">Submit</a>
                </div>
            </div>
        </div>
    </div>
   
    <div class="modal fade" id="exitClear-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-exitClear">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Exit Clearance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="manning-timeline text-start" id="exit-clearance-status-list">
                        <!-- Status items will be appended here via AJAX -->
                    </ul>
                </div>
            </div>
        </div>
    </div>


    {{-- <div class="modal fade" id="exitClear-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-exitClear">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Exit Clearance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <ul class="manning-timeline text-start ">
                        <li class="active">
                            <div>
                                <h6>Clearance Forms Date :</h6>
                                <p>18 March 2025</p>
                            </div>
                        </li>
                        <li class="active">
                            <div>
                                <h6>Full & Final Settlements</h6>
                                <p>19 March 2025</p>
                            </div>
                        </li>
                        <li>
                            <div>
                                <h6>Exit Interview Responses</h6>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div> --}}
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
 <script>

        $(document).ready(function() {
            $('#datapicker_modal').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            zIndex: 9999,
            container: '#listDep-modal'
            });

            $('#listDep-modal').on('shown.bs.modal', function () {
            $('#datapicker_modal').datepicker('update');
            });
        });

        $(document).ready(function() {
            $('#submitExitClearanceForm').click(function(e) {
                e.preventDefault();

                let resignation_id = $('#listDepModal').data('id');
                
                let departmentId = $('#select_dep').val();
                let dueDate = $('#datapicker_modal').val();

                if (!departmentId || !dueDate) {
                    toastr.error("Please select at least one department and a due date.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                $.ajax({
                    url: $('#exitClearanceAssignForm').attr('action'),
                    type: 'POST',
                    data: {
                        employee_resignation_id: resignation_id,
                        department_id: departmentId,
                        deadline_date: dueDate,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Three response shapes from the server:
                        //   • status:'success'      → every dept got a form
                        //   • status:'partial'      → some did, some skipped
                        //   • status:'no_template'  → zero forms exist; user
                        //     needs to create department templates first.
                        // Toast color tracks intent so partial/no-template
                        // failures don't masquerade as green checkmarks.
                        var toastOpts = { positionClass: 'toast-bottom-right', timeOut: 8000, extendedTimeOut: 4000 };
                        if (response.status === 'no_template') {
                            toastr.warning(response.message, "Templates missing", toastOpts);
                            // Keep the modal open so HR can adjust the dept
                            // selection without re-opening the dialog.
                            return;
                        }
                        if (response.status === 'partial') {
                            toastr.warning(response.message, "Partially assigned", toastOpts);
                        } else if (response.success) {
                            toastr.success(response.message, "Success", toastOpts);
                        }

                        if (response.success) {
                            $('#listDep-modal').modal('hide');
                            $('#select_dep').val('').trigger('change');
                            $('#datapicker_modal').val('');
                            // `.reset()` is a DOM method, not jQuery —
                            // calling it on the jQuery wrapper threw
                            // "reset is not a function". Use the raw
                            // form node.
                            const f = document.getElementById('exitClearanceAssignForm');
                            if (f) f.reset();
                            getExitClearanceData();

                            // Auto-open the Status modal so HR immediately
                            // sees the new assignments — was a hidden side
                            // effect before: assignments were created but
                            // the user had to click the status badge to
                            // see them, which read as "nothing happened".
                            if (resignation_id) {
                                $(document)
                                    .find('.status-modal-trigger[data-id="' + resignation_id + '"]')
                                    .first()
                                    .trigger('click');
                            }
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Failed to assign exit clearance.';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
                    }
                });
            });
        });

    
    $(document).ready(function(){        
        getExitClearanceData();
        $('.select2t-none').select2();
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });

        // Calendar filter (#datapicker) commented out — re-add it here
        // once the spec for date_range is decided.
        // Debounced free-text search — reloads the table 300ms after
        // the user stops typing so we don't fire a query per keystroke.
        // Sends the value as `searchTerm`; controller looks it up
        // against Emp_id, employee name, and assigned-to name.
        let searchDebounce;
        $('#searchInput').on('input search', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function () {
                getExitClearanceData();
            }, 300);
        });

        $('#deptFilter, #positionFilter, #statusFilter').on('change', function () {
            getExitClearanceData();
        });
    
    });

    function getExitClearanceData() {
        if ($.fn.dataTable.isDataTable('#exit-clearance-table')) {
            $('#exit-clearance-table').DataTable().destroy();
        }
        $table = $('#exit-clearance-table').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[8, 'desc']],
            ajax: {
                url: "{{ route('people.exit-clearance') }}",
                type: 'GET',
                data: function (d) {
                    // 'ALL' = the explicit "All Departments / All Positions"
                    // entry. Backend treats blank as "no filter", so map the
                    // sentinel back to '' before sending.
                    let dept = $('#deptFilter').val();
                    let pos  = $('#positionFilter').val();
                    d.department_id = (dept === 'ALL') ? '' : dept;
                    d.position_id   = (pos  === 'ALL') ? '' : pos;
                    d.status = $('#statusFilter').val();
                    // Free-text search box — controller LIKE-matches
                    // against Emp_id, full_name, and assigned-to name.
                    d.searchTerm = ($('#searchInput').val() || '').trim();
                    // d.date_range = $('#datapicker').val();  // calendar filter disabled

                    // Forward ?empId=<base64> from the URL when the page is
                    // opened from the Employee Detail "Clearance" tab so the
                    // datatable scopes to that employee's clearance only.
                    const empIdFromUrl = new URLSearchParams(window.location.search).get('empId');
                    if (empIdFromUrl) {
                        d.empId = empIdFromUrl;
                    }
                }
            },
            columns: [
                { data: 'Emp_id', name: 'Emp_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'position', name: 'position' },
                { data: 'department', name: 'department' },
                { data: 'resignation_date', name: 'resignation_date' },
                { data: 'last_working_day', name: 'last_working_day' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'created_at', visible: false, searchable: false }
            ]
        });
    }

        $(document).on('click', '.status-modal-trigger', function (e) {
            e.preventDefault();
            const resignationId = $(this).data('id');
            const modal = $('#exitClear-modal');
            const statusList = $('#exit-clearance-status-list');

            // Open the modal first with a loading placeholder so the user
            // gets immediate feedback, then swap in the timeline data.
            statusList.html('<li><div><p class="text-muted">Loading…</p></div></li>');
            modal.modal('show');

            $.ajax({
                url: "{{ route('people.exit-clearance.get-status') }}",
                type: 'GET',
                data: { resignation_id: resignationId },
                success: function (response) {
                    statusList.empty();
                    if (response.success && response.data && response.data.length > 0) {
                        response.data.forEach(function (value) {
                            const formName = value.exit_clearance_form
                                ? value.exit_clearance_form.form_name
                                : 'Clearance Form';
                            const statusItem = `
                                <li class="${value.status === 'Completed' ? 'active' : ''}">
                                    <div>
                                        <h6>${formName}</h6>
                                        ${value.deadline_date ? `<p>Due Date: ${value.deadline_date}</p>` : ''}
                                    </div>
                                </li>`;
                            statusList.append(statusItem);
                        });
                    } else {
                        statusList.html('<li><div><p>No status updates available.</p></div></li>');
                    }
                },
                error: function () {
                    statusList.html('<li><div><p class="text-danger">Failed to load status updates.</p></div></li>');
                }
            });
        });
</script>
@include('resorts._dropdown_script')
@endsection
