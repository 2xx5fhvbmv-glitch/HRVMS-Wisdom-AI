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
                                <input type="search" class="form-control "
                                    placeholder="Search by Employee Name, ID or Manager Name" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="deptFilter" data-placeholder="By Department">
                                <option value=""></option>
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach             
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="positionFilter" data-placeholder="By Position">
                                <option value=""></option>
                                @if($positions)
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter" data-placeholder="By Status">
                                <option></option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" class="form-control datepicker" id="datapicker" data-placeholder="Date Range"/>
                        </div>
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
                            <label for="meetingVenue" class="form-label">Meeting venue <span class="req_span">*</span></label>
                            <input type="text" class="form-control" id="meetingVenue" name="meetingVenue" placeholder="Enter Meeting Venue('ex: Google Meet,Zoom,Vertual')" required> 
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Schedule Meeting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
    
@endsection

@section('import-scripts')
 <script>

    $(document).ready(function(){        
        getExitClearanceData();
        $('.select2t-none').select2();
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            startDate: new Date() // Restrict to upcoming dates only
        });

        $('#deptFilter, #positionFilter, #statusFilter, #datapicker').on('change', function () {
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
            "order": [[8, 'desc']],
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('people.employee-resignation.index') }}",
                type: 'GET',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.status = $('#statusFilter').val();
                    d.date_range = $('#datapicker').val();
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
@endsection
