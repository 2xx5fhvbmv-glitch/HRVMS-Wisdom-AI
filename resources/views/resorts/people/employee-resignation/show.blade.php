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
                </div>
            </div>
            <div class="card card-exitProfilePeopleEmp">
                <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                    <div class="col-lg-6">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0">
                                <h3>Employee Details</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{$employeeResignation->employee->resortAdmin->full_name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Employee ID:</th>
                                            <td>{{$employeeResignation->employee->Emp_id}}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{$employeeResignation->employee->department->name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{$employeeResignation->employee->position->position_title}}</td>
                                        </tr>
                                       <tr>
                                            <th>Employment Duration:</th>
                                            <td>
                                                @php
                                                    $jd = $employeeResignation->employee->joining_date ?? null;
                                                    // End of the employment span. Prefer the
                                                    // confirmed last_working_day; fall back to
                                                    // resignation_date so freshly-submitted
                                                    // resignations (no LWD yet) still show a
                                                    // meaningful tenure.
                                                    $end = $employeeResignation->last_working_day
                                                        ?? $employeeResignation->resignation_date
                                                        ?? null;
                                                    $tenureLabel = null;
                                                    if ($jd && $end) {
                                                        $start = \Carbon\Carbon::parse($jd);
                                                        $finish = \Carbon\Carbon::parse($end);
                                                        if ($finish->greaterThanOrEqualTo($start)) {
                                                            $diff = $start->diff($finish);
                                                            // Build "X year(s) Y month(s)" with
                                                            // sensible fallbacks for short stints.
                                                            $parts = [];
                                                            if ($diff->y > 0) {
                                                                $parts[] = $diff->y . ' year' . ($diff->y === 1 ? '' : 's');
                                                            }
                                                            if ($diff->m > 0) {
                                                                $parts[] = $diff->m . ' month' . ($diff->m === 1 ? '' : 's');
                                                            }
                                                            if (empty($parts)) {
                                                                // Less than a month — show days
                                                                // instead of an empty bracket.
                                                                $days = $start->diffInDays($finish);
                                                                $parts[] = $days . ' day' . ($days === 1 ? '' : 's');
                                                            }
                                                            $tenureLabel = implode(' ', $parts);
                                                        }
                                                    }
                                                @endphp
                                                {{ $jd ? \Carbon\Carbon::parse($jd)->format('d M Y') : 'N/A' }}
                                                -
                                                {{ $end ? \Carbon\Carbon::parse($end)->format('d M Y') : 'N/A' }}
                                                @if($tenureLabel)
                                                    <span class="text-muted">({{ $tenureLabel }})</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0">
                                <h3>Request Details</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable table-reqDetPeopleEmp mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Reason for Resignation:</th>
                                            <td>{{$employeeResignation->reason_title->reason}}</td>
                                        </tr>
                                        <tr>
                                            <th>Last Working Date:</th>
                                            <td>{{ \Carbon\Carbon::parse($employeeResignation->last_working_day)->format('d M Y')}}</td>
                                        </tr>
                                        <tr>
                                            <th>Notice Period:</th>
                                            <td>{{
                                                    \Carbon\Carbon::parse($employeeResignation->resignation_date)->format('d M Y')
                                                    . ' - ' .
                                                    \Carbon\Carbon::parse($employeeResignation->last_working_day)->format('d M Y')
                                                }}</td>
                                        </tr>
                                        <tr>
                                            <th>Required Immediate Release:</th>
                                            <td>{{$employeeResignation->immediate_release}}</td>
                                        </tr>
                                        <tr>
                                            <th>Additional Details:</th>
                                            <td>{{$employeeResignation->comments}}</td>
                                        </tr>
                                        <tr>
                                            <th>Attachments:</th>
                                            <td>
                                                {{-- Was hardcoded to "lorem-ipsum.pdf" with a
                                                     static icon — clicked nothing, downloaded
                                                     nothing. Real attachment lives in
                                                     employee_resignation.resignation_letter
                                                     as a Wasabi/S3 key, so resolve it through
                                                     Common::GetApplicantAWSFile (same helper
                                                     as biometric + expiry-document links). --}}
                                                @php
                                                    $_attachPath = $employeeResignation->resignation_letter ?? null;
                                                    $_attachUrl = null;
                                                    if (!empty($_attachPath)) {
                                                        $_res = \Common::GetApplicantAWSFile($_attachPath);
                                                        $_attachUrl = ($_res['success'] ?? false) ? ($_res['NewURLshow'] ?? null) : null;
                                                    }
                                                @endphp
                                                @if(!empty($_attachUrl))
                                                    <a href="{{ $_attachUrl }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                        <i class="fa fa-file-pdf text-danger me-2"></i>
                                                        {{ basename($_attachPath) }}
                                                    </a>
                                                @elseif(!empty($_attachPath))
                                                    <span class="text-muted">
                                                        <i class="fa fa-file me-2"></i>{{ basename($_attachPath) }}
                                                        <small>(file not accessible — re-upload required)</small>
                                                    </span>
                                                @else
                                                    <span class="text-muted">No attachment</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                    @if($employeeResignation->hod_status != 'Pending')
                        <div class="col-lg-6">
                            <div class="bg-themeGrayLight h-100">
                                <div class="card-title mb-0">
                                    <h3>HOD Details</h3>
                                </div>  
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tbody>
                                            <tr>
                                                <th>HOD Name:</th>
                                                <td>
                                                    @php
                                                        // Resolve HOD name through every path we have, in order
                                                        // of authority. Empty cell was happening because the
                                                        // resignation row had hod_id but the linked Employee's
                                                        // resortAdmin was missing — falling back through the
                                                        // employee's direct reporting line + dept HOD lookup
                                                        // covers that. Final fallback is the employee's
                                                        // raw reporting_to so the column is never blank.
                                                        $_hodName = null;
                                                        $candidates = [
                                                            optional(optional($employeeResignation->hod)->resortAdmin)->full_name,
                                                            optional(optional(optional($employeeResignation->employee)->reportingTo)->resortAdmin)->full_name,
                                                        ];
                                                        if (!empty($employeeResignation->employee->Dept_id)) {
                                                            $_hodFromDept = \App\Helpers\Common::FindResortHODDepartment(
                                                                $employeeResignation->resort_id,
                                                                $employeeResignation->employee->Dept_id
                                                            );
                                                            if ($_hodFromDept) {
                                                                $candidates[] = optional(optional($_hodFromDept)->resortAdmin)->full_name;
                                                            }
                                                        }
                                                        foreach ($candidates as $c) {
                                                            if (!empty(trim((string) $c))) { $_hodName = $c; break; }
                                                        }
                                                    @endphp
                                                    {{ $_hodName ?: 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>HOD Status:</th>
                                                <td>{{$employeeResignation->hod_status}}</td>
                                            </tr>
                                            <tr>
                                                <th>HOD Meeting Status:</th>
                                                <td>{{$employeeResignation->hod_meeting_status}}</td>
                                            </tr>
                                            @if($employeeResignation->hod_meeting_status == 'Completed')
                                                <tr>
                                                    <th>HOD Comments:</th>
                                                    <td>{{$employeeResignation->hod_comments}}</td>
                                                </tr>
                                            @endif
                                            @if($employeeResignation->hod_status == 'Rejected')
                                                <tr>
                                                    <th>HOD Comments:</th>
                                                    <td>{{$employeeResignation->rejected_reason}}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>      
                        </div>
                    @endif

                    @if($employeeResignation->hr_status != 'Pending')
                        <div class="col-lg-6">
                            <div class="bg-themeGrayLight h-100">
                                <div class="card-title mb-0">
                                    <h3>HR Details</h3> 
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tbody>
                                            <tr>
                                                <th>HR Name:</th>
                                                <td>
                                                    @php
                                                        // Same defensive resolution as HOD Name: prefer the
                                                        // saved hr_id approver, then fall back to the resort's
                                                        // canonical HR (FindResortHR) so the cell isn't blank
                                                        // when the relationship target was soft-deleted or
                                                        // its resortAdmin row went missing.
                                                        $_hrName = optional(optional($employeeResignation->hr)->resortAdmin)->full_name;
                                                        if (empty(trim((string) $_hrName))) {
                                                            $_hrFallback = \App\Helpers\Common::FindResortHR((object) [
                                                                'resort_id' => $employeeResignation->resort_id,
                                                            ]);
                                                            $_hrName = optional(optional($_hrFallback)->resortAdmin)->full_name;
                                                        }
                                                    @endphp
                                                    {{ $_hrName ?: 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>HR Status:</th>
                                                <td>{{$employeeResignation->hr_status}}</td>
                                            </tr>
                                            <tr>
                                                <th>HR Meeting Status:</th>
                                                <td>{{$employeeResignation->hr_meeting_status}}</td>
                                            </tr>
                                            @if($employeeResignation->hr_meeting_status == 'Completed')
                                                <tr>
                                                    <th>HR Comments:</th>
                                                    <td>{{$employeeResignation->hr_comments}}</td>
                                                </tr>
                                            @endif
                                            @if($employeeResignation->hr_status == 'Rejected')
                                                <tr>
                                                    <th>HR Comments:</th>
                                                    <td>{{$employeeResignation->rejected_reason}}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>


                @if($is_hod == true && $employeeResignation->hod_status == 'Pending' && $employeeResignation->hod_meeting_status == 'Employee Schedule Confirm' || $is_hr == true && $employeeResignation->hr_status == 'Pending' && $employeeResignation->hr_meeting_status == 'Employee Schedule Confirm' && $employeeResignation->hod_meeting_status == 'Completed')
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="statusComment" class="form-label">Enter Meeting Conclusion <span class="text-danger">*</span></label>
                            <textarea id="statusComment" class="form-control" rows="3" placeholder="Write your notes here"></textarea>
                        </div>
                    </div>
                
                    <div class="card-footer">
                        <div class="row align-items-center g-2 @if(Common::checkRouteWisePermission('people.employee-resignation.index',config('settings.resort_permissions.edit')) == false) d-none @endif">
                        
                            <div class="col-auto ms-auto">
                                <a href="javascript:void(0);" 
                                   class="btn btn-themeDanger btn-sm update-status" 
                                   data-status="Rejected"
                                   data-id="{{ base64_encode($employeeResignation->id) }}">
                                   Reject
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="javascript:void(0);" 
                                   class="btn btn-themeGreenNew btn-sm update-status" 
                                   data-status="Approved"
                                   data-id="{{ base64_encode($employeeResignation->id) }}">
                                   Approve
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

     <!-- Reject Modal -->
    <div class="modal fade" id="rejectStatusModal" tabindex="-1" aria-labelledby="rejectStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-rejected">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectStatusForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="resignation_id" id="rejectResignation_id">
                        <input type="hidden" name="status" value="Rejected">
                        <input type="hidden" name="meeting_comment" id="meetingComment" value="">
                        <textarea id="rejectComment" class="form-control" name="comment" rows="3" placeholder="Write your comment (required)" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="submitBtn" class="btn btn-themeBlue">Submit</button>
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
    $(document).ready(function() {
        let statusComment = '';

        $('#statusComment').on('input', function() {
            statusComment = $(this).val();
        });

        // Approve or Reject button click
        $('.update-status').off('click').on('click', function(e) {
            e.preventDefault();
            var status = $(this).data('status');
            var resignationId = $(this).data('id');
            statusComment = $('#statusComment').val();

            if (!statusComment.trim()) {
                toastr.error('Please enter a comment before proceeding.', 'Error', {
                    positionClass: 'toast-bottom-right',
                    timeOut: 2000,
                });
                return;
            }

            if (status === 'Approved') {
                $.ajax({
                    url: "{{ route('people.employee-resignation.status-update') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        status: 'Approved',
                        resignation_id: resignationId,
                        meeting_comment: statusComment,
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        toastr.error('Something went wrong', 'Error', {
                            positionClass: 'toast-bottom-right',
                            timeOut: 2000,
                        });
                    }
                });
            } else if (status === 'Rejected') {
                $('#rejectResignation_id').val(resignationId);
                $('#rejectComment').val('');
                $('#meetingComment').val(statusComment);
                $('#rejectStatusModal').modal('show');
            }
        });

        // When modal is closed, clear comment in modal
        $('#rejectStatusModal').on('hidden.bs.modal', function () {
            $('#rejectComment').val('');
        });

        // On modal submit, pass both meeting comment and rejection reason
        $('#rejectStatusForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            var resignationId = $('#rejectResignation_id').val();
            var comment = $('#rejectComment').val();
            var meetingComment = $('#meetingComment').val();

            if (!comment.trim()) {
                
                toastr.error('Please enter rejection reason.', 'Error', {
                    positionClass: 'toast-bottom-right',
                    timeOut: 2000,
                });
                return;
            }

            $.ajax({
                url: "{{ route('people.employee-resignation.status-update') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    status: 'Rejected',
                    reject_reason: comment,
                    meeting_comment: meetingComment,
                    resignation_id: resignationId,
                },
                success: function(response) {
                    $('#rejectStatusModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error('Failed to reject. Please try again.', 'Error', {
                        positionClass: 'toast-bottom-right',
                        timeOut: 2000,
                    });
                }
            });
        });
    });

</script>
@endsection
