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
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto"><a href="#" class="btn btn-theme">Download</a></div> -->
                </div>
            </div>

            <form id="investigationMeeting" enctype="multipart/form-data">
                @csrf
                <div class="card">
                    {{-- Row 1: Incident ID · Meeting Subject · Scheduled Date --}}
                    <div class="row g-lg-4 g-3 mb-3">
                        <div class="col-md-4 col-sm-6">
                            <label for="incident_id" class="form-label">INCIDENT ID <span class="red-mark">*</span></label>
                            <input type="hidden" class="form-control" name="incidentId" id="incidentId" value="{{ $incident->id }}" readonly />
                            <input type="text" class="form-control" name="incident_id" id="incident_id" value="{{ $incident->incident_id }}" readonly required/>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">MEETING SUBJECT <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" name="meeting_subject" placeholder="Meeting Subject" required
                                data-parsley-required-message="Please enter meeting subject"
                                data-parsley-script-tag="true"
                                data-parsley-html="true"/>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">SCHEDULED DATE <span class="red-mark">*</span></label>
                            <input type="text" class="form-control datepicker" id="schedule_date" name="meeting_date" placeholder="Select Date" required
                                data-parsley-required-message="Please select scheduled date"
                                data-parsley-script-tag="true"
                                data-parsley-html="true"/>
                        </div>
                    </div>

                    {{-- Row 2: Scheduled Time · Meeting Location · Meeting Type --}}
                    <div class="row g-lg-4 g-3 mb-3">
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">SCHEDULED TIME <span class="red-mark">*</span></label>
                            <input type="time" class="form-control" name="meeting_time" placeholder="Select Time" required/>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">MEETING LOCATION <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" id="meeting_location" name="location" required
                                data-parsley-required-message="Please select meeting location"
                                data-parsley-errors-container="#meeting_location-error">
                                <option value="">Select Location</option>
                                <option value="HR Office">HR Office</option>
                                <option value="Conference Room">Conference Room</option>
                                <option value="Manager's Office">Manager's Office</option>
                                <option value="Training Room">Training Room</option>
                                <option value="Site / Department Floor">Site / Department Floor</option>
                                <option value="__custom__">Other / Virtual link…</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="meeting_location_custom" name="location_custom" placeholder="Enter location or virtual meeting link" />
                            <div id="meeting_location-error"></div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">MEETING TYPE <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" name="meeting_type" required
                                data-parsley-required-message="Please select meeting type"
                                data-parsley-errors-container="#meeting_type-error">
                                <option value="">Select Type</option>
                                <option value="Physical">Physical</option>
                                <option value="Online">Virtual</option>
                            </select>
                            <div id="meeting_type-error"></div>
                        </div>
                    </div>

                    {{-- Row 3+: Participants · Roles (repeatable rows) --}}
                    <div id="participants-div">
                        <div class="row g-lg-4 g-3 mb-3 participant-row">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" name="participants[]" required
                                    data-parsley-required-message="Please select participants"
                                    data-parsley-errors-container="#participants-error">
                                    <option value="">Select Employee</option>
                                    @foreach ($participants as $participant)
                                        <option value="{{ $participant->id }}">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</option>
                                    @endforeach
                                </select>
                                <div id="participants-error"></div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">ROLES <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" name="roles[]" required
                                    data-parsley-required-message="Please select role">
                                    <option value="">Select Role</option>
                                    <option value="Victim">Victim</option>
                                    <option value="Witness">Witness</option>
                                    <option value="Accused">Accused</option>
                                    <option value="Investigator">Investigator</option>
                                    <option value="Observer">Observer</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6 d-flex align-items-end">
                                <a href="#" class="btn eb-btn-accent btn-sm" id="addMoreParticipants">+ Add Participant</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <a href="#" class="btn eb-btn-accent btn-sm" id="add-external-participants">Add External Participants</a>
                    </div>

                    <div class="row g-3 mb-3" id="external-participants"></div>

                    {{-- Meeting Agenda --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">MEETING AGENDA <span class="red-mark">*</span></label>
                            <textarea class="form-control" name="meeting_agenda" rows="5" placeholder="Meeting Agenda" required
                                data-parsley-required-message="Please enter meeting agenda"
                                data-parsley-script-tag="true"
                                data-parsley-html="true"></textarea>
                        </div>
                    </div>

                    {{-- Attachments --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">ATTACHMENTS</label>
                            <div class="uploadFile-block">
                                <div class="uploadFile-btn mb-2">
                                    <a href="#" class="btn eb-btn-accent btn-sm" onclick="event.preventDefault(); document.getElementById('uploadFile').click();">Upload Files</a>
                                    <input type="file" name="attachments[]" id="uploadFile" multiple style="display:none;">
                                </div>
                                <div class="uploadFile-text mb-2 text-muted small">Photos, Documents, Or Videos</div>
                            </div>
                            <ul id="file-list" class="mt-2"></ul>
                        </div>
                    </div>

                    {{-- Previous Notes / Findings — read-only summary of past
                         meeting agendas on this incident, so the user has
                         context before scheduling the next one. --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">PREVIOUS NOTES / FINDINGS</label>
                            @if($previousMeetings->isEmpty())
                                <p class="text-muted mb-0">No previous meetings have been recorded for this incident.</p>
                            @else
                                <div class="previous-notes-block">
                                    @foreach($previousMeetings as $pm)
                                        <div class="previous-note-item mb-3 pb-3 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong>{{ $pm->meeting_subject }}</strong>
                                                <span class="text-muted small">
                                                    {{ \Carbon\Carbon::parse($pm->meeting_date)->format('d M Y') }}
                                                    @if($pm->meeting_time)
                                                        · {{ \Carbon\Carbon::parse($pm->meeting_time)->format('h:i A') }}
                                                    @endif
                                                </span>
                                            </div>
                                            <p class="mb-0 text-body small">{{ $pm->meeting_agenda }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn eb-btn-primary">Submit</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    $('.select2t-none').select2();

    $('#investigationMeeting').parsley({
        trigger: 'change',
        errorsContainer: function (el) {
            return el.$element.closest('.form-group');
        },
        errorClass: 'is-invalid',
        successClass: 'is-valid'
    });

    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        clearBtn: true,
        container: 'body',
        orientation: 'bottom auto'
    });

    document.getElementById('uploadFile').addEventListener('change', function (e) {
        let fileList = document.getElementById('file-list');
        fileList.innerHTML = '';
        Array.from(this.files).forEach(function(file) {
            
            let li = document.createElement('li');
            li.textContent = file.name;
            fileList.appendChild(li);
        });
    });

    // When "Other / Virtual link…" is picked, show a free-text field and use
    // its value as the actual posted location. Server-side `location` field
    // accepts string up to 255 chars.
    $(document).on('change', '#meeting_location', function () {
        var $custom = $('#meeting_location_custom');
        if ($(this).val() === '__custom__') {
            $custom.removeClass('d-none').attr('required', 'required').attr('name', 'location');
            $(this).removeAttr('name');
        } else {
            $custom.addClass('d-none').removeAttr('required').removeAttr('name').val('');
            $(this).attr('name', 'location');
        }
    });

    let number = 0;
    $('#addMoreParticipants').click(function (e) {
        e.preventDefault();
        let row = `
            <div class="row g-lg-4 g-3 mb-3 participant-row">
                <div class="col-md-4 col-sm-6">
                    <select class="form-select select2t-none" name="participants[]" required data-parsley-required-message="Please select participants" data-parsley-errors-container="#participants-error-${number}">
                        <option value="">Select Employee</option>
                        @foreach($participants as $participant)
                            <option value="{{ $participant->id }}">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</option>
                        @endforeach
                    </select>
                    <div id="participants-error-${number}"></div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <select class="form-select select2t-none" name="roles[]" required data-parsley-required-message="Please select role">
                        <option value="">Select Role</option>
                        <option value="Victim">Victim</option>
                        <option value="Witness">Witness</option>
                        <option value="Accused">Accused</option>
                        <option value="Investigator">Investigator</option>
                        <option value="Observer">Observer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6 d-flex align-items-end">
                    <a href="#" class="btn eb-btn-critical btn-sm removeParticipant">Remove</a>
                </div>
            </div>`;
        $('#participants-div').append(row);
        $('.select2t-none').select2();
        number++;
    });

    $(document).on('click', '.removeParticipant', function (e) {
        e.preventDefault();
        $(this).closest('.participant-row').remove();
    });

    $('#add-external-participants').click(function (e) {
        e.preventDefault();
        let extRow = `
            <div class="row g-3 mb-3 external-row">
                <div class="col-md-4 col-sm-6">
                    <input type="text" class="form-control" name="ext_participants[]" placeholder="External Participant Name" />
                </div>
                <div class="col-md-4 col-sm-6 d-flex align-items-end">
                    <a href="#" class="btn eb-btn-critical btn-sm removeExternal">Remove</a>
                </div>
            </div>`;
        $('#external-participants').append(extRow);
    });

    $(document).on('click', '.removeExternal', function (e) {
        e.preventDefault();
        $(this).closest('.external-row').remove();
    });

    $('#investigationMeeting').submit(function (e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('incident.meeting.store') }}",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                toastr.success(res.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
                setTimeout(function() {
                    window.location.href = res.redirect_url;
                }, 2000);
            },
            error: function (xhr) {
                let err = xhr.responseJSON.errors;
                let msg = Object.values(err).flat().join("\n");
                toastr.error(msg, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
});
</script>

@endsection