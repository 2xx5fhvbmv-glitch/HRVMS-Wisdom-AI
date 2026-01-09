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
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">SCHEDULED TIME <span class="red-mark">*</span></label>
                            <input type="time" class="form-control" name="meeting_time" placeholder="Select Time" required/>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">MEETING TYPE <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" name="meeting_type" required data-parsley-required-message="Please select meeting type" data-parsley-errors-container="#meeting_type-error">
                                <option value="">Select Type</option>
                                <option value="Physical">Physical</option>
                                <option value="Online">Virtual</option>
                            </select>
                            <div id="meeting_type-error"></div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">MEETING LOCATION / LINK <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" name="location" placeholder="Meeting Location or Link" required 
                            data-parsley-required-message="Please select scheduled date"
                            data-parsley-script-tag="true"
                            data-parsley-html="true"/>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="participants-div">
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">PARTICIPANTS <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" name="participants[]" required data-parsley-required-message="Please select participants" data-parsley-errors-container="#participants-error">
                                <option value="">Select Employee</option>
                                @foreach ($participants as $participant)
                                    <option value="{{ $participant->id }}">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</option>
                                @endforeach
                            </select>
                            <div id="participants-error"></div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <label class="form-label">ROLES <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" name="roles[]" placeholder="Role" required 
                            data-parsley-required-message="Please enter role"
                            data-parsley-script-tag="true"
                            data-parsley-html="true" />
                        </div>
                        <div class="col-md-4 col-sm-6 d-flex align-items-end">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="addMoreParticipants">Add More Participants</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <a href="#" class="btn btn-themeSkyblue btn-sm" id="add-external-participants">Add External Participants</a>
                    </div>

                    <div class="row g-3 mb-3" id="external-participants"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">MEETING AGENDA <span class="red-mark">*</span></label>
                            <textarea class="form-control" name="meeting_agenda" rows="5" placeholder="Agenda Details" required 
                            data-parsley-required-message="Please enter meeting agenda"
                            data-parsley-script-tag="true"
                            data-parsley-html="true"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ATTACHMENTS </label>
                            <div class="uploadFile-block">
                                <div class="uploadFile-btn mb-2">
                                    <a href="#" class="btn btn-themeBlue btn-sm" onclick="event.preventDefault(); document.getElementById('uploadFile').click();" required>Upload Files</a>
                                    <input type="file" name="attachments[]" id="uploadFile" multiple style="display:none;">
                                </div>
                                <div class="uploadFile-text mb-2">Photos, Documents, Or Videos</div>
                                <!-- Move file list to bottom of upload button -->
                            </div>
                            <ul id="file-list" class="mt-2"></ul>
                            
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
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

    let number = 0;
    $('#addMoreParticipants').click(function (e) {
        e.preventDefault();
        let row = `
            <div class="row g-3 mb-3 participant-row">
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
                    <input type="text" class="form-control" name="roles[]" placeholder="Role" required 
                            data-parsley-required-message="Please enter role"
                            data-parsley-script-tag="true"
                            data-parsley-html="true"/>
                </div>
                <div class="col-md-4 col-sm-6 d-flex align-items-end">
                    <a href="#" class="btn btn-danger btn-sm removeParticipant">Remove</a>
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
                    <a href="#" class="btn btn-danger btn-sm removeExternal">Remove</a>
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