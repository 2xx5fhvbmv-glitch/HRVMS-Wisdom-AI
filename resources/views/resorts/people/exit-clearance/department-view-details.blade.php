@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    {{-- Surface any flash messages (e.g. the Clearance Form redirect when
         the user isn't assigned) as toastr toasts on first paint so they
         don't disappear silently. --}}
    @if(session('success') || session('error'))
        <script>
            $(function () {
                @if(session('success'))
                    toastr.success(@json(session('success')), 'Success', { positionClass: 'toast-bottom-right' });
                @endif
                @if(session('error'))
                    toastr.error(@json(session('error')), 'Notice', { positionClass: 'toast-bottom-right' });
                @endif
            });
        </script>
    @endif
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
                                            <td>{{ optional(optional($exit_clearance->employee)->resortAdmin)->full_name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Employee ID:</th>
                                            <td>{{ optional($exit_clearance->employee)->Emp_id ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{ optional(optional($exit_clearance->employee)->department)->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{ optional(optional($exit_clearance->employee)->position)->position_title ?? '—' }}</td>
                                        </tr>
                                       <tr>
                                            <th>Employment Duration:</th>
                                            <td>
                                                @php
                                                    // Mirrors the HR-facing view-details template.
                                                    // Falls back to resignation_date when
                                                    // last_working_day hasn't been recorded so
                                                    // freshly-submitted requests still show a
                                                    // meaningful tenure bracket.
                                                    $jd  = $exit_clearance->employee->joining_date ?? null;
                                                    $end = $exit_clearance->last_working_day
                                                        ?? $exit_clearance->resignation_date
                                                        ?? null;
                                                    $tenureLabel = null;
                                                    if ($jd && $end) {
                                                        $start  = \Carbon\Carbon::parse($jd);
                                                        $finish = \Carbon\Carbon::parse($end);
                                                        if ($finish->greaterThanOrEqualTo($start)) {
                                                            $diff = $start->diff($finish);
                                                            $parts = [];
                                                            if ($diff->y > 0) $parts[] = $diff->y . ' year'  . ($diff->y > 1 ? 's' : '');
                                                            if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
                                                            if (empty($parts)) {
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
                                            <td>{{ optional($exit_clearance->reason_title)->reason ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Last Working Date:</th>
                                            <td>{{ \Carbon\Carbon::parse($exit_clearance->last_working_day)->format('d M Y')}}</td>
                                        </tr>
                                        <tr>
                                            <th>Notice Period:</th>
                                            <td>{{
                                                    \Carbon\Carbon::parse($exit_clearance->resignation_date)->format('d M Y')
                                                    . ' - ' .
                                                    \Carbon\Carbon::parse($exit_clearance->last_working_day)->format('d M Y')
                                                }}</td>
                                        </tr>
                                        <tr>
                                            <th>Required Immediate Release:</th>
                                            <td>{{$exit_clearance->immediate_release}}</td>
                                        </tr>
                                        <tr>
                                            <th>Additional Details:</th>
                                            <td>{{$exit_clearance->comments}}</td>
                                        </tr>
                                        <tr>
                                            <th>Attachments:</th>
                                            <td>
                                                {{-- Mirrors the HR-facing view-details template:
                                                     resignation_letter is stored as JSON
                                                     `{"Filename":"...","Child_id":<id>}` by the
                                                     API resignation flow. Resolve through
                                                     ChildFileManagement → wasabi/s3 signed URL. --}}
                                                @php
                                                    $attachment = !empty($exit_clearance->resignation_letter)
                                                        ? json_decode($exit_clearance->resignation_letter, true)
                                                        : null;
                                                    $attachmentName = is_array($attachment) ? ($attachment['Filename'] ?? null) : null;
                                                    $attachmentChildId = is_array($attachment) ? ($attachment['Child_id'] ?? null) : null;
                                                    $attachmentUrl = null;
                                                    // First try the JSON-encoded payload (newer
                                                    // resignations). Fall back to treating the
                                                    // column as a raw path (older flows still write
                                                    // a plain S3 key directly into the column).
                                                    if ($attachmentChildId) {
                                                        try {
                                                            $cfm = \App\Models\ChildFileManagement::find($attachmentChildId);
                                                            if ($cfm && !empty($cfm->File_Path)) {
                                                                $res = \Common::GetApplicantAWSFile($cfm->File_Path);
                                                                $attachmentUrl = ($res['success'] ?? false) ? ($res['NewURLshow'] ?? null) : null;
                                                            }
                                                        } catch (\Throwable $e) { $attachmentUrl = null; }
                                                    } elseif (!empty($exit_clearance->resignation_letter) && !is_array($attachment)) {
                                                        // Raw path fallback.
                                                        try {
                                                            $res = \Common::GetApplicantAWSFile($exit_clearance->resignation_letter);
                                                            $attachmentUrl = ($res['success'] ?? false) ? ($res['NewURLshow'] ?? null) : null;
                                                            $attachmentName = basename($exit_clearance->resignation_letter);
                                                        } catch (\Throwable $e) { $attachmentUrl = null; }
                                                    }
                                                @endphp
                                                @if($attachmentUrl)
                                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                        <i class="fa-regular fa-file-pdf me-1" style="color:#c0392b;"></i>
                                                        {{ $attachmentName ?: 'Resignation Letter' }}
                                                    </a>
                                                @elseif($attachmentName)
                                                    <span title="File reference exists but the stored file could not be located">
                                                        <i class="fa-regular fa-file-pdf me-1" style="color:#999;"></i>
                                                        {{ $attachmentName }}
                                                        <small class="text-muted">(file not accessible — re-upload required)</small>
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
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title mb-0">
                        <div class="">
                            <h3>Exit Interview Status</h3>
                        </div>

                    </div>
                    @foreach($exitClearanceFormAssignments as $exitClearanceFormAssignment)
                        <div class="row g-xxl-4 g-md-3 g-2">
                            <div class="col-lg-6">
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tbody>
                                            <tr>
                                                <th>Form Assigned:</th>
                                                <td>Yes
                                                    @if($is_hr == true )
                                                    <a href="{{route('people.exit-clearance.employeeFormAssignmentShow',base64_encode($exitClearanceFormAssignment->id))}}" class="btn-lg-icon icon-bg-yellow mx-1"><i
                                                            class="fa-solid fa-link"></i></a>
                                                    @endif
                                                    <span class="">{{ $exitClearanceFormAssignment->exitClearanceForm->form_name }}</span></td>
                                            </tr>
                                            <tr>
                                                <th>Date Assigned:</th>
                                                <td>{{ Carbon\Carbon::parse($exitClearanceFormAssignment->assigned_date)->format('d M Y') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="table-responsive">
                                    <table class="table table-lable mb-1">
                                        <tbody>
                                            <tr>
                                                <th>Response Deadline:</th>
                                                <td> {{ Carbon\Carbon::parse($exitClearanceFormAssignment->deadline_date)->format('d M Y') }} </td>
                                            </tr>
                                            <tr>
                                                <th>Completion Status:</th>
                                                <td>
                                                    {{ $exitClearanceFormAssignment->status }}
                                                    @if($exitClearanceFormAssignment->status === 'Completed' && !empty($exitClearanceFormAssignment->completed_via))
                                                        @if($exitClearanceFormAssignment->completed_via === 'mobile')
                                                            <span class="badge bg-info-subtle text-info ms-1" title="Submitted by the employee via the mobile app">
                                                                <i class="fa-solid fa-mobile-screen-button me-1"></i>mobile
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary ms-1" title="Marked complete in-browser by HR or HOD">
                                                                <i class="fa-solid fa-desktop me-1"></i>web
                                                            </span>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Departure Arrangements — was implemented on the HR-facing
                     view-details template but missing here. Same checkbox set,
                     same AJAX save endpoint (employeeDepartureArrangement);
                     handler already exists below in the scripts section.
                     Passport / visa expiry dates pull from the OCR-extracted
                     VisaEmployeeExpiryData row when one is on file. --}}
                @php
                    $_passportExpiry = null;
                    $_visaExpiry = null;
                    if (!empty($exit_clearance->employee->id)) {
                        $_passportRow = \App\Models\VisaEmployeeExpiryData::where('resort_id', $exit_clearance->resort_id)
                            ->where('employee_id', $exit_clearance->employee->id)
                            ->where('DocumentName', 'Passport_Copy')
                            ->latest('id')->first();
                        if ($_passportRow) {
                            $f = $_passportRow->Ai_extracted_data['extracted_fields'] ?? [];
                            $raw = $f['Date of Expiry'] ?? $f['Passport Expiry Date'] ?? null;
                            if ($raw) { try { $_passportExpiry = \Carbon\Carbon::parse($raw)->format('d M Y'); } catch (\Throwable $e) {} }
                        }
                        $_visaRow = \App\Models\VisaEmployeeExpiryData::where('resort_id', $exit_clearance->resort_id)
                            ->where('employee_id', $exit_clearance->employee->id)
                            ->where('DocumentName', 'Visa')
                            ->latest('id')->first();
                        if ($_visaRow) {
                            $f = $_visaRow->Ai_extracted_data['extracted_fields'] ?? [];
                            $raw = $f['Visa Expiry Date'] ?? null;
                            if ($raw) { try { $_visaExpiry = \Carbon\Carbon::parse($raw)->format('d M Y'); } catch (\Throwable $e) {} }
                        }
                    }
                @endphp
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Departure Arrangements</h3>
                    </div>
                    <div class="row g-xxl-4 g-md-3 g-2">
                        <div class="col-xl-5 col-md-6">
                            <div class="form-check mb-md-4 mb-2">
                                <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="international_flight" value="option1" @if(isset($exit_clearance->departure_arrangements) && ($exit_clearance->departure_arrangements['international_flight'] ?? 0) == 1) checked @endif>
                                <label class="form-check-label" for="international_flight">Has the international flight ticket been booked?</label>
                            </div>
                            <div class="form-check mb-md-4 mb-2">
                                <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="transportation_arranged" value="option2" @if(isset($exit_clearance->departure_arrangements) && ($exit_clearance->departure_arrangements['transportation_arranged'] ?? 0) == 1) checked @endif>
                                <label class="form-check-label" for="transportation_arranged">Has transportation been arranged?</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="passport_validity" value="option3" @if(isset($exit_clearance->departure_arrangements) && ($exit_clearance->departure_arrangements['passport_validity'] ?? 0) == 1) checked @endif>
                                <label class="form-check-label" for="passport_validity">
                                    Has the employee’s passport validity been verified?
                                    <span class="d-block text-muted" style="font-size:12px;">
                                        (Passport Validity: {{ $_passportExpiry ?: 'N/A' }} &middot; Visa Validity: {{ $_visaExpiry ?: 'N/A' }})
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="col-xl-7 col-md-6">
                            <div class="form-check mb-md-4 mb-2">
                                <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="accommodation_arranged" value="option4" @if(isset($exit_clearance->departure_arrangements) && ($exit_clearance->departure_arrangements['accommodation_arranged'] ?? 0) == 1) checked @endif>
                                <label class="form-check-label" for="accommodation_arranged">Has accommodation in Malé been arranged?</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="documentVerifed" value="option5" @if(isset($exit_clearance->departure_arrangements) && ($exit_clearance->departure_arrangements['documentVerifed'] ?? 0) == 1) checked @endif>
                                <label class="form-check-label" for="documentVerifed">Has the employee’s visa documentation been verified and cleared?</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row align-items-center g-2">
                        {{-- Left-side link helpers --}}
                        <div class="col-auto">
                            <a href="javascript:void(0)" onclick="sendEmploymentCertificate()" class="a-link">Send An Employment Certificate</a>
                        </div>
                        <div class="col-auto">
                            <a href="javascript:void(0)" data-url="{{ route('people.exit-clearance.sendReminder', base64_encode($exit_clearance->id)) }}" class="a-linkTheme" id="send-reminder-btn">Send Reminder To Employee</a>
                        </div>

                        {{-- Right-side action buttons. Matches the figma: three
                             primary actions pushed to the right. --}}

                        {{-- Clearance Form — opens the per-department checklist
                             this user has been assigned to fill in. The
                             controller redirects with an error flash if the
                             user isn't assigned, which used to be invisible
                             (no flash renderer on the page). We now (a) gate
                             the button on $is_assigned and (b) render flash
                             messages as toasts at the top of the page so any
                             remaining redirects don't silently die. --}}
                        @if(!empty($is_assigned))
                            <div class="col-auto ms-auto">
                                <a href="{{route('people.exit-clearance.department-form',base64_encode($exit_clearance->id))}}" class="btn btn-themeSkyblue btn-sm">Clearance Form</a>
                            </div>
                        @else
                            <div class="col-auto ms-auto" title="You are not assigned to a clearance form on this resignation.">
                                <button type="button" class="btn btn-themeSkyblue btn-sm disabled" disabled>Clearance Form</button>
                            </div>
                        @endif

                        {{-- Full and Final Settlement — opens the FNF wizard
                             (Payroll). The route is global and lists every
                             approved resignation pending FNF, so the user
                             still picks this employee on the next screen. --}}
                        <div class="col-auto">
                            <a href="{{route('payroll.final.settlement', ['empId' => base64_encode($exit_clearance->employee_id)])}}" class="btn btn-themeBlue btn-sm">Full And Final Settlement</a>
                        </div>

                        {{-- Mark As Completed — confirms every department
                             assignment is signed off and flips the resignation
                             to Completed (and the employee from Offboarding to
                             Terminated). Hidden once it's already complete so
                             HR can't fire the endpoint twice. The Mark-as-
                             Complete route is a POST that expects a
                             base64-encoded id — the previous HR-view markup
                             accidentally passed the raw id, which silently
                             404'd. The button below uses a tiny form +
                             confirm() so the POST + CSRF + base64 are all
                             correct. --}}
                        @if(($exit_clearance->status ?? null) !== 'Completed')
                            <div class="col-auto">
                                {{-- Hidden form that actually POSTs. The visible
                                     button just opens the confirmation modal,
                                     whose "Confirm" submits the form. Avoids
                                     the native browser confirm() dialog. --}}
                                <form id="markCompleteForm"
                                      action="{{ route('people.exit-clearance.markAsComplete', base64_encode($exit_clearance->id)) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                </form>
                                <button type="button" class="btn btn-themeGreenNew btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#markCompleteModal">
                                    Mark As Completed
                                </button>
                            </div>
                        @else
                            <div class="col-auto">
                                <span class="badge badge-themeSuccess px-3 py-2">
                                    <i class="fa-solid fa-check me-1"></i> Completed
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Mark-As-Completed confirmation modal. Triggered by the
                     button above; the Confirm button submits #markCompleteForm
                     which POSTs to people.exit-clearance.markAsComplete with
                     CSRF + base64 id. --}}
                <div class="modal fade" id="markCompleteModal" tabindex="-1" aria-labelledby="markCompleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="markCompleteModalLabel">
                                    <i class="fa-solid fa-circle-check text-success me-2"></i>
                                    Mark Exit Clearance Completed
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-1">Mark this exit clearance as <strong>Completed</strong>?</p>
                                <p class="text-muted mb-0" style="font-size: 13px;">
                                    This finalises the employee's offboarding —
                                    the resignation moves to Completed and the
                                    employee's status flips from Offboarding to
                                    Terminated. This action cannot be undone.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-themeGray btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-themeGreenNew btn-sm" id="markCompleteConfirmBtn">
                                    Yes, Mark Completed
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
    
@endsection

@section('import-scripts')

<script>
$(document).on('change', '.dep-arrangement-checkbox', function() {
    let arrangementData = {};
    $('.dep-arrangement-checkbox').each(function() {
        arrangementData[$(this).attr('id')] = $(this).is(':checked') ? 1 : 0;
    });
    $.ajax({
        url: "{{ route('people.exit-clearance.employeeDepartureArrangement', base64_encode($exit_clearance->id)) }}",
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            arrangements: arrangementData
        },
        success: function(response) {
            if(response.status === 'success') {
                $('#departure-arrangements').html(response.html);
            }
        }
    });
});

// Modal Confirm → submit the hidden Mark-As-Completed form. The button
// disables itself on first click so an impatient double-click can't
// double-POST and re-fire the offboarding notifications.
$(document).on('click', '#markCompleteConfirmBtn', function () {
    $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Marking…');
    $('#markCompleteForm').trigger('submit');
});

// ------------------------------------------------------------------
// Send Reminder To Employee — GETs the sendReminder route which
// emails the resignee with the current departure-arrangement gaps.
// Defensive null-checks on responseJSON so a 500/network error
// doesn't crash the toast handler (same defensive pattern as the
// employee-create submit handler).
// ------------------------------------------------------------------
$('#send-reminder-btn').on('click', function (e) {
    e.preventDefault();
    var url = $(this).data('url');
    if (!url) {
        toastr.error('Reminder URL is missing.', 'Error', { positionClass: 'toast-bottom-right' });
        return;
    }
    var $btn = $(this).addClass('disabled');
    $.ajax({
        url: url,
        type: 'GET',
        success: function (response) {
            if (response && response.success) {
                toastr.success(response.message || 'Reminder sent.', 'Success', { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error((response && response.message) || 'Could not send reminder.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        },
        error: function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Something went wrong while sending the reminder.';
            toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
        },
        complete: function () { $btn.removeClass('disabled'); }
    });
});

// ------------------------------------------------------------------
// Send An Employment Certificate — fires the same email pipeline as
// the HR view (people.exit-clearance.employement-certificate). Useful
// for resignees who need an immediate certificate handover before
// the full FNF settlement.
// ------------------------------------------------------------------
function sendEmploymentCertificate() {
    $.ajax({
        url: "{{ route('people.exit-clearance.employement-certificate', base64_encode($exit_clearance->id)) }}",
        type: 'GET',
        success: function (response) {
            if (response && response.success) {
                toastr.success(response.message || 'Certificate sent.', 'Success', { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error((response && response.message) || 'Could not send certificate.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        },
        error: function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Something went wrong while sending the certificate.';
            toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
        }
    });
}
</script>
@endsection
