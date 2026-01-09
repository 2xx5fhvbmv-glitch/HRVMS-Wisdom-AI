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
                            <select class="form-select select2t-none" id="deptFilter">
                                <option value="">By Department</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="positionFilter">
                                <option value="">By Position</option>
                                @if($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter">
                                <option value="">By Probation Status</option>
                                <option value="Active">Active</option>
                                <option value="Extended">Extended</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6"> 
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="trainingStatusFilter">
                                <option value="">Training Status</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select id="filter_month" class="form-control">
                                @for ($i = 0; $i < 12; $i++)
                                    @php
                                        $monthDate = \Carbon\Carbon::now()->subMonthsNoOverflow($i);
                                    @endphp
                                    <option value="{{ $monthDate->format('Y-m') }}">{{ $monthDate->format('F Y') }}</option>
                                @endfor
                            </select>
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
                    <button type="submit" class="btn btn-warning">Extend</button>
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
    $(document).ready(function () {
        $('.select2t-none').select2();
    
        $(".datepicker").datepicker({
            format: 'dd/mm/yyyy', 
            autoclose: true,   // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });
        getProbationaryData();

        $('#searchInput, #dateFilter, #statusFilter, #deptFilter, #positionFilter, #trainingStatusFilter').on('keyup change', function () {
            getProbationaryData();
        });

        $('#filter_month').change(function () {
            getProbationaryData();
        });
        // Confirm Probation
        $(document).on('click', '.confirm-probation', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'Confirm Probation',
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
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm',
                preConfirm: () => {
                    const selected = $('#employmentTypeSelect').val();
                    if (!selected) {
                        Swal.showValidationMessage('Please select an employment type');
                    }
                    return selected;
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

            Swal.fire({
                title: 'Fail Probation',
                html: `
                    <p>You are about to mark this probation as <strong>Failed</strong>.</p>
                    <textarea id="fail_remarks" class="swal2-textarea" placeholder="Enter reason for failure..."></textarea>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const remarks = document.getElementById('fail_remarks').value;
                    if (!remarks.trim()) {
                        Swal.showValidationMessage('Remarks are required!');
                    }
                    return remarks;
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
                    d.month = $('#filter_month').val();  // new month filter (YYYY-MM)

                    let selectedDate = $('#dateFilter').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    } else {
                        d.date = '';
                    }
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
@endsection
