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
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>     
                        
                        <div class="col-xl-2 col-lg-4 col-md-5  col-sm-6">
                            <select class="form-select select2t-none" id="statusFilter">
                                <option value="">Select Status</option>
                                <option value="Absent">Absent</option>
                                <option value="Late">Late</option>
                                <option value="Present">Present</option>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <select id="typeFilter" class="form-select select2t-none">
                                <option value=""> By Learning Type</option>
                                <option value="face-to-face">Face-to-Face</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="online">Online</option>        
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                    </div>
                   
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id) ?? 'default-profile.png'}}" 
                                alt="Employee Picture" class="rounded-circle" width="80" height="80">
                        </div>
                        <div class="col">
                            <h5 class="mb-1">{{ $employee->resortAdmin->first_name }} {{ $employee->resortAdmin->last_name }}</h5>
                            <p class="mb-0 text-muted">Employee ID: {{ $employee->Emp_id }}</p>
                            <p class="mb-0 text-muted">Position: {{ $employee->position->position_title ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
                <table id="attendanceHistoryTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Learning Name</th>
                            <th>Learning Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div id="attendanceModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark / Edit Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="trainingScheduleId">
                    <input type="hidden" id="employeeId">

                    <!-- Attendance Date Picker -->
                    <div class="mb-3">
                        <label for="attendanceDate" class="form-label">Attendance Date</label>
                        <input type="text" id="attendanceDate" class="form-control datepicker">
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceModalBody"></tbody>
                    </table>

                    <!-- Notes Field -->
                    <div class="mb-3">
                        <label for="attendanceNotes" class="form-label">Notes</label>
                        <textarea id="attendanceNotes" class="form-control" rows="3" placeholder="Enter any remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="save-attendance">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
<style></style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        getAttendeesHistory();
        $('#searchInput, #typeFilter,#dateFilter,#statusFilter').on('keyup change', function () {
            getAttendeesHistory();
        });
        $('#attendanceDate,#dateFilter').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });
        $('#save-attendance').on('click', function() {
            let trainingScheduleId = $('#trainingScheduleId').val();
            let employeeId = $('#employeeId').val();
            let attendanceDate = $('#attendanceDate').val();
            let status = $('.attendance-status').val();
            let notes = $('#attendanceNotes').val();

            if (!attendanceDate || !status) {
                toastr.error("Please select a date and status.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }
            $('#attendanceDate').datepicker();
                $.ajax({
                    url: "{{ route('attendance.save') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        training_schedule_id: trainingScheduleId,
                        employee_id: employeeId,
                        attendance_date: attendanceDate,
                        status: status,
                        notes: notes
                    },
                    success: function(response) {
                        // alert(response.message);
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });           
                        $('#attendanceModal').modal('hide');
                        getAttendeesHistory();
                    },
                    error: function(xhr) {
                        let errorMessage = '';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });

    })
    function getAttendeesHistory() {
        if ($.fn.DataTable.isDataTable('#attendanceHistoryTable')) {
            $('#attendanceHistoryTable').DataTable().destroy();
        }

        $('#attendanceHistoryTable').DataTable({
            searching: false,
            lengthChange: false,
            filter: true,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 6,
            processing: true,
            serverSide: false,
            ajax: {
                url: "{{ route('attendance.history.data', ['employee_id' => base64_encode($employee->id)]) }}",
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.status = $('#statusFilter').val();
                    d.type = $('#typeFilter').val();
                    
                    // Convert date format from d/m/Y to Y-m-d
                    let selectedDate = $('#dateFilter').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`; // YYYY-MM-DD format
                    } else {
                        d.date = '';
                    }
                }
            },
            columns: [
                { data: 'schedule.learning_program.name', name: 'schedule.learning_program.name' },
                { data: 'schedule.learning_program.delivery_mode', name: 'schedule.learning_program.delivery_mode' },
                { data: 'attendance_date', name: 'attendance_date', render: function(data) {
                    return moment(data).format('DD MMM YYYY');
                }},
                { data: 'status', name: 'status' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return `<a href="javascript:void(0)" title="Edit" data-id="${data.id}" 
                                    data-employee-id="${data.employee_id}" 
                                    data-schedule-id="${data.training_schedule_id}" 
                                    data-date="${data.attendance_date}" 
                                    data-status="${data.status}" 
                                    data-notes="${data.notes || ''}" class="btn-lg-icon icon-bg-green me-1 edit-attendance">
                <img src="${window.location.origin}/resorts_assets/images/edit.svg" alt="Edit" class="img-fluid">
                        </a>`;
                    }
                }
            ]
        });
    }

    $(document).on('click', '.edit-attendance', function() {
        let attendanceId = $(this).data('id');
        let employeeId = $(this).data('employee-id');
        let trainingScheduleId = $(this).data('schedule-id');
        let attendanceDate = $(this).data('date');
        let status = $(this).data('status');
        let notes = $(this).data('notes');

        $('#trainingScheduleId').val(trainingScheduleId);
        $('#employeeId').val(employeeId);
        $('#attendanceDate').val(attendanceDate);
        $('#attendanceNotes').val(notes);

        // Populate dropdown for status selection
        let statusOptions = ['Present', 'Absent', 'Late'];
        let selectHTML = `<select class="form-select attendance-status">`;
        statusOptions.forEach(opt => {
            selectHTML += `<option value="${opt}" ${opt === status ? 'selected' : ''}>${opt}</option>`;
        });
        selectHTML += `</select>`;

        $('#attendanceModalBody').html(`
            <tr>
                <td>${$(this).closest('tr').find('td:first').text()}</td>
                <td>${selectHTML}</td>
            </tr>
        `);

        $('#attendanceModal').modal('show');
    });

</script>
@endsection