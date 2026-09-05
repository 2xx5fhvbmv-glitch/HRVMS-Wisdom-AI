@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #incident-meeting-create-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #incident-meeting-create-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="incident-meeting-create-hero">
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

            <div class="imc-wrap">
            <form id="investigationMeeting" enctype="multipart/form-data">
                @csrf

                {{-- Meeting details --}}
                <div class="imc-card">
                    <div class="imc-sec-h">Meeting details</div>
                    <div class="imc-grid3">
                        <div class="imc-fld">
                            <label for="incident_id">Incident ID <span class="req">*</span></label>
                            <input type="hidden" name="incidentId" id="incidentId" value="{{ $incident->id }}" readonly />
                            <input type="text" class="form-control ctrl ro" name="incident_id" id="incident_id" value="{{ $incident->incident_id }}" readonly required/>
                        </div>
                        <div class="imc-fld">
                            <label>Meeting subject <span class="req">*</span></label>
                            <input type="text" class="form-control ctrl" name="meeting_subject" placeholder="Meeting subject" required
                                data-parsley-required-message="Please enter meeting subject"
                                data-parsley-script-tag="true"
                                data-parsley-html="true"/>
                        </div>
                        <div class="imc-fld">
                            <label>Scheduled date <span class="req">*</span></label>
                            <input type="text" class="form-control ctrl datepicker" id="schedule_date" name="meeting_date" placeholder="Select date" required
                                data-parsley-required-message="Please select scheduled date"
                                data-parsley-script-tag="true"
                                data-parsley-html="true"/>
                        </div>
                        <div class="imc-fld">
                            <label>Scheduled time <span class="req">*</span></label>
                            <input type="text" class="form-control ctrl timePicker" id="schedule_time" name="meeting_time" placeholder="Select time" required
                                data-parsley-required-message="Please select scheduled time"/>
                        </div>
                        <div class="imc-fld">
                            <label>Meeting location <span class="req">*</span></label>
                            <select class="form-select dd-native-select ctrl" id="meeting_location" name="location" required
                                data-parsley-required-message="Please select meeting location"
                                data-parsley-errors-container="#meeting_location-error">
                                <option value="">Select location</option>
                                <option value="HR Office">HR Office</option>
                                <option value="Conference Room">Conference Room</option>
                                <option value="Manager's Office">Manager's Office</option>
                                <option value="Training Room">Training Room</option>
                                <option value="Site / Department Floor">Site / Department Floor</option>
                                <option value="__custom__">Other / Virtual link…</option>
                            </select>
                            <div class="dd" data-target="#meeting_location">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select location</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Meeting Location">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select location</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="HR Office"><span class="dd-nm">HR Office</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Conference Room"><span class="dd-nm">Conference Room</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Manager's Office"><span class="dd-nm">Manager's Office</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Training Room"><span class="dd-nm">Training Room</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Site / Department Floor"><span class="dd-nm">Site / Department Floor</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="__custom__"><span class="dd-nm">Other / Virtual link…</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                            <input type="text" class="form-control ctrl mt-2 d-none" id="meeting_location_custom" name="location_custom" placeholder="Enter location or virtual meeting link" />
                            <div id="meeting_location-error"></div>
                        </div>
                        <div class="imc-fld">
                            <label>Meeting type <span class="req">*</span></label>
                            <select class="form-select dd-native-select ctrl" id="meeting_type" name="meeting_type" required
                                data-parsley-required-message="Please select meeting type"
                                data-parsley-errors-container="#meeting_type-error">
                                <option value="">Select type</option>
                                <option value="Physical">Physical</option>
                                <option value="Online">Virtual</option>
                            </select>
                            <div class="dd" data-target="#meeting_type">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select type</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Meeting Type">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Physical"><span class="dd-nm">Physical</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Online"><span class="dd-nm">Virtual</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                            <div id="meeting_type-error"></div>
                        </div>
                    </div>
                </div>

                {{-- Participants --}}
                <div class="imc-card">
                    <div class="imc-sec-h">Participants</div>
                    <div class="imc-pcols">
                        <div class="imc-pcol">
                            <div class="imc-pcol-h">Internal</div>
                            <div id="participants-div">
                                <div class="imc-prow participant-row">
                                    <div class="imc-pfields">
                                        <div class="imc-fld">
                                            <label>Participant <span class="req">*</span></label>
                                            <select class="form-select dd-native-select ctrl imc-participant-select" id="participant_static" name="participants[]" required
                                                data-parsley-required-message="Please select participants"
                                                data-parsley-errors-container="#participants-error">
                                                <option value="">Select employee</option>
                                                @foreach ($participants as $participant)
                                                    <option value="{{ $participant->id }}">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="dd" data-target="#participant_static">
                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                    <span class="dd-lbl">Select employee</span>
                                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                </button>
                                                <div class="dd-panel" role="listbox" aria-label="Participant">
                                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                                    <div class="dd-scroll">
                                                        @foreach ($participants as $participant)
                                                            <div class="dd-item" role="option" data-value="{{ $participant->id }}"><span class="dd-nm">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="participants-error"></div>
                                        </div>
                                        <div class="imc-fld">
                                            <label>Role <span class="req">*</span></label>
                                            <select class="form-select dd-native-select ctrl" id="role_static" name="roles[]" required
                                                data-parsley-required-message="Please select role">
                                                <option value="">Select role</option>
                                                <option value="Victim">Victim</option>
                                                <option value="Witness">Witness</option>
                                                <option value="Accused">Accused</option>
                                                <option value="Investigator">Investigator</option>
                                                <option value="Observer">Observer</option>
                                                <option value="Other">Other</option>
                                            </select>
                                            <div class="dd" data-target="#role_static">
                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                    <span class="dd-lbl">Select role</span>
                                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                </button>
                                                <div class="dd-panel" role="listbox" aria-label="Role">
                                                    <div class="dd-scroll">
                                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select role</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="Victim"><span class="dd-nm">Victim</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="Witness"><span class="dd-nm">Witness</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="Accused"><span class="dd-nm">Accused</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="Investigator"><span class="dd-nm">Investigator</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="Observer"><span class="dd-nm">Observer</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="Other"><span class="dd-nm">Other</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="#" class="btn eb-btn-accent btn-sm mt-2" id="addMoreParticipants"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="margin-right:5px;vertical-align:-2px"><path d="M12 5v14M5 12h14"/></svg>Add participant</a>
                        </div>

                        <div class="imc-pcol">
                            <div class="imc-pcol-h">External</div>
                            <a href="#" class="btn eb-btn-accent btn-sm" id="add-external-participants"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;vertical-align:-2px"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM19 8v6M22 11h-6"/></svg>Add external participant</a>
                            <div class="imc-ext-block" id="external-participants"></div>
                        </div>
                    </div>
                </div>

                {{-- Agenda & attachments --}}
                <div class="imc-card">
                    <div class="imc-sec-h">Agenda &amp; attachments</div>
                    <div class="imc-fld mb-3">
                        <label>Meeting agenda <span class="req">*</span></label>
                        <textarea class="form-control ctrl" name="meeting_agenda" rows="5" placeholder="Meeting agenda — points to cover, order of discussion, expected outcomes" required
                            data-parsley-required-message="Please enter meeting agenda"
                            data-parsley-script-tag="true"
                            data-parsley-html="true"></textarea>
                    </div>
                    <div class="imc-fld">
                        <label>Attachments</label>
                        <div class="uploadFile-block imc-upload">
                            <div class="uploadFile-btn">
                                <a href="#" class="btn eb-btn-accent btn-sm" onclick="event.preventDefault(); document.getElementById('uploadFile').click();"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;vertical-align:-2px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>Upload files</a>
                                <input type="file" name="attachments[]" id="uploadFile" multiple style="display:none;">
                            </div>
                            <span class="hint">Photos, documents, or videos</span>
                        </div>
                        <ul id="file-list" class="imc-files"></ul>
                    </div>
                </div>

                {{-- Previous Notes / Findings — read-only summary of past
                     meeting agendas on this incident, so the user has
                     context before scheduling the next one. --}}
                <div class="imc-card">
                    <div class="imc-sec-h">Previous notes / findings</div>
                    @if($previousMeetings->isEmpty())
                        <div class="imc-empty">
                            <div class="g"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg></div>
                            <div class="t">No previous meetings recorded</div>
                            <div class="s">Once meetings are held for this incident, their notes and findings will appear here.</div>
                        </div>
                    @else
                        <div class="imc-notes">
                            @foreach($previousMeetings as $pm)
                                <div class="imc-note">
                                    <div>
                                        <div class="imc-note-t">{{ $pm->meeting_subject }}</div>
                                        <div class="imc-note-d">{{ $pm->meeting_agenda }}</div>
                                    </div>
                                    <div class="imc-note-time">
                                        {{ \Carbon\Carbon::parse($pm->meeting_date)->format('d M Y') }}
                                        @if($pm->meeting_time)
                                            &middot; {{ \Carbon\Carbon::parse($pm->meeting_time)->format('h:i A') }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="imc-footer-bar">
                    <a href="{{ route('incident.meeting') }}" class="btn eb-btn-secondary">Cancel</a>
                    <button type="submit" class="btn eb-btn-primary">Submit</button>
                </div>
            </form>
            </div>

        </div>
    </div>
@include('resorts.incident.meeting._meeting_create_styles')
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {

    $('#investigationMeeting').parsley({
        trigger: 'change',
        errorsContainer: function (el) {
            return el.$element.closest('.form-group');
        },
        errorClass: 'is-invalid',
        successClass: 'is-valid'
    });

    flatpickr('.datepicker', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        appendTo: document.body
    });

    // Brand-styled time picker (matches the site-wide clock-icon input.timePicker
    // CSS already defined in default.css) instead of the native browser time
    // widget. 24-hour output — matches what the native <input type="time"> this
    // replaces already submitted, so already-stored meeting_time values and the
    // Carbon::parse()/strtotime() reads elsewhere keep working unchanged.
    flatpickr('.timePicker', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        allowInput: true,
        appendTo: document.body
    });

    function imcFormatBytes(bytes) {
        if (!bytes) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + units[i];
    }

    document.getElementById('uploadFile').addEventListener('change', function (e) {
        let fileList = document.getElementById('file-list');
        fileList.innerHTML = '';
        Array.from(this.files).forEach(function(file) {
            let li = document.createElement('li');
            li.className = 'imc-filerow';
            li.title = file.name;
            li.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>'
                + '<span class="fn"></span><span class="fsz">' + imcFormatBytes(file.size) + '</span>';
            li.querySelector('.fn').textContent = file.name;
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
    var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
    var participantItems = `
        @foreach($participants as $participant)
            <div class="dd-item" role="option" data-value="{{ $participant->id }}"><span class="dd-nm">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</span>${tickSvg}</div>
        @endforeach
    `;
    var roleItems = ['Victim', 'Witness', 'Accused', 'Investigator', 'Observer', 'Other'].map(function (r) {
        return `<div class="dd-item" role="option" data-value="${r}"><span class="dd-nm">${r}</span>${tickSvg}</div>`;
    }).join('');
    $('#addMoreParticipants').click(function (e) {
        e.preventDefault();
        let row = `
            <div class="imc-prow participant-row">
                <div class="imc-pfields">
                    <div class="imc-fld">
                        <label>Participant <span class="req">*</span></label>
                        <select class="form-select dd-native-select ctrl" id="participant_${number}" name="participants[]" required data-parsley-required-message="Please select participants" data-parsley-errors-container="#participants-error-${number}">
                            <option value="">Select employee</option>
                            @foreach($participants as $participant)
                                <option value="{{ $participant->id }}">{{ $participant->Emp_id . ' : ' . $participant->resortAdmin->full_name }}</option>
                            @endforeach
                        </select>
                        <div class="dd" data-target="#participant_${number}">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select employee</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Participant">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                <div class="dd-scroll">${participantItems}</div>
                            </div>
                        </div>
                        <div id="participants-error-${number}"></div>
                    </div>
                    <div class="imc-fld">
                        <label>Role <span class="req">*</span></label>
                        <select class="form-select dd-native-select ctrl" id="role_${number}" name="roles[]" required data-parsley-required-message="Please select role">
                            <option value="">Select role</option>
                            <option value="Victim">Victim</option>
                            <option value="Witness">Witness</option>
                            <option value="Accused">Accused</option>
                            <option value="Investigator">Investigator</option>
                            <option value="Observer">Observer</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="dd" data-target="#role_${number}">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select role</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Role">
                                <div class="dd-scroll">${roleItems}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="#" class="btn eb-btn-critical btn-sm removeParticipant">Remove</a>
            </div>`;
        $('#participants-div').append(row);
        number++;
    });

    $(document).on('click', '.removeParticipant', function (e) {
        e.preventDefault();
        $(this).closest('.participant-row').remove();
    });

    $('#add-external-participants').click(function (e) {
        e.preventDefault();
        let extRow = `
            <div class="imc-extrow external-row">
                <input type="text" class="form-control ctrl" name="ext_participants[]" placeholder="External participant name" />
                <a href="#" class="btn eb-btn-critical btn-sm removeExternal">Remove</a>
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