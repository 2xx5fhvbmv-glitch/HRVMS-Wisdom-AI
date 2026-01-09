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
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <select id="statusFilter" class="form-select select2t-none">
                                <option value=""> All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Denied">Denied</option>
                                <option value="On Hold">On Hold</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- data-Table -->
                <div class="table-responsive mb-md-3 mb-2">
                    <table id="table-learning-request" class="table table-LearningProgram w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Learning Name</th>
                                <th>Suggested Employees</th>
                                <th>Reason</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Rejection Modal -->
    <div id="statusModal"  class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="statusRequestId">
                    <input type="hidden" id="statusType">
                    <div class="form-group">
                        <label for="statusReason">Reason (Optional):</label>
                        <textarea class="form-control" id="statusReason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitStatusChange()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        loadLearningRequests();

        $('#searchInput, #statusFilter').on('keyup change', function () {
            loadLearningRequests();
        });
    });

    function loadLearningRequests() {
        if ($.fn.DataTable.isDataTable('#table-learning-request')) {
            $('#table-learning-request').DataTable().destroy();
        }

        let isManager = {{ $isManager ? 'true' : 'false' }}; // Get role from backend
        console.log(isManager);

        $('#table-learning-request').DataTable({
            searching: false,
            lengthChange: false,
            filter: true,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 6,
            processing: true,
            serverSide: true,
            order: [[7, 'desc']], 
            ajax: {
                url: '{{ route("learning.request.list") }}',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.status = $('#statusFilter').val();
                },
                type: 'GET',
            },
            columns: [
                { data: 'learning_name', name: 'Learning Name', className: 'text-nowrap' },
                { data: 'employees', name: 'Suggested Employees', className: 'text-nowrap' },
                { data: 'reason', name: 'Reason', className: 'text-nowrap' },
                { data: 'start_date', name: 'Start Date', className: 'text-nowrap' },
                { data: 'end_date', name: 'End Date', className: 'text-nowrap' },
                { data: 'status', name: 'Status', className: 'text-nowrap' },
                { 
                    data: 'action', 
                    name: 'Action', 
                    className: 'text-nowrap',
                    visible: isManager // Only show if the user is a manager
                },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }

    // Function to open modal for Deny or On Hold
    function rejectLearningRequest(requestId) {
        $('#statusRequestId').val(requestId);
        $('#statusType').val('Denied');
        $('#statusModalLabel').text("Enter Denial Reason");
        $('#statusModal').modal('show');
    }

    function putOnHold(requestId) {
        $('#statusRequestId').val(requestId);
        $('#statusType').val('On Hold');
        $('#statusModalLabel').text("Enter On Hold Reason");
        $('#statusModal').modal('show');
    }

    // Function to submit the status change
    function submitStatusChange() {
        var requestId = $('#statusRequestId').val();
        var status = $('#statusType').val();
        var reason = $('#statusReason').val();

        $.ajax({
            url: '{{ route("learning.request.updateStatus") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                request_id: requestId,
                status: status,
                reason: reason
            },
            success: function(response) {
                $('#statusModal').modal('hide');
                $('#table-learning-request').DataTable().ajax.reload();
                toastr.success("Status updated successfully!", "Success",
                {
                    positionClass: 'toast-bottom-right'
                });
            },
            error: function(xhr) {
                let errs = xhr.responseJSON.error || 'An unexpected error occurred. Please try again.';
                toastr.error(errs, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            },
        });
    }

    // Function to approve learning request
    function updateLearningRequestStatus(requestId, status) {
        $.ajax({
            url: '{{ route("learning.request.updateStatus") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                request_id: requestId,
                status: status
            },
            success: function(response) {
                $('#table-learning-request').DataTable().ajax.reload();
                toastr.success("Learning request " + status + " successfully!", "Success",
                {
                    positionClass: 'toast-bottom-right'
                });
            },
            error: function(xhr) {
                let errs = xhr.responseJSON.error || 'An unexpected error occurred. Please try again.';
                toastr.error(errs, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            },
        });
    }


</script>
@endsection