@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #learning-request-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #learning-request-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="learning-request-hero">
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
                            <select id="statusFilter" class="form-select dd-native-select">
                                <option value=""> All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Denied">Denied</option>
                                <option value="On Hold">On Hold</option>
                            </select>
                            <div class="dd" data-target="#statusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Pending"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Approved"><span class="dd-nm">Approved</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Denied"><span class="dd-nm">Denied</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="On Hold"><span class="dd-nm">On Hold</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
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
                                <th>Requested By</th>
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
                    <button type="button" class="btn lnd-btn-neutral" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn lnd-btn-primary" onclick="submitStatusChange()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@include('resorts.learning._learning_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
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
            order: [[8, 'desc']], // hidden created_at column index — bumped after adding "Requested By"
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
                { data: 'requested_by', name: 'Requested By', className: 'text-nowrap' },
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
            ],
            // After every redraw, (re-)init Bootstrap tooltips on the status badges
            // so the rejection_reason hover popover works for Denied / On Hold rows.
            drawCallback: function () {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    document.querySelectorAll('#table-learning-request [data-bs-toggle="tooltip"]').forEach(function (el) {
                        var existing = bootstrap.Tooltip.getInstance(el);
                        if (existing) existing.dispose();
                        new bootstrap.Tooltip(el);
                    });
                }
            }
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