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
                            <select class="form-select select2t-none" id="trainingFilter">
                                <option value="">Select Training</option>
                                @if($trainings)
                                    @foreach($trainings as $training)
                                        <option value="{{ $training->id }}" {{ $scheduleId == $training->id ? 'selected' : '' }}>
                                            {{ $training->learningProgram->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-xl-5 col-lg-3 col-md-2 col-sm-6 ms-auto text-end">
                            <button id="mark-attendance-btn" class="btn btn-themeBlue btn-sm">Mark Attendance</button>
                        </div>
                    </div>
                </div>
                <table id="table-attendTrack" class="table data-Table table-attendTrack w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Emp ID</th>
                            <th>Name </th>
                            <th>Position</th>
                            <th>Training Name</th>
                            <th>Training Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Attendance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
  
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="attendanceModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="trainingScheduleId">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceModalBody"></tbody>
                    </table>
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
        getAttendeesList();
        $('#searchInput, #trainingFilter').on('keyup change', function () {
            getAttendeesList();
        });

        $('#mark-attendance-btn').on('click', function() {
            let selectedEmployees = [];
            $('.attendance-checkbox:checked').each(function() {
                let employeeId = $(this).data('employee-id');
                let trainingScheduleId = $(this).data('training-id');
               
                let employeeName = $(this).closest('tr').find('.userReviewTasks-btn').text();
                selectedEmployees.push({ id: employeeId, name: employeeName });
                $('#trainingScheduleId').val(trainingScheduleId);
            });

            if (selectedEmployees.length === 0) {
                toastr.error("Please select at least one employee.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Populate modal with employees
            let modalBody = $('#attendanceModalBody');
            modalBody.empty();
            selectedEmployees.forEach(emp => {
                modalBody.append(`
                    <tr>
                        <td>${emp.name}</td>
                        <td>
                            <select class="form-select attendance-status" data-employee-id="${emp.id}">
                                <option value="Present">Present</option>
                                <option value="Absent">Absent</option>
                                <option value="Late">Late</option>
                            </select>
                        </td>
                    </tr>
                `);
            });

            // Open the modal
            $('#attendanceModal').modal('show');
        });

        $('#save-attendance').on('click', function() {
            let trainingScheduleId = $('#trainingScheduleId').val();
            
            if (!trainingScheduleId) {
                alert("Error: Training Schedule ID is missing!");
                return;
            }

            let employees = [];
            $('.attendance-status').each(function() {
                employees.push({
                    employee_id: $(this).data('employee-id'),
                    status: $(this).val()
                });
            });

            $.ajax({
                url: "{{ route('attendance.mark') }}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: JSON.stringify({
                    training_schedule_id: trainingScheduleId,
                    employees: employees
                }),
                contentType: "application/json",
                success: function(response) {
                    if(response.success === false) {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        return;
                        $('#attendanceModal').modal('hide');
                    }
                    $('#attendanceModal').modal('hide');
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });   
                },
                error: function(xhr) {
                    let errs = xhr.responseJSON?.message || 'An unexpected error occurred. Please try again.';
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
    });

    function getAttendeesList() {
        if ($.fn.DataTable.isDataTable('#table-attendTrack')) {
            $('#table-attendTrack').DataTable().destroy();
        }

        $('#table-attendTrack').DataTable({
            searching: false,
            lengthChange: false,
            filter: true,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 6,
            processing: true,
            serverSide: false, // Since we are returning pre-processed data
            order:[[12, 'desc']],
            ajax: {
                url: '{{ route("learning.schedule.attendance.list") }}',
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.training = $('#trainingFilter').val();
                }
            },
            columns: [
                { data: 'checkbox', name: 'Select', orderable: false, searchable: false },
                { data: 'Emp_ID', name: 'Emp_ID' },
                { data: 'employee_name', name: 'Name', orderable: false },
                { data: 'position', name: 'Position' },
                { data: 'training_name', name: 'Training Name' },
                { data: 'training_type', name: 'Training Type' },
                { data: 'start_date', name: 'Start Date' },
                { data: 'end_date', name: 'End Date' },
                { data: 'start_time', name: 'Start Time' },
                { data: 'end_time', name: 'End Time' },
                { data: 'attendance', name: 'Attendance' },
                { data: 'action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
   
</script>
@endsection