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
                                            <td>{{$exit_clearance->employee->resortAdmin->full_name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Employee ID:</th>
                                            <td>{{$exit_clearance->employee->Emp_id}}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{$exit_clearance->employee->department->name}}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{$exit_clearance->employee->position->position_title}}</td>
                                        </tr>
                                       <tr>
                                            <th>Employment Duration:</th>
                                            <td>
                                                @php
                                                    $joinDate = \Carbon\Carbon::parse($exit_clearance->employee->joining_date);
                                                    $endDate  = \Carbon\Carbon::parse($exit_clearance->last_working_day);
                                                    // Carbon diff in years/months/days, dropping any
                                                    // zero leading parts so a short tenure reads
                                                    // "1 month 7 days" instead of "0 years 1 month 7 days".
                                                    $diff     = $joinDate->diff($endDate);
                                                    $parts    = [];
                                                    if ($diff->y > 0) $parts[] = $diff->y . ' year'  . ($diff->y > 1 ? 's' : '');
                                                    if ($diff->m > 0) $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
                                                    if ($diff->d > 0) $parts[] = $diff->d . ' day'   . ($diff->d > 1 ? 's' : '');
                                                    $tenure   = $parts ? implode(' ', $parts) : '0 days';
                                                @endphp
                                                {{ $joinDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}
                                                <span style="color:#666;">({{ $tenure }})</span>
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
                                            <td>{{$exit_clearance->reason_title->reason}}</td>
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
                                                {{-- `resignation_letter` is stored as JSON
                                                    `{"Filename":"...", "Child_id":<id>}` by the
                                                    API resignation flow (see
                                                    API\ResignationController::store). When the
                                                    record was created via a path that skipped the
                                                    file upload, the column is NULL and there's
                                                    genuinely nothing to show. --}}
                                                @php
                                                    $attachment = !empty($exit_clearance->resignation_letter)
                                                        ? json_decode($exit_clearance->resignation_letter, true)
                                                        : null;
                                                    $attachmentName = is_array($attachment)
                                                        ? ($attachment['Filename'] ?? null)
                                                        : null;
                                                    $attachmentChildId = is_array($attachment)
                                                        ? ($attachment['Child_id'] ?? null)
                                                        : null;
                                                    $attachmentUrl = null;
                                                    if ($attachmentChildId) {
                                                        try {
                                                            $cfm = \App\Models\ChildFileManagement::find($attachmentChildId);
                                                            if ($cfm && !empty($cfm->File_Path)) {
                                                                // File_Path is the wasabi/s3 key
                                                                // captured by AWSEmployeeFileUpload.
                                                                // Use Storage::disk('wasabi') so
                                                                // signed URL respects whichever
                                                                // driver is configured.
                                                                $disk = config('filesystems.default') === 'wasabi'
                                                                    ? 'wasabi' : 'public';
                                                                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($cfm->File_Path)) {
                                                                    $attachmentUrl = \Illuminate\Support\Facades\Storage::disk($disk)
                                                                        ->temporaryUrl($cfm->File_Path, now()->addMinutes(30));
                                                                }
                                                            }
                                                        } catch (\Throwable $e) {
                                                            $attachmentUrl = null;
                                                        }
                                                    }
                                                @endphp
                                                @if($attachmentName)
                                                    @if($attachmentUrl)
                                                        <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener">
                                                            <i class="fa-regular fa-file-pdf me-1" style="color:#c0392b;"></i>
                                                            {{ $attachmentName }}
                                                        </a>
                                                    @else
                                                        <span title="File reference exists but the stored file could not be located">
                                                            <i class="fa-regular fa-file-pdf me-1" style="color:#999;"></i>
                                                            {{ $attachmentName }}
                                                        </span>
                                                    @endif
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

                {{-- People Details: Supervisor + HOD (with comments) + HR
                     (with comments). Was previously not rendered — the
                     data is on the resignation model (hod_id / hr_id /
                     hod_comments / hr_comments) and on the employee
                     (reporting_to). Added so HR can see WHO owns the
                     resignation and WHAT each approver said. --}}
                @php
                    $supervisor = optional($exit_clearance->employee)->reportingTo;
                    $supervisorName = optional(optional($supervisor)->resortAdmin)->full_name;
                    $supervisorPos  = optional(optional($supervisor)->position)->position_title;

                    $hodName = optional(optional($exit_clearance->hod)->resortAdmin)->full_name;
                    $hodPos  = optional(optional($exit_clearance->hod)->position)->position_title;
                    $hrName  = optional(optional($exit_clearance->hr)->resortAdmin)->full_name;
                    $hrPos   = optional(optional($exit_clearance->hr)->position)->position_title;

                    $statusBadge = function ($s) {
                        $s = trim((string) $s);
                        if ($s === '') return '<span class="badge badge-themeWarning">Pending</span>';
                        $cls = match ($s) {
                            'Approved', 'Completed' => 'badge-themeSuccess',
                            'Rejected'              => 'badge-themeDanger',
                            'On Hold'               => 'badge-themeSkyblue',
                            default                 => 'badge-themeWarning',
                        };
                        return '<span class="badge ' . $cls . '">' . e($s) . '</span>';
                    };
                @endphp
                <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                    <div class="col-lg-4">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0"><h3>Reporting Supervisor</h3></div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{ $supervisorName ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{ $supervisorPos ?: '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0"><h3>HOD</h3></div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{ $hodName ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{ $hodPos ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td>{!! $statusBadge($exit_clearance->hod_status) !!}</td>
                                        </tr>
                                        <tr>
                                            <th>Comments:</th>
                                            <td>{{ $exit_clearance->hod_comments ?: '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0"><h3>HR</h3></div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Name:</th>
                                            <td>{{ $hrName ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{ $hrPos ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td>{!! $statusBadge($exit_clearance->hr_status) !!}</td>
                                        </tr>
                                        <tr>
                                            <th>Comments:</th>
                                            <td>{{ $exit_clearance->hr_comments ?: '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-themeGrayLight mb-md-4 mb-3 @if(App\Helpers\Common::checkRouteWisePermission('people.exit-clearance',config('settings.resort_permissions.edit')) == false) d-none @endif">
                    <div class="card-title mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>Exit Interview Status</h3>
                            <a class="btn btn-themeSkyblue" href="{{route('people.exit-clearance.employeeFormAssignment', base64_encode($exit_clearance->id))}}">Assign Employee Form</a>
                        </div>
                    </div>
                    {{-- Empty-state: no clearance forms have been assigned
                         to this resignation yet. The section header used to
                         render blank when the @foreach found nothing; now
                         it points HR at the "Assign Employee Form" button. --}}
                    @if(count($exitClearanceFormAssignments) === 0)
                        <div class="text-center py-4" style="color:#888; font-size:13px;">
                            <i class="fa-regular fa-clipboard fa-2x mb-2" style="display:block; color:#bbb;"></i>
                            No exit interview / clearance forms have been assigned yet.
                            @if($is_hr)
                                Use <strong>Assign Employee Form</strong> above to add one.
                            @else
                                Nothing has been assigned to you for this resignation.
                            @endif
                        </div>
                    @endif
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
                                                <td>{{ $exitClearanceFormAssignment->status }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Departure Arrangements</h3>
                    </div>
                    <div class="row g-xxl-4 g-md-3 g-2">
                        <div class="col-xl-5 col-md-6">
                            <div class="form-check mb-md-4 mb-2">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="international_flight" value="option1" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['international_flight'] == 1) checked @endif>
                                    <label class="form-check-label" for="international_flight">Has the international flight
                                        ticket been booked?</label>
                            </div>
                            <div class="form-check  mb-md-4 mb-2">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="transportation_arranged" value="option2" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['transportation_arranged'] == 1) checked @endif>
                                    <label class="form-check-label" for="transportation_arranged">Has transportation been
                                        arranged?</label>
                            </div>
                            <div class="form-check">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="passport_validity" value="option3" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['passport_validity'] == 1) checked @endif>
                                    <label class="form-check-label" for="passport_validity">Has the employee’s passport validity been verified?
                                        @php
                                            // Dynamic passport + visa expiry pulled from the OCR-
                                            // extracted VisaEmployeeExpiryData rows. Was hardcoded
                                            // to "14 April 2025" before. Falls back to "N/A" so
                                            // the label still reads cleanly when no doc is on file.
                                            $_hrPassportExpiry = null;
                                            $_hrVisaExpiry = null;
                                            if (!empty($exit_clearance->employee->id)) {
                                                $row = \App\Models\VisaEmployeeExpiryData::where('resort_id', $exit_clearance->resort_id)
                                                    ->where('employee_id', $exit_clearance->employee->id)
                                                    ->where('DocumentName', 'Passport_Copy')
                                                    ->latest('id')->first();
                                                if ($row) {
                                                    $f = $row->Ai_extracted_data['extracted_fields'] ?? [];
                                                    $raw = $f['Date of Expiry'] ?? $f['Passport Expiry Date'] ?? null;
                                                    if ($raw) { try { $_hrPassportExpiry = \Carbon\Carbon::parse($raw)->format('d M Y'); } catch (\Throwable $e) {} }
                                                }
                                                $row = \App\Models\VisaEmployeeExpiryData::where('resort_id', $exit_clearance->resort_id)
                                                    ->where('employee_id', $exit_clearance->employee->id)
                                                    ->where('DocumentName', 'Visa')
                                                    ->latest('id')->first();
                                                if ($row) {
                                                    $f = $row->Ai_extracted_data['extracted_fields'] ?? [];
                                                    $raw = $f['Visa Expiry Date'] ?? null;
                                                    if ($raw) { try { $_hrVisaExpiry = \Carbon\Carbon::parse($raw)->format('d M Y'); } catch (\Throwable $e) {} }
                                                }
                                            }
                                        @endphp
                                        <span>(Passport Validity: {{ $_hrPassportExpiry ?: 'N/A' }} &middot; Visa Validity: {{ $_hrVisaExpiry ?: 'N/A' }})</span>
                                    </label>
                            </div>
                        </div>
                        <div class="col-xl-7 col-md-6">
                            <div class="form-check  mb-md-4 mb-2">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="accommodation_arranged" value="option4" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['accommodation_arranged'] == 1) checked @endif>
                                    <label class="form-check-label" for="accommodation_arranged">Has accommodation in Malé been
                                        arranged?</label>
                            </div>
                            <div class="form-check">
                                    <input class="form-check-input dep-arrangement-checkbox" type="checkbox" id="documentVerifed" value="option5" @if(isset($exit_clearance->departure_arrangements) && $exit_clearance->departure_arrangements['documentVerifed'] == 1) checked @endif>
                                    <label class="form-check-label" for="documentVerifed">Has the employee’s visa documentation
                                        been verified and cleared?</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row align-items-center g-2">
                        <div class="col-auto">
                            <a href="javascript:void(0)" onclick="sendEmploymentCertificate()" class="a-link">Send An Employment Certificate</a>
                        </div>
                        <div class="col-auto"><a href="javascript:void()" data-url="{{ route('people.exit-clearance.sendReminder',base64_encode($exit_clearance->id)) }}" class="a-linkTheme " id="send-reminder-btn">Send Reminder To Employee</a></div>
                        
                            {{-- Mark-as-Complete route is POST and expects a
                                 base64-encoded id. The previous markup passed
                                 the raw id and used an <a> — both wrong, so
                                 every click silently 404'd. Use a form with
                                 a confirm() so the action is intentional. --}}
                            @php
                                $_markAsCompleteUrl = route('people.exit-clearance.markAsComplete', base64_encode($exit_clearance->id));
                                $_isCompleted = ($exit_clearance->status ?? null) === 'Completed';
                            @endphp
                            @if($is_hr == false && $is_assigned == true)
                                <div class="col-auto ms-auto"> <a href="{{route('people.exit-clearance.department-form',base64_encode($exit_clearance->id))}}" class="btn btn-themeSkyblue btn-sm">Clearance Form</a></div>
                            @elseif($is_hr == true && $is_assigned == true)
                                <div class="col-auto ms-auto"> <a href="{{route('people.exit-clearance.department-form',base64_encode($exit_clearance->id))}}" class="btn btn-themeSkyblue btn-sm">Clearance Form</a></div>
                                <div class="col-auto"><a href="{{route('payroll.final.settlement')}}" class="btn  btn-themeBlue btn-sm">Full And Final Settlement</a></div>
                                @if($_isCompleted)
                                    <div class="col-auto"><span class="badge badge-themeSuccess px-3 py-2"><i class="fa-solid fa-check me-1"></i> Completed</span></div>
                                @else
                                    <div class="col-auto">
                                        {{-- Confirmation modal pattern matches the
                                             department view — see #markCompleteModal
                                             defined below. --}}
                                        <button type="button" class="btn btn-themeGreenNew btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#markCompleteModal">
                                            Mark As Completed
                                        </button>
                                    </div>
                                @endif
                            @elseif($is_hr == true && $is_assigned == false)
                                <div class="col-auto ms-auto"><a href="{{route('payroll.final.settlement')}}" class="btn  btn-themeBlue btn-sm">Full And Final Settlement</a></div>
                                @if($_isCompleted)
                                    <div class="col-auto"><span class="badge badge-themeSuccess px-3 py-2"><i class="fa-solid fa-check me-1"></i> Completed</span></div>
                                @else
                                    <div class="col-auto">
                                        {{-- Confirmation modal pattern matches the
                                             department view — see #markCompleteModal
                                             defined below. --}}
                                        <button type="button" class="btn btn-themeGreenNew btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#markCompleteModal">
                                            Mark As Completed
                                        </button>
                                    </div>
                                @endif
                            @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mark-As-Completed confirmation modal + hidden submission form.
         Shared by both Mark As Completed buttons in the footer above. --}}
    @if(!$_isCompleted)
        <form id="markCompleteForm" action="{{ $_markAsCompleteUrl }}" method="POST" class="d-none">
            @csrf
        </form>
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
                            This finalises the employee's offboarding — the
                            resignation moves to Completed and the employee's
                            status flips from Offboarding to Terminated. This
                            action cannot be undone.
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
    @endif
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
            autoclose: true
        });

        $('#deptFilter, #positionFilter, #statusFilter, #datapicker').on('change', function () {
            getExitClearanceData();
        });
    
    });

    // Modal Confirm → submit the hidden Mark-As-Completed form. Replaces
    // the old onsubmit="return confirm(...)" native browser dialog.
    $(document).on('click', '#markCompleteConfirmBtn', function () {
        $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Marking…');
        $('#markCompleteForm').trigger('submit');
    });

    $('#send-reminder-btn').on('click', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.message , "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function (xhr) {
                var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong while sending the reminder.';
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
            }
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
            processing: true,
            serverSide: true,
            order:[[8, 'desc']],
            ajax: {
                url: "{{ route('people.exit-clearance') }}",
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
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
    
</script>
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

function sendEmploymentCertificate() {
    $.ajax({
        url: "{{ route('people.exit-clearance.employement-certificate', base64_encode($exit_clearance->id)) }}",
        type: "GET",
        success: function(response) {
            if(response.success) {
                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
            }else{
                toastr.error(response.message || 'Something went wrong!', "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        },
        error: function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Something went wrong while sending the certificate.';
            toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
        }
    });
}
</script>
@endsection
