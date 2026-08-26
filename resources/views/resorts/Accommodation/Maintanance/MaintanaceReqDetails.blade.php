@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-maintenReqDetail">
            <div class="row g-md-4 g-3">
                <div class="col-lg-8">
                    <div class="table-responsive ">
                        <table class="table table-lable">
                            <tbody>
                                <tr>
                                    <th>Request ID:</th>
                                    <td>{{ $MaintanaceRequest->Request_id }}</td>
                                </tr>
                                <tr>
                                    <th>Affected Amenity:</th>
                                    <td>{{ $MaintanaceRequest->EffectedAmenity }}</td>
                                </tr>
                                <tr>
                                    <th>Description of Issue:</th>
                                    <td>{{ $MaintanaceRequest->descriptionIssues }}</td>
                                </tr>
                                <tr>
                                    <th>Requested By:</th>
                                    <td>
                                        {!! $MaintanaceRequest->RequestedBy !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Location:</th>
                                    <td> {!! $MaintanaceRequest->Location !!}</td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td>{{ $MaintanaceRequest->Date }}</td>
                                </tr>
                                <tr>
                                    <th>Priority:</th>
                                    <td>
                                        {!! $MaintanaceRequest->Priority !!}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Image:</th>
                                    <td>
                                        <div class="smallImg-block">
                                            {!! $MaintanaceRequest->Image !!}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Video:</th>
                                    <td>
                                        <div class="smallImg-block">
                                            {!! $MaintanaceRequest->Video !!}
                                        </div>
                                    </td>
                                </tr>
                                @if (isset($MaintanaceRequest->ReasonOnHold))
                                    <tr>
                                        <th>Reason On Hold:</th>
                                        <td>
                                            <div class="smallImg-block">
                                                {{ $MaintanaceRequest->ReasonOnHold }}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-small  bg mb-3">
                        <div class=" card-title">
                            <h3>Status</h3>
                        </div>
                        @php
                            // Each step's own reached/not-reached state, computed
                            // once so the "Pending" marker below can reuse the
                            // exact same condition as the active class instead
                            // of duplicating it — previously every step printed
                            // its label unconditionally with no visual
                            // distinction beyond a dimmed opacity, so a
                            // not-yet-reached step read as already done.
                            $sentToHrActive = in_array('Open', $displayedStatuses) || in_array('Assinged', $displayedStatuses) || $MaintanaceRequest->Status == 'Open';
                            $reviewActive = in_array('Assinged', $displayedStatuses);
                            $inProgressActive = in_array('In-Progress', $displayedStatuses) || in_array('Resolved', $displayedStatuses) || in_array('Confirmed', $displayedStatuses);
                            $resolvedAwaitingActive = in_array('Resolvedawaiting', $displayedStatuses) || in_array('Closed', $displayedStatuses);
                            $confirmedActive = in_array('Closed', $displayedStatuses);
                        @endphp
                        <ul class="manning-timeline text-start">
                            {{-- 1. Request Submitted --}}
                            <li class="active">
                                <span>Request Submitted</span>
                                <small class="d-block text-muted">on {{ $MaintanaceRequest->Date }}</small>
                                <small class="d-block text-muted">for {{ $MaintanaceRequest->first_name }} {{ $MaintanaceRequest->last_name }}</small>
                            </li>

                            {{-- 2. Pending HR approval --}}
                            <li class="{{ $sentToHrActive ? 'active' : '' }} {{ $MaintanaceRequest->Status == 'Rejected' ? 'text-danger' : '' }}">
                                <span>{{ in_array('Open', $displayedStatuses) ? 'Approved by HR' : 'Pending HR approval' }}</span>
                                @if($MaintanaceRequest->Status == 'Rejected')
                                    <small class="d-block text-danger">Rejected: {{ $MaintanaceRequest->RejactionReason ?? '' }}</small>
                                @elseif($MaintanaceRequest->Status == 'On-Hold')
                                    <small class="d-block text-warning">On Hold: {{ $MaintanaceRequest->ReasonOnHold ?? '' }}</small>
                                @elseif(in_array('Open', $displayedStatuses))
                                    @if(isset($displayedStatusesDetails['Open'][0]))
                                        <small class="d-block text-success">by {{ $displayedStatusesDetails['Open'][0][2] ?? '' }} on {{ $displayedStatusesDetails['Open'][0][0] ?? '' }}</small>
                                    @else
                                        <small class="d-block text-success">Approved</small>
                                    @endif
                                @else
                                    <small class="d-block text-muted">Pending</small>
                                @endif
                            </li>

                            {{-- 3. Engineering HOD/EXCOM Review & Assign --}}
                            <li class="{{ $reviewActive ? 'active' : '' }}">
                                <span>Engineering Department Review</span>
                                @if($reviewActive && isset($displayedStatusesDetails['Assinged']))
                                    @foreach($displayedStatusesDetails['Assinged'] as $detail)
                                        <small class="d-block text-muted">Assigned to {{ $detail[2] ?? '' }} on {{ $detail[0] ?? '' }}</small>
                                    @endforeach
                                @else
                                    <small class="d-block text-muted">Pending</small>
                                @endif
                            </li>

                            {{-- 4. In Progress --}}
                            <li class="{{ $inProgressActive ? 'active' : '' }}">
                                <span>In Progress</span>
                                @unless($inProgressActive)
                                    <small class="d-block text-muted">Pending</small>
                                @endunless
                            </li>

                            {{-- 5. Resolved awaiting confirmation --}}
                            <li class="{{ $resolvedAwaitingActive ? 'active' : '' }}">
                                <span>Resolved Awaiting Confirmation</span>
                                @unless($resolvedAwaitingActive)
                                    <small class="d-block text-muted">Pending</small>
                                @endunless
                            </li>

                            {{-- 6. Confirmed resolved --}}
                            <li class="{{ $confirmedActive ? 'active' : '' }}">
                                <span>Confirmed Resolved</span>
                                @unless($confirmedActive)
                                    <small class="d-block text-muted">Pending</small>
                                @endunless
                            </li>
                        </ul>

                    </div>
                    <div class="card card-small bg">
                        <div class="flex-all justify-content-between">
                            <h6>Assigned Staff:</h6>
                            @if($MaintanaceRequest->Assigned_To)
                                @php
                                    $assignedStaff = \App\Models\Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                        ->where('employees.id', $MaintanaceRequest->Assigned_To)
                                        ->first(['ra.first_name', 'ra.last_name', 'ra.id as Parentid']);
                                @endphp
                                @if($assignedStaff)
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="{{ App\Helpers\Common::getResortUserPicture($assignedStaff->Parentid) }}" alt="user"></div>
                                        <div>
                                            <span>{{ $assignedStaff->first_name }} {{ $assignedStaff->last_name }}</span>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="text-muted">Not Assigned Yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ForwardToHOD-DetailsModel" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Request History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card card-small  bg mb-3">
                                <div class=" card-title">
                                    <h3>Status</h3>
                                </div>

                                <ul class="manning-timeline text-start ">
                                    {{-- Sent to HOD --}}
                                    <li
                                        class="{{ array_key_exists('SubmitedRequest', $displayedStatusesDetails) ? 'active' : '' }}">

                                        <p>
                                            @php
                                                if (array_key_exists('SubmitedRequest', $displayedStatusesDetails)) {
                                                    echo $displayedStatusesDetails['SubmitedRequest'][0];
                                                }
                                            @endphp
                                        </p>
                                        <span>Submitted Request</span>

                                    </li>
                                    <li class="{{ in_array('Open', $displayedStatuses) ? 'active' : '' }}">
                                        <p>
                                            @php
                                                if (array_key_exists('Open', $displayedStatusesDetails)) {
                                                    echo $displayedStatusesDetails['Open'][0][0];
                                                }
                                            @endphp
                                        </p>
                                        <span>Send to HOD</span>

                                    </li>

                                    {{-- Assigned by HOD --}}
                                    <li class="{{ in_array('Assinged', $displayedStatuses) ? 'active' : '' }}">
                                        <p>
                                            @if (array_key_exists('Assinged', $displayedStatusesDetails))
                                                {{ $displayedStatusesDetails['Assinged'][0][0] }}

                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img
                                                            src="{{ $displayedStatusesDetails['Assinged'][0][1] }}"
                                                            alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn"> HOD Assigned task to
                                                        {{ $displayedStatusesDetails['Assinged'][0][2] }}</span>
                                                </div>
                                            @endif
                                        </p>
                                    </li>

                                    {{-- In Progress --}}
                                    <li
                                        class="{{ in_array('In-Progress', $displayedStatuses) || in_array('Resolved', $displayedStatuses) || in_array('Confirmed', $displayedStatuses) ? 'active' : '' }}">
                                        <span>In Progress</span>
                                    </li>

                                    {{-- Resolved awaiting confirmation --}}
                                    <li class="{{ in_array('Resolvedawaiting', $displayedStatuses) ? 'active' : '' }}">
                                        <span>Resolved awaiting confirmation</span>
                                    </li>

                                    {{-- Confirmed resolved --}}
                                    <li class="{{ in_array('Closed', $displayedStatuses) ? 'active' : '' }}">
                                        <span>Confirmed resolved</span>
                                    </li>

                                </ul>


                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                </div>
                </form>
            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <script>
        $(document).on("click", "#RequestDetails", function() {
            $("#ForwardToHOD-DetailsModel").modal("show");
        });
    </script>
@endsection
