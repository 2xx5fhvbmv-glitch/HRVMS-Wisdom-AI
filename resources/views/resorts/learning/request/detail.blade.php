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
            <div class="card appealReviewDecision-card">
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="row g-lg-5 g-sm-4 g-3">
                        <div class="col-lg-6">
                            <div class="table-responsive  mb-2">
                                <table class="table-lableSmallLabel">
                                    <tr>
                                        <th>Request By:</th>
                                        <td>{{$createdBy->full_name}}</td>
                                    </tr>
                                    <tr>
                                        <th>Training Title:</th>
                                        <td>{{$request_detail->learning->name}}</td>
                                    </tr>
                                    <tr>
                                        <th>Reason</th>
                                        <td>{{$request_detail->reason}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Start Date:</th>
                                        <td>{{$request_detail->start_date}}</td>
                                    </tr>
                                    <tr>
                                        <th>End Date:</th>
                                        <td>{{$request_detail->end_date}}</td>
                                    </tr>
                                </table>
                            </div>
                            <h6>Description:</h6>
                            <p>{{$request_detail->learning->description}}</p>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="mb-md-3 mb-2">Employee Details:</h6>
                            
                            @if($request_detail->employees)
                                @foreach($request_detail->employees as $emp)
                                    <div class="d-flex align-items-center mb-md-2 mb-1">
                                        <div class="img-circle me-2">
                                            <img src="{{Common::getResortUserPicture($emp->employee->resortAdmin->id)}}" alt="image">
                                        </div>
                                        <h6 class="mb-0">{{$emp->employee->resortAdmin->full_name}}</h6> 
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mb-md-4 mb-3">
                    <button class="btn btn-themeBlue btn-sm" onclick="updateLearningRequestStatus('{{ $request_detail->id }}', 'Approved')">Approve</button>
                    <button class="btn btn-sm btn-warning" onclick="updateLearningRequestStatus('{{ $request_detail->id }}', 'On Hold')">On Hold</button>
                    <button class="btn btn-danger btn-sm" onclick="rejectLearningRequest('{{ $request_detail->id }}')">Deny</button>
                </div>
            </div>
        </div>
    </div>

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
<script>
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