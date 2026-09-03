@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #disciplinary-create-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #disciplinary-create-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="disciplinary-create-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Grievance And Disciplinery</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>

            </div>
        </div>

        <div class="cdi-wrap">
        <form id="disciplinaryForm" enctype="multipart/form-data" data-parsley-validate>
            @csrf
            <div class="cdi-shell">

                {{-- ===== form ===== --}}
                <div class="cdi-card">
                    <div class="cdi-sec-h">Disciplinary details</div>

                    <div class="cdi-grid2">
                        <div class="cdi-fld">
                            <label>Employee <span class="req">*</span></label>
                            <select class="form-select ctrl dd-native-select" name="Employee_id" id="Employee_id"
                                required data-parsley-required-message="Please select an employee"
                                data-parsley-errors-container="#employee-error-list">
                                <option value=""></option>
                                @foreach($Employee ?? [] as $item)
                                    <option value="{{ base64_encode($item->id) }}">
                                        {{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#Employee_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Employee</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Employee">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                    <div class="dd-scroll">
                                        @foreach($Employee ?? [] as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="employee-error-list" class="text-danger small"></div>
                        </div>

                        <div class="cdi-fld">
                            <label>Offence <span class="req">*</span></label>
                            <select class="form-select ctrl dd-native-select" name="Offence_id" id="Offence_id"
                                required data-parsley-required-message="Please select an offence"
                                data-parsley-errors-container="#offence-error-list">
                                <option value=""></option>
                                @foreach($Offenses ?? [] as $off)
                                    <option
                                        value="{{ base64_encode($off->id) }}"
                                        data-cat="{{ base64_encode($off->disciplinary_cat_id) }}"
                                        data-severity="{{ $off->default_severity_id ? base64_encode($off->default_severity_id) : '' }}"
                                        data-action="{{ $off->default_action_id ? base64_encode($off->default_action_id) : '' }}"
                                        data-desc="{{ $off->offensesdescription }}">
                                        {{ $off->OffensesName }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#Offence_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Offence</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Offence">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an offence…"></div>
                                    <div class="dd-scroll">
                                        @foreach($Offenses ?? [] as $off)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($off->id) }}"><span class="dd-nm">{{ $off->OffensesName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="offence-error-list" class="text-danger small"></div>
                        </div>
                    </div>

                    <div class="cdi-grid3">
                        <div class="cdi-fld">
                            <label>Category <span class="req">*</span><span id="categoryAutoHint" class="cdi-autobadge"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 007 0l3-3a5 5 0 00-7-7l-1 1M14 11a5 5 0 00-7 0l-3 3a5 5 0 007 7l1-1"/></svg>Auto</span></label>
                            <select class="form-select ctrl dd-native-select" name="Category_id" id="Category_id"
                                required data-parsley-required-message="Please select a category"
                                data-parsley-errors-container="#category-error-list">
                                <option value=""></option>
                                @foreach($DisciplinaryCategories ?? [] as $item)
                                    <option value="{{ base64_encode($item->id) }}">{{ $item->DisciplinaryCategoryName }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#Category_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Category</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Category">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                    <div class="dd-scroll">
                                        @foreach($DisciplinaryCategories ?? [] as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->DisciplinaryCategoryName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="category-error-list" class="text-danger small"></div>
                        </div>

                        <div class="cdi-fld">
                            <label>Action <span class="req">*</span><span id="actionAutoHint" class="cdi-autobadge"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 007 0l3-3a5 5 0 00-7-7l-1 1M14 11a5 5 0 00-7 0l-3 3a5 5 0 007 7l1-1"/></svg>Auto</span></label>
                            <select class="form-select ctrl dd-native-select" name="Action_id" id="Action_id"
                                required data-parsley-required-message="Please select an action"
                                data-parsley-errors-container="#action-error-list">
                                <option value=""></option>
                                @foreach($ActionStore ?? [] as $item)
                                    <option value="{{ base64_encode($item->id) }}">{{ $item->ActionName }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#Action_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Action</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Action">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an action…"></div>
                                    <div class="dd-scroll">
                                        @foreach($ActionStore ?? [] as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->ActionName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="action-error-list" class="text-danger small"></div>
                        </div>

                        <div class="cdi-fld">
                            <label>Severity <span class="req">*</span><span id="severityAutoHint" class="cdi-autobadge"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 007 0l3-3a5 5 0 00-7-7l-1 1M14 11a5 5 0 00-7 0l-3 3a5 5 0 007 7l1-1"/></svg>Auto</span></label>
                            <select class="form-select ctrl dd-native-select" name="Severity_id" id="Severity_id"
                                required data-parsley-required-message="Please select severity"
                                data-parsley-errors-container="#severity-error-list">
                                <option value=""></option>
                                @foreach($SeverityStore ?? [] as $item)
                                    <option value="{{ base64_encode($item->id) }}">{{ $item->SeverityName }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#Severity_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Severity</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Severity">
                                    <div class="dd-scroll">
                                        @foreach($SeverityStore ?? [] as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->SeverityName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="severity-error-list" class="text-danger small"></div>
                        </div>
                    </div>

                    <div class="cdi-grid2">
                        <div class="cdi-fld">
                            <label>Action valid until <span class="req">*</span></label>
                            <input type="text" name="Expiry_date" class="form-control ctrl Expiry_date datepicker"
                                required data-parsley-required-message="Please select an expiry date"
                                placeholder="Select date">
                        </div>

                        {{-- Priority — temporarily hidden per request. Default value
                             'Medium' kept as a hidden input so existing controllers /
                             storage logic that read `priority_level` still receive a
                             valid value. Restore the visible block below to re-enable.
                        <div class="cdi-fld">
                            <label>Priority <span class="req">*</span></label>
                            <select class="form-select select2t-none ctrl" name="priority_level" id="priority_level"
                                required data-parsley-required-message="Please select a priority"
                                data-parsley-errors-container="#priority-error-list">
                                <option value="" disabled selected>Select Priority</option>
                                <option value="High">High</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Low">Low</option>
                            </select>
                            <div id="priority-error-list" class="text-danger small"></div>
                        </div>
                        --}}
                        <input type="hidden" name="priority_level" value="Medium">

                        <div class="cdi-fld">
                            <label>Assign to</label>
                            <select class="form-select ctrl dd-native-select" id="assign_to" name="assign_to"
                                data-parsley-errors-container="#assign-error-list">
                                <option value=""></option>
                                @foreach($committiee ?? [] as $c)
                                    <option value="{{ $c->id }}">{{ $c->CommitteeName }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#assign_to">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Committee</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Committee">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a committee…"></div>
                                    <div class="dd-scroll">
                                        @foreach($committiee ?? [] as $c)
                                            <div class="dd-item" role="option" data-value="{{ $c->id }}"><span class="dd-nm">{{ $c->CommitteeName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="assign-error-list" class="text-danger small"></div>
                        </div>
                    </div>

                    <div class="cdi-fld">
                        <label>Incident description</label>
                        <textarea class="form-control ctrl" id="incident-description" name="incident_description"
                            rows="3" placeholder="Describe the incident…"></textarea>
                    </div>

                    <div class="cdi-fld">
                        <label>Witnesses <span class="cdi-optional">(optional)</span></label>
                        <select class="form-select select2 ctrl" name="select_witness[]" multiple id="select-witness">
                            <option value="">Select witness</option>
                            @foreach($Employee ?? [] as $item)
                                <option value="{{ base64_encode($item->id) }}">
                                    {{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cdi-grid2">
                        <div class="cdi-fld">
                            <label>Attachment</label>
                            <div class="uploadFile-block cdi-upload">
                                <div class="uploadFile-btn">
                                    <a href="#" class="btn eb-btn-accent btn-sm">Upload file</a>
                                    <input type="file" name="attachment[]" id="attachment"
                                        accept=".png,.jpg,.jpeg,.heic,.heif,image/heic,image/heif,.pdf" multiple>
                                </div>
                                <span class="hint">PNG, JPEG, HEIC, PDF</span>
                            </div>
                            <ul id="attachment-preview" class="cdi-files"></ul>
                        </div>

                        <div class="cdi-fld">
                            <label>Signed document</label>
                            <div class="uploadFile-block cdi-upload">
                                <div class="uploadFile-btn">
                                    <a href="#" class="btn eb-btn-accent btn-sm">Upload file</a>
                                    <input type="file" name="upload_signed_document" id="upload-signed-document"
                                        accept=".png,.jpg,.jpeg,.heic,.heif,image/heic,image/heif,.pdf">
                                </div>
                                <span class="hint">PNG, JPEG, HEIC, PDF</span>
                            </div>
                            <ul id="signed-preview" class="cdi-files"></ul>
                        </div>
                    </div>

                    <div class="cdi-mfoot"><button type="submit" class="btn eb-btn-primary Submitme">Submit</button></div>
                </div>

                {{-- ===== Active Offences — vertical stacked list ===== --}}
                <div class="cdi-card cdi-ao">
                    <div class="cdi-ao-h"><span class="ttl">Active Offences</span><span class="cnt tnum" id="aoCount">0</span></div>
                    <div class="cdi-ao-sub" id="aoSub">Select an employee to see their active record</div>
                    <div class="cdi-ao-list" id="aoList"></div>

                    {{-- DataTables' real ajax engine stays bound to this table
                         exactly as before (same server-side endpoint, same
                         columns, same Employee-scoped fetch) — only the <table>
                         itself is hidden (.cdi-ao-datatable-host CSS); its visible
                         rendering is swapped for the cards above via drawCallback.
                         Fetches every row in one request (no paging) — the list
                         scrolls internally instead. --}}
                    <table class="table table-Disciplinary cdi-ao-datatable-host">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Offence</th>
                                <th>Action Valid Until</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>
@include('resorts.GrievanceAndDisciplinery.diciplinary._create_disciplinary_styles')
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    /* Readonly dropdown — value still submits with the form, but pointer +
       keyboard interaction is blocked and the field is dimmed. The lock is
       released as soon as the dependent (Offence) selection is cleared. */
    .dd.is-readonly {
        pointer-events: none;
        opacity: 0.7;
    }
    .dd.is-readonly .dd-trigger {
        background-color: #f5f5f5 !important;
        cursor: not-allowed !important;
    }
</style>
@endsection

@section('import-scripts')
<script>
 $(document).ready(function(){

    $("#disciplinaryForm").parsley();
        flatpickr('.Expiry_date', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
        // Reverse-direction shortcut: when the user picks an Offence first,
        // auto-select its parent Category and lock it. The data-cat attribute
        // is baked into each <option> from the controller payload.
        // Category, Severity and Action are all fully derived from the
        // Offence — only Offence is user-editable. Keep the dependents
        // locked at all times; we only update their values programmatically
        // when an Offence is picked.
        function lockSelect(id) {
            $('#' + id).addClass('is-readonly').next('.dd').addClass('is-readonly');
            // .trigger('change.select2') is namespaced — it only ever
            // reached select2's own internal listener (which redrew its
            // widget), never a plain $(document).on('change', ...) handler,
            // so it can't drive the new dropdown's auto-resync-on-change
            // hook either. lockSelect() runs right after every one of
            // this form's .val().trigger('change.select2') calls, so
            // syncing here covers all of them in one place.
            wisdomDD.sync('#' + id);
        }
        function lockDerivedFields() {
            lockSelect('Category_id');
            lockSelect('Severity_id');
            lockSelect('Action_id');
        }
        // Backwards-compat alias used elsewhere in this file.
        function lockCategoryWidget() { lockSelect('Category_id'); }
        lockDerivedFields();

        // Severity and Action stay locked at all times; only the value
        // changes when a new Offence is picked. We don't toggle the lock
        // anymore — kept the helper signature so existing call sites work.
        function lockField(selectId, hintId, locked) {
            // Always keep the field locked; ignore the `locked` arg.
            lockSelect(selectId);
        }
        function clearAndUnlockHistoryFields() {
            $('#Severity_id').val('').trigger('change.select2');
            $('#Action_id').val('').trigger('change.select2');
            wisdomDD.sync('#Severity_id');
            wisdomDD.sync('#Action_id');
            // Stay locked — user must pick a new Offence to populate.
        }
        $(document).on('change', '#Offence_id', function () {
            var $sel = $(this).find('option:selected');
            var catId       = $sel.data('cat');
            var sevDefault  = $sel.data('severity');
            var actDefault  = $sel.data('action');
            var offenceVal  = $(this).val();
            if (!offenceVal) {
                // Offence cleared — clear and unlock all dependents.
                $('#Category_id').val('').trigger('change.select2');
                lockCategoryWidget();
                clearAndUnlockHistoryFields();
                return;
            }
            if (catId && $('#Category_id').val() !== String(catId)) {
                $('#Category_id').val(String(catId)).trigger('change.select2');
            }
            lockCategoryWidget();

            // Apply admin-set defaults synchronously (no waiting for AJAX).
            if (sevDefault) {
                $('#Severity_id').val(String(sevDefault)).trigger('change.select2');
                lockField('Severity_id', 'severityAutoHint', true);
            }
            if (actDefault) {
                $('#Action_id').val(String(actDefault)).trigger('change.select2');
                lockField('Action_id', 'actionAutoHint', true);
            }

            // Fetch + apply prior defaults (severity / action) from history,
            // overriding only the dropdowns the admin hasn't set defaults on.
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.GetOffenceDefaults') }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", offence_id: offenceVal },
                success: function (resp) {
                    if (!resp || !resp.success) return;
                    var d = resp.data || {};
                    if (d.Severity_id) {
                        $('#Severity_id').val(d.Severity_id).trigger('change.select2');
                        lockField('Severity_id', 'severityAutoHint', true);
                    } else {
                        lockField('Severity_id', 'severityAutoHint', false);
                    }
                    if (d.Action_id) {
                        $('#Action_id').val(d.Action_id).trigger('change.select2');
                        lockField('Action_id', 'actionAutoHint', true);
                    } else {
                        lockField('Action_id', 'actionAutoHint', false);
                    }
                    if (d.description && !$('#incident-description').val()) {
                        $('#incident-description').val(d.description);
                    }
                }
            });
        });

        $(document).on('change', '#Category_id', function() {
            var Category_id = $(this).val();

            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.GetCategoryWiseOffence') }}", 
                type: 'POST',
                data: {"_token":"{{ csrf_token() }}","id":Category_id},
                success: function(response) 
                {
                    if (response.success) 
                    {
                        var option = '<option value="">Select Offence</option>';
                        $.each(response.data, function(i, v) {
                            option += `<option value="${v.newid}" data-cat="${v.cat}">${v.OffensesName}</option>`;
                        });
                        $('#Offence_id').html(option);
                        wisdomDD.rebuild('#Offence_id');

                    }
                    else 
                    {
                        toastr.error(response.message, "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) 
                {
                    var errors = response.responseJSON;

                    if (errors.error) { 
                        // If it's a duplicate entry error
                        toastr.error(errors.error, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // If it's a validation error
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });

                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }

            });
                
        
        });


        // Truncating file-preview row — icon + ellipsis-truncated name (full
        // name via title= on hover, see .cdi-filerow CSS). Filenames are
        // user-controlled, so text is always set via .textContent, never
        // interpolated into innerHTML.
        function cdiFileRow(name) {
            var li = document.createElement('li');
            li.className = 'cdi-filerow';
            li.title = name;
            li.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg><span class="fn"></span>';
            li.querySelector('.fn').textContent = name;
            return li;
        }

        document.getElementById("attachment").addEventListener("change", function () {
            const list = document.getElementById("attachment-preview");
            list.innerHTML = '';
            Array.from(this.files).forEach(function (file) {
                list.appendChild(cdiFileRow(file.name));
            });
        });

        document.getElementById("upload-signed-document").addEventListener("change", function () {
            const list = document.getElementById("signed-preview");
            list.innerHTML = '';
            const file = this.files[0];
            if (file) list.appendChild(cdiFileRow(file.name));
        });


        $("#disciplinaryForm").on("submit", function (event) {
            event.preventDefault();
            var formData = new FormData(this);
            

            $(".Submitme").prop("disabled", true);
            $(".Submitme").text("Submitting...");
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.StoreDisciplinary') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                         $(".Submitme").prop("disabled", false);
                        $(".Submitme").text("Submit");
                        window.location.href = "{{ route('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex') }}";
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });

                        $(".Submitme").prop("disabled", false);
                        $(".Submitme").text("Submit");
                    }
                },
                error: function (response) 
                {
                    console.log(response);
                    var errors = response.responseJSON;
                    if (errors.error) { 
                        // If it's a duplicate entry error
                        toastr.error(errors.error, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // If it's a validation error
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });

                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });

        $('#Employee_id').on('change', function() {
            var empName = $(this).find('option:selected').text().trim();
            $('#aoSub').text(empName ? empName + "'s current record — reference while filing" : 'Select an employee to see their active record');
            GetOpenOffences();
        });


});


// Action pill colour — derived client-side from the same ActionName string
// the table already returns (no new field, no backend change): a written
// warning reads amber, a termination/final action reads red, anything else
// (verbal warning, etc.) stays neutral grey — matching the design's 3-tier
// severity treatment.
function cdiActionClass(actionName) {
    var a = (actionName || '').toLowerCase();
    if (a.indexOf('final') !== -1 || a.indexOf('terminat') !== -1) return 'final';
    if (a.indexOf('written') !== -1) return 'written';
    return 'verbal';
}

function GetOpenOffences()
{
    if ($.fn.dataTable.isDataTable('.table-Disciplinary'))
    {
        $('.table-Disciplinary').DataTable().destroy();
    }
    // The DataTables engine (server-side ajax, the exact same endpoint/
    // columns/Employee-scoped request as before) stays bound to the real
    // <table> — it's just visually hidden (.cdi-ao-datatable-host). The
    // vertical card list is rendered from that same fetched data via
    // drawCallback, so nothing about the fetch contract changes.
    var TableAccomMainten = $('.table-Disciplinary').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": false,
        "bAutoWidth": false,
        // -1 = fetch every active offence in one request — a year's worth
        // of active offences for one employee realistically never gets
        // large enough to need paging; the list just scrolls internally
        // (.cdi-ao-list's own max-height) instead.
        "iDisplayLength": -1,
        processing: true,
        serverSide: true,
        dom: 'rt',
        ajax: {
            url: '{{ route("GrievanceAndDisciplinery.DisciplineryOpenOffence") }}',
            type: 'GET',
            data: function (d) {
                d.Employee_id =$("#Employee_id").val();
            }
        },
        columns: [
            { data: 'Category', name: 'Category', className: 'text-nowrap' },
            { data: 'Offense', name: 'Offense', className: 'text-nowrap' },
            { data: 'Date', name: 'Date', className: 'text-nowrap' },
            { data: 'Action', name: 'Action', className: 'text-nowrap' },
        ],
        drawCallback: function () {
            var api = this.api();
            var rows = api.rows({ page: 'current' }).data();
            var $list = $('#aoList').empty();
            $('#aoCount').text(api.page.info().recordsTotal);

            if (rows.length === 0) {
                $list.append($('<div class="cdi-ao-empty"></div>').text('No active offences on record.'));
                return;
            }
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                // Each card links straight to that case's existing
                // Disciplinary Investigation page — same route/id-encoding
                // the investigation list itself already uses (base64 of
                // the disciplinary_submits row id), just built client-side
                // since the id already comes back in this AJAX response.
                var href = "{{ route('GrievanceAndDisciplinery.Disciplinary.Investigation', ':id') }}".replace(':id', btoa(String(row.id)));
                var $item = $(
                    '<a class="cdi-ao-item" href="' + href + '">' +
                        '<div class="cdi-ao-top">' +
                            '<span class="cdi-ao-off"></span>' +
                            '<span class="cdi-ao-act"></span>' +
                        '</div>' +
                        '<div class="cdi-ao-meta">' +
                            '<span class="cdi-ao-cat"></span>' +
                            '<span class="cdi-ao-valid">Valid until <b></b></span>' +
                        '</div>' +
                    '</a>'
                );
                $item.find('.cdi-ao-off').text(row.Offense);
                $item.find('.cdi-ao-act').addClass(cdiActionClass(row.Action)).text(row.Action || '—');
                $item.find('.cdi-ao-cat').attr('title', row.Category).text(row.Category);
                $item.find('.cdi-ao-valid b').text(row.Date);
                $list.append($item);
            }
        }
    });

}

</script>
@endsection