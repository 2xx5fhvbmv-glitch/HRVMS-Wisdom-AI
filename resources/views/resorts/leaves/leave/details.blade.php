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
                <div class="card leave-details-card mb-4 shadow-sm border-0 overflow-hidden">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="mb-0 fw-semibold">Leave Request</h3>
                        @php
                            $statusVal = $leaveDetail->status ?? $leaveDetail->leave_status ?? 'Pending';
                            $statusClass = strtolower($statusVal) === 'approved' ? 'bg-success' : (strtolower($statusVal) === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                        @endphp
                        <span class="badge {{ $statusClass }} px-3 py-2">{{ $statusVal }}</span>
                    </div>
                    <div class="card-body p-4">
                        {{-- Leave overview --}}
                        <div class="leave-overview-card rounded-3 p-4 mb-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <span class="text-muted small">Applied on {{ \Carbon\Carbon::parse($leaveDetail->created_at)->format('d M, Y') }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-center flex-wrap gap-3 py-2">
                                <div class="leave-date-box text-center rounded-2 px-3 py-2">
                                    <div class="text-uppercase small text-muted">{{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('M') }}</div>
                                    <div class="fs-4 fw-bold">{{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('d') }}</div>
                                    <div class="small">{{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('D') }}</div>
                                </div>
                                @if(isset($leaveDetail->combinedLeave))
                                    <div class="d-flex align-items-center">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow.svg')}}" alt="" class="mx-1">
                                        <span class="fw-semibold">{{ $leaveDetail->total_days + $leaveDetail->combinedLeave->total_days }} days</span>
                                    </div>
                                    <div class="leave-date-box text-center rounded-2 px-3 py-2">
                                        <div class="text-uppercase small text-muted">{{ \Carbon\Carbon::parse($leaveDetail->combinedLeave->to_date)->format('M') }}</div>
                                        <div class="fs-4 fw-bold">{{ \Carbon\Carbon::parse($leaveDetail->combinedLeave->to_date)->format('d') }}</div>
                                        <div class="small">{{ \Carbon\Carbon::parse($leaveDetail->combinedLeave->to_date)->format('D') }}</div>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow.svg')}}" alt="" class="mx-1">
                                        <span class="fw-semibold">{{ $leaveDetail->total_days }} days</span>
                                    </div>
                                    <div class="leave-date-box text-center rounded-2 px-3 py-2">
                                        <div class="text-uppercase small text-muted">{{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('M') }}</div>
                                        <div class="fs-4 fw-bold">{{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('d') }}</div>
                                        <div class="small">{{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('D') }}</div>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-3 pt-3 border-top">
                                @if(isset($leaveDetail->combinedLeave))
                                    <span class="badge border-0 me-1 mb-1" style="color:{{$leaveDetail->color}}; background:{{$leaveDetail->color}}22;">{{ $leaveDetail->leave_type ?? 'N/A' }}</span>
                                    <span class="badge border-0 mb-1" style="color:{{$leaveDetail->combinedLeave->color}}; background:{{$leaveDetail->combinedLeave->color}}22;">{{ $leaveDetail->combinedLeave->leave_type ?? 'N/A' }}</span>
                                @else
                                    <span class="badge border-0 mb-1" style="color:{{$leaveDetail->color}}; background:{{$leaveDetail->color}}22;">{{ $leaveDetail->leave_type ?? 'N/A' }}</span>
                                @endif
                                <p class="mb-0 mt-2 text-secondary">{{ $leaveDetail->reason ?? 'No reason provided' }}</p>
                            </div>
                        </div>

                        {{-- Form details grid (col-6) --}}
                        <h6 class="text-uppercase letter-spacing text-muted mb-3">Application details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Attachment</div>
                                    @if ($leaveDetail->attachments)
                                        <a href="{{ URL::asset($leaveDetail->attachments) }}" target="_blank" class="detail-value d-inline-flex align-items-center gap-2">
                                            <img src="{{ URL::asset('resorts_assets/images/pdf1.svg') }}" alt="" width="20">
                                            <span class="a-link">View attachment</span>
                                        </a>
                                    @else
                                        <span class="detail-value text-muted">No attachment</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Task delegation</div>
                                    <div class="detail-value d-inline-flex align-items-center gap-2">
                                        <img src="{{ $leaveDetail->task_delegation_profile_picture ? $leaveDetail->task_delegation_profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;">
                                        <span>{{ $leaveDetail->task_delegation_first_name ?? '—' }} {{ $leaveDetail->task_delegation_last_name ?? '' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Destination</div>
                                    <span class="detail-value">{{ $leaveDetail->destination ?: '—' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Transportation</div>
                                    <span class="detail-value">{{ $leaveDetail->transportation_label ?? ($leaveDetail->transportation ? '—' : '—') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Leave reason</div>
                                    <span class="detail-value">{{ $leaveDetail->reason ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Departure date</div>
                                    <span class="detail-value">{{ $leaveDetail->departure_date ? \Carbon\Carbon::parse($leaveDetail->departure_date)->format('d M, Y') : '—' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Arrival date</div>
                                    <span class="detail-value">{{ $leaveDetail->arrival_date ? \Carbon\Carbon::parse($leaveDetail->arrival_date)->format('d M, Y') : '—' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">From date</div>
                                    <span class="detail-value">{{ \Carbon\Carbon::parse($leaveDetail->from_date)->format('d M, Y') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">To date</div>
                                    <span class="detail-value">{{ \Carbon\Carbon::parse($leaveDetail->to_date)->format('d M, Y') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item rounded-2 p-3 h-100">
                                    <div class="detail-label">Total days</div>
                                    <span class="detail-value fw-semibold">{{ $leaveDetail->total_days }} day(s)</span>
                                </div>
                            </div>
                        </div>

                        @if(isset($departurePass) && $departurePass)
                        <div class="mt-4 pt-4 border-top">
                            <h6 class="text-uppercase letter-spacing text-muted mb-3">Departure pass</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="detail-item rounded-2 p-3 h-100">
                                        <div class="detail-label">Departure date</div>
                                        <span class="detail-value">{{ $departurePass->departure_date ? \Carbon\Carbon::parse($departurePass->departure_date)->format('d M, Y') : '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item rounded-2 p-3 h-100">
                                        <div class="detail-label">Departure time</div>
                                        <span class="detail-value">{{ $departurePass->departure_time ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item rounded-2 p-3 h-100">
                                        <div class="detail-label">Arrival date</div>
                                        <span class="detail-value">{{ $departurePass->arrival_date ? \Carbon\Carbon::parse($departurePass->arrival_date)->format('d M, Y') : '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item rounded-2 p-3 h-100">
                                        <div class="detail-label">Arrival time</div>
                                        <span class="detail-value">{{ $departurePass->arrival_time ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item rounded-2 p-3 h-100">
                                        <div class="detail-label">Transportation</div>
                                        <span class="detail-value">{{ $departurePass->transportation_label ?? '—' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item rounded-2 p-3 h-100">
                                        <div class="detail-label">Status</div>
                                        @php
                                            $passStatus = $departurePass->status ?? 'Pending';
                                            $passStatusClass = strtolower($passStatus) === 'approved' ? 'bg-success' : (strtolower($passStatus) === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                                        @endphp
                                        <span class="badge {{ $passStatusClass }}">{{ $passStatus }}</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="detail-item rounded-2 p-3">
                                        <div class="detail-label">Reason</div>
                                        <span class="detail-value">{{ $departurePass->reason ?? $departurePass->departure_reason ?? $departurePass->arrival_reason ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($canApproveThisLeave ?? false)
                    <div class="card-footer bg-light border-top py-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button class="btn btn-themeBlue btn-sm approve-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{$leaveDetail->id}}">Approve</button>
                            <button class="btn btn-danger btn-sm reject-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{$leaveDetail->id}}">Reject</button>
                            <a href="#" class="btn btn-link btn-sm text-decoration-none" id="recommendDateBtn" data-leave-id="{{$leaveDetail->id}}">Recommend alternative date</a>
                            @if($available_rank == "HR")
                                <button type="button" data-leave-id="{{$leaveDetail->id}}" id="sentEmailToTravelPartner" class="btn btn-themeSkyblue btn-sm ms-auto">Send Email To Travel Partner</button>
                            @endif
                        </div>
                    </div>
                    @elseif($available_rank == "HR")
                    <div class="card-footer bg-light border-top py-3">
                        <div class="d-flex justify-content-end">
                            <button type="button" data-leave-id="{{$leaveDetail->id}}" id="sentEmailToTravelPartner" class="btn btn-themeSkyblue btn-sm">Send Email To Travel Partner</button>
                        </div>
                    </div>
                    @endif
                </div>
                <!-- </div> old structure -->
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
                            <th>Action</th>
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

    {{-- Leave History View Detail Modal --}}
    <div class="modal fade" id="leaveHistoryDetailModal" tabindex="-1" aria-labelledby="leaveHistoryDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="leaveHistoryDetailModalLabel">Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0" id="leaveHistoryDetailModalBody">
                    <div class="text-center py-4" id="leaveHistoryDetailLoading">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                    <div id="leaveHistoryDetailContent" class="d-none">
                        <div class="mb-3">
                            <small class="text-uppercase text-muted d-block">Leave application status</small>
                            <span id="modalLeaveStatus" class="badge bg-warning text-dark">Pending</span>
                        </div>
                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <div class="border rounded-2 p-3 text-center" style="min-width:80px;">
                                <div class="fw-bold fs-5" id="modalFromDay">—</div>
                                <small class="text-muted">From</small>
                            </div>
                            <div class="border rounded-2 p-3 text-center" style="min-width:80px;">
                                <div class="fw-bold fs-5" id="modalToDay">—</div>
                                <small class="text-muted">To</small>
                            </div>
                        </div>
                        <h6 class="text-uppercase letter-spacing text-muted mb-2">Application details</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Leave category</span><div class="detail-value" id="modalLeaveCategory">—</div></div></div>
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Reporting to</span><div class="detail-value" id="modalReportingTo">—</div></div></div>
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Reason for leave</span><div class="detail-value" id="modalReason">—</div></div></div>
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Leave from date</span><div class="detail-value" id="modalFromDate">—</div></div></div>
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Leave to date</span><div class="detail-value" id="modalToDate">—</div></div></div>
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Total days</span><div class="detail-value" id="modalTotalDays">—</div></div></div>
                            <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Attachment</span><div class="detail-value" id="modalAttachment">—</div></div></div>
                        </div>
                        <div id="modalDeparturePassSection" class="d-none">
                            <h6 class="text-uppercase letter-spacing text-muted mb-2">Departure pass</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Departure date</span><div class="detail-value" id="modalDepDate">—</div></div></div>
                                <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Departure time</span><div class="detail-value" id="modalDepTime">—</div></div></div>
                                <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Arrival date</span><div class="detail-value" id="modalArrDate">—</div></div></div>
                                <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Arrival time</span><div class="detail-value" id="modalArrTime">—</div></div></div>
                                <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Reason</span><div class="detail-value" id="modalDepReason">—</div></div></div>
                                <div class="col-md-6"><div class="detail-item rounded-2 p-2"><span class="detail-label">Status</span><div class="detail-value" id="modalDepStatus">—</div></div></div>
                            </div>
                        </div>
                        <div class="detail-item rounded-2 p-2" id="modalRemarksWrap">
                            <span class="detail-label">Remarks</span>
                            <div class="detail-value" id="modalRemarks">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .leave-details-card .leave-overview-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
        border: 1px solid rgba(0,0,0,.06);
    }
    .leave-details-card .leave-date-box {
        background: #fff;
        border: 1px solid rgba(0,0,0,.08);
        min-width: 70px;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .leave-details-card .detail-item {
        background: #f8f9fa;
        border: 1px solid rgba(0,0,0,.06);
        transition: background .15s ease, border-color .15s ease;
    }
    .leave-details-card .detail-item:hover {
        background: #f1f3f5;
        border-color: rgba(0,0,0,.08);
    }
    .leave-details-card .detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        margin-bottom: 0.35rem;
    }
    .leave-details-card .detail-value {
        font-size: 0.9375rem;
        color: #212529;
    }
    .leave-details-card     .letter-spacing {
        letter-spacing: 0.05em;
    }
    #leaveHistoryDetailModal .detail-item {
        background: #f8f9fa;
        transition: background 0.2s;
    }
    #leaveHistoryDetailModal .detail-item:hover {
        background: #eef0f2;
    }
    #leaveHistoryDetailModal .detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        display: block;
        margin-bottom: 0.25rem;
    }
    #leaveHistoryDetailModal .detail-value {
        font-weight: 500;
        color: #212529;
    }
</style>
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
                        start: data.start,
                        length: data.length,
                        draw: data.draw
                    },
                    success: function (response) {
                        var draw = (response && response.draw != null) ? response.draw : data.draw;
                        var recordsTotal = (response && response.recordsTotal != null) ? response.recordsTotal : 0;
                        var recordsFiltered = (response && response.recordsFiltered != null) ? response.recordsFiltered : 0;
                        var dataRows = (response && Array.isArray(response.data)) ? response.data : [];
                        callback({
                            draw: draw,
                            recordsTotal: recordsTotal,
                            recordsFiltered: recordsFiltered,
                            data: dataRows
                        });
                    },
                    error: function (xhr) {
                        callback({
                            draw: data.draw,
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            data: []
                        });
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load leave history.';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        } else {
                            console.error(msg);
                        }
                    }
                });
            },
            columns: [
                { data: 'leave_category', name: 'leave_category', defaultContent: '—' },
                { data: 'reason', name: 'reason', defaultContent: '—' },
                { data: 'from_date', name: 'from_date', defaultContent: '—' },
                { data: 'to_date', name: 'to_date', defaultContent: '—' },
                { data: 'total_days', name: 'total_days', defaultContent: '—' },
                {
                    data: 'attachments',
                    defaultContent: 'No Attachment',
                    render: function (data) {
                        if (!data) return 'No Attachment';
                        var url = typeof data === 'string' ? data : (data.url || '');
                        return url ? '<a href="' + url + '" target="_blank"><img src="/resorts_assets/images/pdf1.svg" alt="attachment"></a>' : 'No Attachment';
                    }
                },
                {
                    data: 'status',
                    defaultContent: 'Pending',
                    render: function (data, type, row) {
                        var statusText = (row && row.status_text != null) ? String(row.status_text) : 'Pending';
                        var statusClass = 'badge-secondary';
                        if (statusText.indexOf('Approved') !== -1) statusClass = 'badge-themeSuccess';
                        else if (statusText.indexOf('Rejected') !== -1) statusClass = 'badge-themeDanger';
                        else if (statusText.indexOf('Pending') !== -1) statusClass = 'badge-themeWarning';
                        return '<span class="badge ' + statusClass + '">' + statusText + '</span>';
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    defaultContent: '',
                    render: function (data) {
                        var id = (data != null && data !== '') ? String(data) : '';
                        return '<a href="#" class="btn btn-sm btn-link p-0 view-history-detail" data-leave-id="' + id + '" title="View"><i class="fa-regular fa-eye"></i></a>';
                    }
                }
            ]
        });

        // Refresh table on category filter change
        $('#category-filter').on('change', function () {
            $('#table-leaveHistory').DataTable().ajax.reload();
        });

        // Leave History: View detail modal
        $(document).on('click', '.view-history-detail', function (e) {
            e.preventDefault();
            var leaveId = parseInt($(this).attr('data-leave-id') || $(this).data('leave-id') || 0, 10);
            var empID = parseInt($('#empID').val() || $('#empID').attr('value') || 0, 10);
            if (!leaveId || !empID) {
                if (typeof toastr !== 'undefined') toastr.error('Missing leave or employee.');
                return;
            }
            var $modal = $('#leaveHistoryDetailModal');
            var modalEl = document.getElementById('leaveHistoryDetailModal');
            var $loading = $('#leaveHistoryDetailLoading');
            var $content = $('#leaveHistoryDetailContent');
            $loading.removeClass('d-none');
            $content.addClass('d-none');
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal && modalEl) {
                    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                } else if ($modal.length && typeof $modal.modal === 'function') {
                    $modal.modal('show');
                } else {
                    $modal.addClass('show').css('display', 'block').attr('aria-hidden', 'false');
                    $('body').addClass('modal-open');
                }
            } catch (err) {
                $modal.addClass('show').css('display', 'block').attr('aria-hidden', 'false');
                $('body').addClass('modal-open');
            }
            $.ajax({
                url: '{{ route("leave.history.detail") }}',
                type: 'GET',
                dataType: 'json',
                data: { leave_id: leaveId, empID: empID },
                success: function (res) {
                    $loading.addClass('d-none');
                    if (!res.success || !res.leave) {
                        $content.find('.detail-value').text('—');
                        $('#modalLeaveStatus').text('Error loading details');
                        $content.removeClass('d-none');
                        return;
                    }
                    var L = res.leave;
                    var statusClass = (L.status_label || '').toLowerCase() === 'approved' ? 'bg-success' : ((L.status_label || '').toLowerCase() === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                    $('#modalLeaveStatus').attr('class', 'badge ' + statusClass).text(L.status_label || 'Pending');
                    $('#modalFromDay').text(L.from_date ? L.from_date_formatted || L.from_date : '—');
                    $('#modalToDay').text(L.to_date ? L.to_date_formatted || L.to_date : '—');
                    $('#modalLeaveCategory').text(L.leave_category || '—');
                    $('#modalReportingTo').text(L.reporting_to_name || '—');
                    $('#modalReason').text(L.reason || '—');
                    $('#modalFromDate').text(L.from_date_formatted || '—');
                    $('#modalToDate').text(L.to_date_formatted || '—');
                    $('#modalTotalDays').text(L.total_days != null ? L.total_days + ' day(s)' : '—');
                    if (L.attachments) {
                        $('#modalAttachment').html('<a href="' + L.attachments + '" target="_blank"><img src="/resorts_assets/images/pdf1.svg" alt="attachment"></a>');
                    } else {
                        $('#modalAttachment').text('No Attachment');
                    }
                    var dp = res.departure_pass;
                    if (dp) {
                        $('#modalDeparturePassSection').removeClass('d-none');
                        $('#modalDepDate').text(dp.departure_date_formatted || '—');
                        $('#modalDepTime').text(dp.departure_time || '—');
                        $('#modalArrDate').text(dp.arrival_date_formatted || '—');
                        $('#modalArrTime').text(dp.arrival_time || '—');
                        $('#modalDepReason').text(dp.reason_text || '—');
                        var passBadge = dp.pass_status ? '<span class="badge bg-warning text-dark">' + dp.pass_status + '</span>' : '—';
                        $('#modalDepStatus').html(passBadge);
                    } else {
                        $('#modalDeparturePassSection').addClass('d-none');
                    }
                    $('#modalRemarks').text(L.destination || '—');
                    $content.removeClass('d-none');
                },
                error: function (xhr) {
                    $loading.addClass('d-none');
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load leave details.';
                    $('#modalLeaveStatus').attr('class', 'badge bg-danger').text('Error');
                    $('#modalFromDay,#modalToDay,#modalLeaveCategory,#modalReportingTo,#modalReason,#modalFromDate,#modalToDate,#modalTotalDays,#modalRemarks').text('—');
                    $('#modalAttachment').text('—');
                    $('#modalDeparturePassSection').addClass('d-none');
                    $content.removeClass('d-none');
                    if (typeof toastr !== 'undefined') toastr.error(msg); else alert(msg);
                }
            });
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