@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #employee-resignation-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #employee-resignation-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="employee-resignation-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="resignationSearch"
                                    placeholder="Search by Employee Name, ID or Manager Name" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            {{-- "All" entries use the `ALL` sentinel because
                                 Select2 folds duplicate `value=""` options
                                 into one. The AJAX callback translates ALL
                                 back to '' so the controller sees no filter. --}}
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
                                        <div class="dd-item" role="option" data-value="Completed"><span class="dd-nm">Completed</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Date-range filter disabled per request — backend
                             never honoured `date_range` and the column it
                             would filter wasn't decided. --}}
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
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
   

    <div class="modal fade" id="scheduleMeetingModal" tabindex="-1" aria-labelledby="scheduleMeetingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleMeetingModalLabel">Schedule Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="scheduleMeetingForm">
                    @csrf
                    <input type="hidden" name="resignationId" id="resignation_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="meetingTitle" class="form-label">Meeting Title <span class="req_span">*</span></label>
                            <input type="text" class="form-control" id="meetingTitle" name="meetingTitle" placeholder="Enter Meeting Title" required>      
                        </div>
                        <div class="mb-3">
                            <label for="meetingDate" class="form-label">Meeting Date <span class="req_span">*</span></label>
                            <input type="text" class="form-control datepicker" id="meetingDate" name="meetingDate" placeholder="Select Meeting Date" required>      
                        </div>
                        <div class="mb-3">
                            <label for="meetingTime" class="form-label ">Meeting Time <span class="req_span">*</span></label>       
                            <input type="time" class="form-control" id="meetingTime" name="meetingTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingVenue" class="form-label">Meeting Venue <span class="req_span">*</span></label>
                            <input type="text" class="form-control" id="meetingVenue" name="meetingVenue" placeholder="Enter Meeting Venue (ex: Google Meet, Zoom, Virtual)" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn eb-btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn eb-btn-primary">Schedule Meeting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
 <script>

    $(document).ready(function(){
        getExitClearanceData();
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            minDate: 'today', // Restrict to upcoming dates only
            allowInput: true,
            appendTo: document.body
        });

        // Date filter removed — `#datapicker` no longer exists.
        $('#deptFilter, #positionFilter, #statusFilter').on('change', function () {
            getExitClearanceData();
        });

        // Search input was inert (no id, no handler). Debounce keystrokes
        // so we don't fire an AJAX request per character.
        let _resignationSearchTimer = null;
        $('#resignationSearch').on('input', function () {
            clearTimeout(_resignationSearchTimer);
            _resignationSearchTimer = setTimeout(getExitClearanceData, 300);
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
            "order": [[8, 'desc']],
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('people.employee-resignation.index') }}",
                type: 'GET',
                data: function (d) {
                    // ALL sentinel → no filter (Select2 dedupe workaround).
                    let dept = $('#deptFilter').val();
                    let pos  = $('#positionFilter').val();
                    d.department_id = (dept === 'ALL') ? '' : dept;
                    d.position_id   = (pos  === 'ALL') ? '' : pos;
                    d.status        = $('#statusFilter').val();
                    d.search_term   = $('#resignationSearch').val();
                    // d.date_range = $('#datapicker').val();  // calendar filter disabled
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
                { data: 'created_at', visible: false, searchable: false },
            ]
        });

    }

        $(document).on('click', '.meeting-schedule', function(e) {
            e.preventDefault();
            var resignationId = $(this).data('id');
            $('#resignation_id').val(resignationId);

            $('#scheduleMeetingModal').modal('show');
        });

        $(document).on('submit', '#scheduleMeetingForm', function(e) {
            e.preventDefault();
            var formData = {
                _token: $('input[name="_token"]').val(),
                resignationId: $('#resignation_id').val(),
                meetingTitle: $('#meetingTitle').val(),
                meetingDate: $('#meetingDate').val(),
                meetingTime: $('#meetingTime').val(),
                meetingVenue: $('#meetingVenue').val()
            };
            $.ajax({
                url: "{{ route('people.employee-resignation.schedule-meeting') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.success) {
                        
                        $('#scheduleMeetingForm')[0].reset();
                        $('#scheduleMeetingModal').modal('hide');
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#exit-clearance-table').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message , "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || "An error occurred.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

</script>
@include('resorts._dropdown_script')
@endsection
