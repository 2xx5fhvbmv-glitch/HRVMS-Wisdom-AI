@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #probation-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #probation-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="probation-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto"><a href="#" class="btn btn-theme">Export</a></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-5 col-sm-4 col-6">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="deptFilter">
                                <option value="">By Department</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
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
                                        @if($departments)
                                            @foreach($departments as $dept)
                                                <div class="dd-item" role="option" data-value="{{$dept->id}}"><span class="dd-nm">{{$dept->name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="positionFilter">
                                <option value="">By Position</option>
                                @if($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
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
                                        @if($positions)
                                            @foreach($positions as $pos)
                                                <div class="dd-item" role="option" data-value="{{$pos->id}}"><span class="dd-nm">{{$pos->position_title}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="statusFilter">
                                <option value="">By Probation Status</option>
                                <option value="Active">Active</option>
                                <option value="Extended">Extended</option>
                            </select>
                            <div class="dd" data-target="#statusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">By Probation Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Probation Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">By Probation Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Active"><span class="dd-nm">Active</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Extended"><span class="dd-nm">Extended</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <input type="text" id="dateFromFilter" class="form-control datepicker" placeholder="From Date" autocomplete="off"/>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <input type="text" id="dateToFilter" class="form-control datepicker" placeholder="To Date" autocomplete="off"/>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="trainingStatusFilter">
                                <option value="">Training Status</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Absent">Absent</option>
                            </select>
                            <div class="dd" data-target="#trainingStatusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Training Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Training Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Training Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Not Started"><span class="dd-nm">Not Started</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="In Progress"><span class="dd-nm">In Progress</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Completed"><span class="dd-nm">Completed</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Absent"><span class="dd-nm">Absent</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            @php
                                $filterMonths = [];
                                for ($i = 0; $i < 12; $i++) {
                                    $filterMonths[] = \Carbon\Carbon::now()->subMonthsNoOverflow($i);
                                }
                            @endphp
                            <select id="filter_month" class="form-control dd-native-select">
                                <option value="">All Months</option>
                                @foreach ($filterMonths as $monthDate)
                                    <option value="{{ $monthDate->format('Y-m') }}">{{ $monthDate->format('F Y') }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#filter_month">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Months</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Month">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Months</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach ($filterMonths as $monthDate)
                                            <div class="dd-item" role="option" data-value="{{ $monthDate->format('Y-m') }}"><span class="dd-nm">{{ $monthDate->format('F Y') }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="probationList" class="table data-Table  table-peopleProbationList w-100">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Joining Date</th>
                            <th>Probation End Date</th>
                            <th>Onboarding Training</th>
                            <th>Monthly Check-in Status</th>
                            <th>Probation Review Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="extendProbationModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="extendProbationForm">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title">Extend Probation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="emp_id" id="extendEmpId">
                <div class="mb-3">
                    <label for="extension_date">New End Date</label>
                    <input type="text" class="form-control datepicker" name="extension_date" required>
                </div>
                <div class="mb-3">
                    <label for="remarks">Remarks (optional)</label>
                    <textarea class="form-control" name="remarks"></textarea>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn eb-btn-secondary">Extend</button>
                </div>
            </div>
            </form>
        </div>
    </div>

@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.select2t-none').select2();
    
        flatpickr(".datepicker", {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
        getProbationaryData();

        $('#searchInput, #dateFromFilter, #dateToFilter, #statusFilter, #deptFilter, #positionFilter, #trainingStatusFilter').on('keyup change', function () {
            getProbationaryData();
        });

        $('#filter_month').change(function () {
            getProbationaryData();
        });
        // Confirm Probation
        $(document).on('click', '.confirm-probation', function () {
            let id = $(this).data('id');

            wisdomConfirm({
                role: 'confirm',
                title: 'Confirm Probation',
                extra: {
                    html: `
                        <p>Select new Employment Type:</p>
                        <select id="employmentTypeSelect" class="swal2-input select2t-none" style="width: 100%; padding: 5px;">
                            <option value="">-- Select Type --</option>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contract">Contract</option>
                            <option value="Casual">Casual</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Internship">Internship</option>
                            <option value="Temporary">Temporary</option>
                        </select>
                    `,
                    preConfirm: () => {
                        const selected = $('#employmentTypeSelect').val();
                        if (!selected) {
                            Swal.showValidationMessage('Please select an employment type');
                        }
                        return selected;
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newType = result.value;

                    $.ajax({
                        url: '{{ route("confirm.probation", ["id" => "___ID___"]) }}'.replace('___ID___', id),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            employment_type: newType
                        },
                        success: function (response) {
                            toastr.success(response.message || 'Probation confirmed & employment type updated.', "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#probationList').DataTable().ajax.reload(null, false);
                        },
                        error: function (xhr) {
                            toastr.error('Something went wrong!','Error',{
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
        });

        // Fail Probation
        $(document).on('click', '.fail-probation', function () {
            let id = $(this).data('id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Fail Probation',
                confirmText: 'Submit',
                extra: {
                    html: `
                        <p>You are about to mark this probation as <strong>Failed</strong>.</p>
                        <textarea id="fail_remarks" class="swal2-textarea" placeholder="Enter reason for failure..."></textarea>
                    `,
                    preConfirm: () => {
                        const remarks = document.getElementById('fail_remarks').value;
                        if (!remarks.trim()) {
                            Swal.showValidationMessage('Remarks are required!');
                        }
                        return remarks;
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const remarks = result.value;

                    $.ajax({
                        url: '{{ route("fail.probation", ["id" => "___ID___"]) }}'.replace('___ID___', id),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: remarks
                        },
                        success: function (response) {
                            toastr.success(response.message || 'Probation has been marked as failed.', "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#probationList').DataTable().ajax.reload(null, false);
                        },
                        error: function () {
                            toastr.error(response.message || 'Something went wrong.', "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.extend-probation', function () {
            const id = $(this).data('id');
            $('#extendEmpId').val(id);
            $('#extendProbationModal').modal('show');
        });

        $('#extendProbationForm').on('submit', function (e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const id = $('#extendEmpId').val();

            $.ajax({
                url: '{{ route("extend.probation", ["id" => "___ID___"]) }}'.replace('___ID___', id),
                type: 'POST',
                data: formData,
                success: function (res) {
                    $('#extendProbationModal').modal('hide');
                    toastr.success(res.message , "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    $('#probationList').DataTable().ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error(response.message || 'Something went wrong.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });               
                }
            });
        });
    });

    function getProbationaryData() {
        if ($.fn.dataTable.isDataTable('#probationList')) {
            $('#probationList').DataTable().destroy();
        }
        $('#probationList').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[10, 'desc']],
            ajax: {
                url: '{{ route("people.probation") }}',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.searchTerm = $('#searchInput').val();
                    d.status = $('#statusFilter').val();
                    d.trainingStatus = $('#trainingStatusFilter').val();
                    d.month = $('#filter_month').val();  // month filter (YYYY-MM); '' = All Months

                    // Date-range filter on probation end date.
                    const toYmd = function (v) {
                        if (!v) return '';
                        const p = v.split('/');
                        return p.length === 3
                            ? `${p[2]}-${p[1].padStart(2, '0')}-${p[0].padStart(2, '0')}`
                            : '';
                    };
                    d.date_from = toYmd($('#dateFromFilter').val());
                    d.date_to = toYmd($('#dateToFilter').val());
                }
            },
            columns: [
                { data: 'employee_id', name: 'employee_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'position', name: 'position.name' },
                { data: 'department', name: 'department.name' },
                { data: 'joining_date', name: 'joining_date' },
                { data: 'probation_end_date', name: 'probation_end_date' },
                { data: 'onboarding_training', name: 'onboarding_training', orderable: false, searchable: false },
                { data: 'monthly_checkin_status', name: 'monthly_checkin_status', orderable: false, searchable: false },
                { data: 'review_status' },
                { data: 'actions', orderable: false, searchable: false },
                { data: 'created_at', visible: false, searchable: false },

            ]
        });
    }
</script>
@include('resorts._dropdown_script')
@endsection
