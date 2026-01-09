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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-lg">
                            <div class="empDetails-user">
                                <div class="img-circle">
                                    <img src="{{ $leaveDetail->employee_profile_picture ? $leaveDetail->employee_profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="image">
                                </div>
                                <div>
                                    <input type="hidden" name="empID" id="empID" value="{{$empID}}"/>
                                    <h4>{{$leaveDetail->employee_first_name}} {{$leaveDetail->employee_last_name}} </h4>
                                    <p>{{$leaveDetail->position}}</p>
                                </div>
                            </div>
                        </div>
                        @php $total_leaves_allocated = 0 ;$total_taken_laves = 0; @endphp
                            @if($leaveBalances)
                                @foreach($leaveBalances as $leaves)
                                    @php 
                                        $total_leaves_allocated = $total_leaves_allocated +  $leaves->allocated_days;
                                        $total_taken_laves = $total_taken_laves +  $leaves->available_days;
                                    @endphp   
                                @endforeach
                            @endif
                            <div class="col-auto ms-auto">
                                <div class="employee-bg">
                                <p>Total Leave Balance: {{ $total_taken_laves }}/<span>{{ $total_leaves_allocated }}</span></p>
                                </div>
                            </div>
                            <div class="col-auto">
                            <a href="{{ route('leave.history.download-pdf', ['empID' => base64_encode($empID)]) }}" class="btn btn-themeSkyblue btn-sm">Download PDF</a>

                            </div>
                    </div>
                </div>
                <div class="employee-progressMain mb-lg-4 mb-2">
                    @if($leaveBalances)
                        @foreach($leaveBalances as $balance)
                            @php
                                // Calculate progress percentage
                                $progressPercentage = ($balance->allocated_days > 0) 
                                                    ? ($balance->used_days / $balance->allocated_days) * 100 
                                                    : 0;
                                $progressPercentage = round($progressPercentage, 2); // Round to 2 decimals
                                $progressColor = $balance->color ?? '#ff0000'; // Default to red if color not found
                            @endphp
                            <div>
                                <div class="progress-container" data-progress="{{ $progressPercentage }}">
                                    <svg class="progress-circle" viewBox="0 0 120 120">
                                        <circle class="progress-background" cx="60" cy="60" r="54" style="stroke: #f0f0f0;"></circle>
                                        <circle class="progress" cx="60" cy="60" r="54" 
                                                style="stroke: {{ $progressColor }}; stroke-dasharray: 339.29; stroke-dashoffset: {{ 339.29 - (339.29 * $progressPercentage / 100) }};"></circle>
                                    </svg>
                                    <div class="progress-text">
                                        {{ $balance->used_days }}/<span>{{ $balance->allocated_days }}</span>
                                    </div>
                                </div>
                                <h6>{{ $balance->leave_type }}</h6>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="card bg leaveReq-card mb-4">
                    <div class="card-title ">
                        <h3>Leave Request</h3>
                    </div>
                    <div class="bg-white mb-4">
                        <div class="row g-4">
                            <div class="col">
                                <div class="row g-3 h-100 mt-lg-0">
                                    <div class="col-md-auto">
                                        <div class="bg-themeGrayLight appleaveReq-block">
                                            <i>Applied On: {{ \Carbon\Carbon::parse($leaveDetail->created_at)->format('d M, Y') }}</i>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <div class="date-block">
                                                    {{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('M') }}
                                                    <h5>{{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('d') }}</h5>
                                                    {{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('D') }}
                                                </div>
                                               
                                                @if(isset($leaveDetail->combinedLeave))
                                                    <div>
                                                        <img src="{{ URL::asset('resorts_assets/images/arrow.svg')}}" alt="">{{ $leaveDetail->total_days + $leaveDetail->combinedLeave->total_days}} days
                                                    </div>
                                                    <div class="date-block">
                                                    {{ \Carbon\Carbon::parse($leaveDetail->combinedLeave->to_date)->format('M') }}
                                                        <h5>{{ \Carbon\Carbon::parse($leaveDetail->combinedLeave->to_date)->format('d') }}</h5>
                                                        {{ \Carbon\Carbon::parse($leaveDetail->combinedLeave->to_date)->format('D') }}
                                                    </div>
                                                @else
                                                    <div>
                                                        <img src="{{ URL::asset('resorts_assets/images/arrow.svg')}}" alt="">{{ $leaveDetail->total_days }} days
                                                    </div>
                                                    <div class="date-block">
                                                    {{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('M') }}
                                                        <h5>{{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('d') }}</h5>
                                                        {{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('D') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if(isset($leaveDetail->combinedLeave))
                                        <div class="col">
                                            <span class="badge mb-2 border-0" style="color:{{$leaveDetail->color}}; background:{{$leaveDetail->color}}1F;">
                                                {{ $leaveDetail->leave_type ?? 'N/A' }}
                                            </span>
                                            <span class="badge mb-2 border-0" style="color:{{$leaveDetail->combinedLeave->color}}; background:{{$leaveDetail->combinedLeave->color}}1F;">
                                                {{ $leaveDetail->combinedLeave->leave_type ?? 'N/A' }}
                                            </span>
                                            <p>{{ $leaveDetail->reason ?? 'No reason provided' }}</p>
                                        </div>
                                    @else
                                        <div class="col">
                                            <span class="badge mb-2 border-0" style="color:{{$leaveDetail->color}}; background:{{$leaveDetail->color}}1F;">
                                                {{ $leaveDetail->leave_type ?? 'N/A' }}
                                            </span>
                                            <p>{{ $leaveDetail->reason ?? 'No reason provided' }}</p>
                                        </div>
                                    @endif
                                    <div class="col-12">
                                        <table class="table-lableNew w-100 mb-4">
                                            <tr>
                                                <th>Attachment:</th>
                                                <td>
                                                    @if ($leaveDetail->attachments)
                                                        <a href="{{ URL::asset($leaveDetail->attachments) }}" target="_blank">
                                                            <img src="{{ URL::asset('resorts_assets/images/pdf1.svg') }}" alt="icon" class="me-2">
                                                        </a>
                                                        <a href="{{ URL::asset($leaveDetail->attachments) }}" class="a-link" target="_blank">View</a>
                                                    @else
                                                        No attachment available
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Task delegation:</th>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle">
                                                            <img src="{{ $leaveDetail->task_delegation_profile_picture ? $leaveDetail->task_delegation_profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">{{$leaveDetail->task_delegation_first_name}} {{$leaveDetail->task_delegation_last_name}}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Destination:</th>
                                                <td>{{$leaveDetail->destination}}</td>
                                            </tr>
                                            <tr>
                                                <th>Transportation:</th>
                                                <td>{{$leaveDetail->transportation ?? "No Provided"}}</td>
                                            </tr>
                                        </table>
                                    </div>

                                    @php
                                        $leaveStatus = App\Models\EmployeeLeaveStatus::where('leave_request_id', $leaveDetail->id)->where('status','Pending')->where('approver_id',$employee->id)->first();
                                    @endphp
                                    <div class="col-12 mt-auto">
                                        <div class="card-footer">
                                            <div class="row align-items-center g-xxl-3 g-2">
                                                @if($leaveStatus)
                                                    
                                                    <div class="col-auto"> 
                                                        <button class="btn btn-themeBlue btn-sm approve-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{$leaveDetail->id}}">Approve</button>
                                                    </div>
                                                    <div class="col-auto">
                                                        <button class="btn btn-danger btn-sm reject-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{$leaveDetail->id}}">Reject</button>
                                                    </div>
                                                    
                                                    <div class="col-auto"> 
                                                        <a href="#" class="a-linkTheme  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" id="recommendDateBtn" data-leave-id="{{$leaveDetail->id}}">Recommend an alternative date</a>
                                                    </div>
                                                @endif

                                                @if($available_rank == "HR")
                                                    <div class="col-auto ms-auto">
                                                        <button href="#" data-leave-id="{{$leaveDetail->id}}" id="sentEmailToTravelPartner" class="btn btn-themeSkyblue btn-sm">Send Email To Travel Partner</button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="col-lg-auto">
                                <div class="leaveReqTicket-main">
                                    <div class="ratio cover">
                                        <img src="{{ URL::asset('resorts_assets/images/ticket.png')}}" alt="image">
                                        <div>
                                            <h5>INDIGO</h5>
                                            <span>ECONOMY</span>
                                        </div>
                                        <div class="d-flex">
                                            <div>
                                                <h6>Maldives</h6>
                                                <h3>MAL</h3>
                                            </div>
                                            <div class="dash"></div>
                                            <img src="{{ URL::asset('resorts_assets/images/plane-group.png')}}" alt="icon">
                                            <div class="dash"></div>
                                            <div>
                                                <h6>India</h6>
                                                <h3>BOM</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="leaveReqTicket-name">
                                            <h5>John Doe</h5>
                                            <span class="badge badge-white">Round</span>
                                        </div>
                                        <div class="leaveReqTicket-block">
                                            <div>
                                                <span>TIME</span>
                                                <h6>14:05</h6>
                                                <p>03, Oct, Sun 2024</p>
                                            </div>
                                            <div>
                                                <img src="{{ URL::asset('resorts_assets/images/plane-white.png')}}" alt="icon">
                                                <h6>3h 00 mm</h6>
                                                <p>6E 1132</p>
                                            </div>
                                            <div>
                                                <span>LAND</span>
                                                <h6>17:35</h6>
                                                <p>03, Oct, Sun 2024</p>
                                            </div>
                                        </div>
                                        <div class="leaveReqTicket-block">
                                            <div>
                                                <span>TIME</span>
                                                <h6>10:40</h6>
                                                <p>03, Oct, Sun 2024</p>
                                            </div>
                                            <div>
                                                <img src="{{ URL::asset('resorts_assets/images/plane-white.png')}}" alt="icon">
                                                <h6>2h 50 mm</h6>
                                                <p>6E 1132</p>
                                            </div>
                                            <div>
                                                <span>LAND</span>
                                                <h6>13:00</h6>
                                                <p>03, Oct, Sun 2024</p>
                                            </div>
                                        </div>
                                        <div class="total">Ticket Cost: $800.00</div>
                                    </div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col">
                            <div class="card-title  pb-0 mb-0 border-0">
                                <h3>Leave History</h3>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <div class="form-group">
                                <select class="form-select select2t-none" id="category-filter" name="category" aria-label="Default select example">
                                    <option selected="" value="">All Category</option>
                                    @if($leave_categories)
                                        @foreach($leave_categories as $category)
                                            <option value="{{$category->id}}">{{$category->leave_type}}</option>
                                        @endforeach
                                    @endif                                    
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="table table-leaveHistory " id="table-leaveHistory">
                    <thead>
                        <tr>
                            <th>Leave Category</th>
                            <th>Reason</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Total Days</th>
                            <th>Attachment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be dynamically populated -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal HTML -->
    <div class="modal fade" id="recommendDateModal" tabindex="-1" role="dialog" aria-labelledby="recommendDateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recommendDateModalLabel">Suggest Alternative Dates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="recommendDateForm">
                        <input type="hidden" name="leave_id" id="leaveId">
                        <div class="mb-3">
                            <label for="altStartDate" class="form-label">Alternative Start Date <span class="req_span">*</span></label>
                            <input type="text" class="form-control datepicker" id="altStartDate" name="alt_start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="altEndDate" class="form-label">Alternative End Date <span class="req_span">*</span></label>
                            <input type="text" class="form-control datepicker" id="altEndDate" name="alt_end_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Reason/Comments <span class="req_span">*</span></label>
                            <textarea class="form-control" id="comments" name="comments" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="rejectionModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="rejectionReason" class="form-control" rows="3" placeholder="Enter a reason (optional)"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRejectBtn" class="btn btn-danger">Reject</button>
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
        $('.datepicker').datepicker({

        });
        $('#table-leaveHistory').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                var leave_catId = $('#category-filter').val();
                var empID = $('#empID').val();

                $.ajax({
                    url: '{{ route("leave.history") }}',
                    type: 'GET',
                    data: {
                        leave_catId: leave_catId,
                        empID: empID,
                        start: data.start, // Starting record for pagination
                        length: data.length, // Number of records to fetch
                        draw: data.draw // DataTables draw counter
                    },
                    success: function (response) {
                        callback({
                            draw: response.draw,
                            recordsTotal: response.recordsTotal,
                            recordsFiltered: response.recordsFiltered,
                            data: response.data
                        });
                    },
                    error: function () {
                        console.error('Error fetching data from server.');
                    }
                });
            },
            columns: [
                { data: 'leave_category', name: 'leave_category' },
                { data: 'reason', name: 'reason' },
                { data: 'from_date', name: 'from_date' },
                { data: 'to_date', name: 'to_date' },
                { data: 'total_days', name: 'total_days' },
                {
                    data: 'attachments',
                    render: function (data) {
                        return data
                            ? `<a href="${data}" target="_blank"><img src="/resorts_assets/images/pdf1.svg" alt="attachment"></a>`
                            : 'No Attachment';
                    }
                },
                {
                    data: 'status',
                    render: function(data, type, row) {
                        let statusClass = 'badge-secondary'; // Default class

                        // Check for specific keywords in the status text and assign the appropriate class
                        if (row.status_text.includes('Approved')) {
                            statusClass = 'badge-themeSuccess'; // Green for approved
                        } else if (row.status_text.includes('Rejected')) {
                            statusClass = 'badge-themeDanger'; // Red for rejected
                        } else if (row.status_text.includes('Pending')) {
                            statusClass = 'badge-themeWarning'; // Yellow for pending
                        }

                        // Render the badge with the dynamic class and status text
                        return `<span class="badge ${statusClass}">${row.status_text}</span>`;
                    }
                }
            ]
        });

        // Refresh table on category filter change
        $('#category-filter').on('change', function () {
            $('#table-leaveHistory').DataTable().ajax.reload();
        });

        let currentLeaveId = null; // To track the leave ID being rejected

        $('.approve-btn').on('click', function () {
            const leaveId = $(this).data('leave-id');

            // Perform the approval action
            handleLeaveAction(leaveId, 'Approved', '');
        });

        $('.reject-btn').on('click', function () {
            currentLeaveId = $(this).data('leave-id');

            // Show the rejection modal
            $('#rejectionModal').modal('show');
        });

        $('#confirmRejectBtn').on('click', function () {
            const reason = $('#rejectionReason').val(); // Get the reason (optional)

            // Perform the rejection action
            handleLeaveAction(currentLeaveId, 'Rejected', reason);

            // Hide the modal
            $('#rejectionModal').modal('hide');
            $('#rejectionReason').val(''); // Clear the input for the next use
        });

        function handleLeaveAction(leaveId, action, reason) {
            $.ajax({
                url: "{{route('leave.handleAction')}}",
                method: 'POST',
                data: {
                    leave_id: leaveId,
                    action: action,
                    reason: reason,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    // console.log(response);
                    if (response.status == "success") {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        let response = xhr.responseJSON;
                        if (response.status == "error") {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    } else {
                        toastr.error("An unexpected error occurred. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        }

        // Open the modal and set the leave ID
        $('#recommendDateBtn').on('click', function (e) {
            e.preventDefault();
            const leaveId = $(this).data('leave-id'); // Pass the leave ID dynamically
            $('#leaveId').val(leaveId);
            $('#recommendDateModal').modal('show');
        });

        // Handle form submission
        $('#recommendDateForm').on('submit', function (e) {
            e.preventDefault();

            const formData = $(this).serialize();

            $.ajax({
                url: "{{ route('leave.recommendAlternativeDate') }}", // Backend route
                method: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    if (response.status === "success") {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right',
                        });
                        $('#recommendDateModal').modal('hide');
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right',
                        });
                    }
                },
                error: function () {
                    toastr.error("An unexpected error occurred.", "Error", {
                        positionClass: 'toast-bottom-right',
                    });
                },
            });
        });

        $('#sentEmailToTravelPartner').on('click',function(e){
            const leaveId = $(this).data('leave-id'); // Pass the leave ID dynamically
            // console.log(leaveId);
            $.ajax({
                url: "{{ route('send.email.to.travel.partner') }}", // Backend route
                method: "POST",
                data:{leaveId : leaveId},
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right',
                    });
                },
                error: function () {
                    toastr.error("An unexpected error occurred.", "Error", {
                        positionClass: 'toast-bottom-right',
                    });
                },
            })
        });
    });

    // progress 
    const radius = 54; // Circle radius
    const circumference = 2 * Math.PI * radius; // The circumference of the circle

    // Select all progress containers
    const progressContainers = document.querySelectorAll('.progress-container');

    progressContainers.forEach(container => {
        const progressCircle = container.querySelector('.progress');
        // const progressText = container.querySelector('.progress-text');
        const progressValue = container.getAttribute('data-progress'); // Get the progress value from the container's data attribute
        const offset = circumference - (progressValue / 100 * circumference); // Calculate the offset

        // Set the initial stroke-dashoffset to the full circumference
        progressCircle.style.strokeDashoffset = circumference;

        // Use a small timeout to allow the browser to render the initial state before applying the offset (to trigger the animation)
        setTimeout(() => {
            // Apply the calculated offset to the progress bar with animation
            progressCircle.style.strokeDashoffset = offset;

            // Update the text inside the circle
            // progressText.textContent = `${progressValue}%`;
        }, 100); // A small delay to trigger the animation smoothly
    });
</script>
@endsection