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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="row g-4">
                    <div class="col-xxl-9 col-lg-8 ">
                        <form id="leave-apply" name="leave-apply" method="post" enctype="multipart/form-data">
                            <div class="card">
                                <div class="append-main">
                                    <div class="append-block mb-4">
                                        <div class="row align-items-end g-md-4 g-3 ">
                                            <div class="col-xl-6 col-sm-4">
                                                <label for="leaveCat1" class="form-label">LEAVE CATEGORY<span class="red-mark">*</span></label>
                                                <select class="form-control LeaveCate_id leave-category-select2" name="leave_category_id[0]" id="leaveCat1" aria-label="Default select example" data-parsley-required="true" data-parsley-errors-container="#leave-cat-error">
                                                    <option value="">Select Leave Category</option>
                                                    @if($leave_categories)
                                                        @foreach($leave_categories as $value)
                                                            <option value="{{$value->leave_cat_id}}"
                                                            data-combine-with-other="{{$value->combine_with_other}}" data-leave-category="{{$value->leave_category}}"
                                                            data-used-leaves = "{{$value->total_leave_days}}"
                                                             data-color="{{$value->color}}" data-alloweddays="{{$value->allocated_days}}" data-available-days="{{ $value->available_days ?? $value->allocated_days }}">{{$value->leave_type}}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <div id="leave-cat-error"></div>
                                            </div>
                                            <div class="col-xl-3 col-sm-4 col-6">
                                                <label for="from_date1" class="form-label">FROM DATE<span class="red-mark">*</span></label>
                                                <input type="text" class="form-control datepicker" id="from_date1" placeholder="From Date" name="from_date[]" data-parsley-required="true"  data-parsley-errors-container="#from-date-error1">
                                                <div id="from-date-error1"></div>
                                            </div>
                                            <div class="col-xl-3 col-sm-4 col-6">
                                                {{-- <a href="#" class="close-btn append-close d-none float-end mb-2"><i class="fa-solid fa-xmark"></i></a> --}}
                                                <label for="to_date1" class="form-label">TO DATE<span class="red-mark">*</span></label>
                                                <input type="text" class="form-control datepicker" id="to_date1"
                                                    placeholder="To Date" name="to_date[]" data-parsley-required="true" data-parsley-endgreaterthanstart="#from_date1" data-parsley-errors-container="#to-date-error11">
                                                <div id="to-date-error1"></div>
                                            </div>
                                            <input type="hidden" id="total_days" name="total_days"/>
                                        </div>
                                    </div>
                                    <a href="#" class="btn btn-themeSkyblue btn-sm mb-3 append-add" id="rowAdder">Add Another Leave</a>
                                    <div id="newinput"></div>
                                </div>
                                <div class="row align-items-end g-4 mb-4">
                                    <div class="col-md-12" id="field-reason">
                                        <label for="leaveReason" class="form-label">LEAVE REASON<span class="red-mark reason-required-mark">*</span></label>
                                        <textarea class="form-control" rows="3" name="reason" id="leaveReason" placeholder="Leave Reason" data-parsley-errors-container="#reason-error"></textarea>
                                        <div id="reason-error"></div>
                                    </div>
                                    <div class="col-md-6" id="field-task_delegation">
                                        <label for="taskDel" class="form-label">TASK DELEGATION<span class="red-mark task_delegation-required-mark d-none">*</span></label>
                                        <select class="form-select select2t-none" name="task_delegation" id="taskDel" data-parsley-errors-container="#task_delegation-error">
                                            <option value="">Select Person</option>
                                            @if($delegations)
                                                @foreach($delegations as $emp)
                                                    <option value="{{$emp->id}}">{{$emp->first_name}} {{$emp->last_name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="task_delegation-error"></div>
                                    </div>
                                    <div class="col-md-6" id="field-destination">
                                        <label for="destination" class="form-label">DESTINATION<span class="red-mark destination-required-mark d-none">*</span></label>
                                        {{-- Select2 AJAX-mode airport picker. The full IATA dataset
                                             (~9k rows) lives in the `airports` table; the dropdown
                                             queries /resort/airports/search?q=... as the user types
                                             so the page stays light. --}}
                                        <select class="form-select select2-airport-search" name="destination" id="destination" data-parsley-errors-container="#destination-error">
                                            <option value="">Select Destination Airport</option>
                                        </select>
                                        <div id="destination-error"></div>
                                    </div>
                                    <div class="col-12" id="field-transportation">
                                        <label class="form-label">TRANSPORTATION  </label>
                                        <div id="transportation-options">
                                            @if($transportations)
                                                @foreach($transportations as $key => $value)
                                                    <div class="form-check form-check-inline">
                                                        <input 
                                                            class="form-check-input transportation-checkbox" 
                                                            type="checkbox" 
                                                            name="transportation[]" 
                                                            value="{{ $key }}" 
                                                            id="transportation-{{ $key }}">
                                                        <label class="form-check-label" for="transportation-{{ $key }}">{{ $value }}</label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <div id="datepickers-container" class="mt-3"></div>
                                    </div>




                                    
                                    <div class="col-12" id="field-departure_pass">
                                        <label class="form-label">DEPARTURE PASS </label>
                                        <div class="form-check form-check-inline">
                                            <input 
                                                class="form-check-input departure-checkbox" 
                                                type="radio" 
                                                name="departure" 
                                                value="Yes" 
                                                id="departure">
                                            <label class="form-check-label" for="departure">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input 
                                                class="form-check-input departure-checkbox" 
                                                type="radio" 
                                                name="departure" 
                                                value="No" 
                                                id="departure" checked>
                                            <label class="form-check-label" for="departure">No</label>
                                        </div>
                                        
                                        <div id="departure-options" class="d-none">
                                            <div class="row gx-xl-5 g-md-4 g-3 mb-4">
                                                <div class="col-lg-6">
                                                    <div class="mb-md-4 mb-3">
                                                        <label for="depDate" class="form-label">DEPARTURE DATE</label>
                                                        <input type="text" class="form-control datepicker" id="depDate" placeholder="DEPARTURE DATE" name="dept_date">
                                                    </div>
                                                    {{-- Departure flight time hidden: employees don't know flight times at leave application --}}
                                                    {{-- <div class="mb-md-4 mb-3">
                                                        <label for="depTime" class="form-label">DEPARTURE TIME</label>
                                                        <input type="time" class="form-control" id="depTime" placeholder="DEPARTURE TIME" name="dept_time">
                                                    </div> --}}
                                                    <div>
                                                        <label for="" class="form-label">DEPARTURE TRANSPORTATION *</label>
                                                        <div>
                                                            @if($transportations)
                                                                @foreach($transportations as $key => $value)
                                                                    <div class="form-check form-check-inline">
                                                                        <input 
                                                                            class="form-check-input  dept-transportation-checkbox" 
                                                                            type="radio" 
                                                                            name="dept_transportation" 
                                                                            value="{{ $key }}" 
                                                                            id="dept-transportation-{{ $key }}"
                        
                                                                                data-parsley-errors-container="#dept-transportation-error-container">
                                                                        <label class="form-check-label" for="dept-transportation-{{ $key }}">{{ $value }}</label>
                                                                    </div>
                                                                @endforeach
                                                                <div id="dept-transportation-error-container" class="parsley-errors-list"></div>

                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-md-4 mb-3">
                                                        <label for="arrDate" class="form-label">ARRIVAL DATE</label>
                                                        <input type="text" class="form-control datepicker" id="arrDate" placeholder="ARRIVAL DATE" name="arr_date">
                                                    </div>
                                                    {{-- Arrival flight time hidden: employees don't know flight times at leave application --}}
                                                    {{-- <div class="mb-md-4 mb-3">
                                                        <label for="arrTime" class="form-label">ARRIVAL TIME</label>
                                                        <input type="time" class="form-control" id="arrTime" placeholder="ARRIVAL TIME" name="arr_time">
                                                    </div> --}}
                                                    <div>
                                                        <label for="" class="form-label">ARRIVAL TRANSPORTATION *</label>
                                                        <div>
                                                            <div>
                                                                <div class="transportation-options">
                                                                    @foreach($transportations as $key => $value)
                                                                        <div class="form-check form-check-inline">
                                                                            <input 
                                                                                class="form-check-input  arrival-transportation-checkbox"
                                                                                type="radio"
                                                                                name="arrival_transportation"
                                                                                value="{{ $key }}"
                                                                                id="arrival-transportation-{{ $key }}"
                        
                                                                                data-parsley-errors-container="#transportation-error-container">
                                                                            <label class="form-check-label" for="arrival-transportation-{{ $key }}">{{ $value }}</label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>    
                                                                <div id="transportation-error-container" class="parsley-errors-list"></div>

                                                            
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div>
                                                        <label for="reason" class="form-label">REASON</label>
                                                        <textarea class="form-control" id="boarding_pass_reason" name="boarding_pass_reason" placeholder="Reason" rows="4"  data-parsley-departure-or-arrival
                                                        data-parsley-errors-container="#error"
                                                        ></textarea>
                                                    </div>
                                                    <div id="error"></div>
                                                </div>
                                            </div>    
                                        </div>
                                    </div>
                                    <div class="col-md-12" id="field-attachment">
                                        <label for="uploadFile" class="form-label">UPLOAD DOCUMENTS<span class="red-mark attachment-required-mark d-none">*</span></label>
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn">
                                                <button type="button" class="btn btn-themeBlue btn-sm" onclick="document.getElementById('uploadFile').click();">Upload File</button>
                                                <input type="file" id="uploadFile" name="attachments" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png">
                                            </div>
                                            <div class="uploadFile-text">PNG, JPEG, PDF, Word — max 5 MB</div>
                                            <span id="uploadFile-name" class="ms-2 text-muted small"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-themeBlue btn-sm float-end">Submit</button>
                                </div>
                            </div>
                       </form>
                    </div>
                    <div class="col-xxl-3 col-lg-4 ">
                        <div class="card regInclude-card">
                            <div class="card-title">
                                <h3>Your Request Includes</h3>
                            </div>
                            <div class="regInclude-card" id="dynamic-summary">
                                <!-- Dynamic content will be appended here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .is-invalid {
        border-color: #dc3545!important;
    }

    .invalid-feedback {
        color: #dc3545!important;
        display: block!important;
        margin-top: 5px!important;
    }

    /* ── "Your Request Includes" card — sticky + internal scroll so the
       right column never overgrows the form on the left. The card body
       scrolls; the title stays pinned. ───────────────────────────────── */
    .card.regInclude-card {
        position: sticky;
        top: 90px;
        max-height: calc(100vh - 110px);
        display: flex;
        flex-direction: column;
    }
    .card.regInclude-card > .card-title { flex: 0 0 auto; }
    .card.regInclude-card > .card-title h3 { font-size: 14px; }
    .card.regInclude-card > #dynamic-summary {
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 0;
        padding-right: 6px;
    }
    /* Subtle scrollbar so it doesn't fight the card border. */
    .card.regInclude-card > #dynamic-summary::-webkit-scrollbar { width: 6px; }
    .card.regInclude-card > #dynamic-summary::-webkit-scrollbar-thumb {
        background: #d1d5db; border-radius: 3px;
    }

    /* Tighten the leave-category header block (Emergency Leave / dates /
       Available balance) to match the form's compact scale. The defaults
       in default.css are 14px h5/h6 + 12px p — a notch larger than the
       form labels next to them, which is what looked off. */
    .card.regInclude-card .regInclude-block h5 { font-size: 12px; font-weight: 600; }
    .card.regInclude-card .regInclude-block h6 { font-size: 11px; font-weight: 600; }
    .card.regInclude-card .regInclude-block span { font-size: 11px; }
    .card.regInclude-card .regInclude-block p { font-size: 11px; margin-bottom: 0; }

    /* "Total:" footer pill — was inheriting bold + larger default text. */
    .card.regInclude-card .bg-themeGrayLight { padding: 10px 14px; font-size: 12px; }
    .card.regInclude-card .bg-themeGrayLight p { font-size: 12px; }
    .card.regInclude-card .bg-themeGrayLight span { font-size: 13px; font-weight: 700; }

    /* ── Leave Breakdown Summary card ─────────────────────────────── */
    /* Font sizes deliberately match the rest of the form:
       - .regInclude-block p uses 12px (tile labels mirror it)
       - .regInclude-block h6 uses 14px (row labels stay close to it)
       Previous 28px tile values + 14px rows looked oversized vs the
       form's 12-14px scale; now everything sits in the same band. */
    .leave-breakdown-card {
        background: #fff;
        border-radius: 14px;
        padding: 14px 14px 4px;
        margin: 10px 0 12px;
        border: 1px solid #ececec;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .leave-breakdown-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.3px;
        color: #1f2937;
        margin: 0 0 12px;
    }
    .leave-breakdown-quadrants {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 12px;
    }
    .leave-breakdown-tile {
        background: #f3eddc;
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-height: 68px;
    }
    .leave-breakdown-tile .tile-label {
        font-size: 11px;
        color: #4b5563;
        line-height: 1.3;
    }
    .leave-breakdown-tile .tile-value {
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        color: #b45309; /* amber-700 */
    }
    .leave-breakdown-tile .tile-value.tile-amber  { color: #b45309; }
    .leave-breakdown-tile .tile-value.tile-green  { color: #166534; } /* green-800 */

    .leave-breakdown-rows {
        border-top: 1px solid #ececec;
        padding-top: 4px;
    }
    .leave-breakdown-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 7px 0;
        border-bottom: 1px solid #f1f1f1;
        font-size: 12px;
        color: #1f2937;
    }
    .leave-breakdown-row:last-child { border-bottom: none; }
    .leave-breakdown-row .row-label { line-height: 1.35; }

    .leave-breakdown-row .row-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
    .pill-purple     { background: #ede7ff; color: #5b21b6; }
    .pill-blue       { background: #e0f2fe; color: #075985; }
    .pill-amber      { background: #fef3c7; color: #92400e; }
    .pill-green      { background: #dcfce7; color: #166534; }
    .pill-green-soft { background: #ecfdf5; color: #065f46; }
    .pill-red        { background: #fee2e2; color: #991b1b; }
</style>
@endsection

@section('import-scripts')    
<script> var functioncallyes = 0;  var cat_ids =[];</script>
<script type="text/javascript">
    var leaveFormValidation = @json($leaveFormValidation ?? []);
    function applyLeaveCategoryValidation() {
        var firstSelect = document.getElementById('leaveCat1');
        if (!firstSelect || !leaveFormValidation || Object.keys(leaveFormValidation).length === 0) return;
        var selectedOption = firstSelect.options[firstSelect.selectedIndex];
        var leaveTypeText = selectedOption ? selectedOption.text.trim() : '';
        var key = leaveTypeText.toLowerCase();
        var rules = leaveFormValidation[key];
        if (!rules) {
            for (var k in leaveFormValidation) {
                if (key.indexOf(k) !== -1 || k.indexOf(key) !== -1) {
                    rules = leaveFormValidation[k];
                    break;
                }
            }
        }
        if (!rules) {
            rules = { reason: 'mandatory', task_delegation: 'optional', destination: 'optional', transportation: 'optional', departure_pass: 'optional', attachment: 'optional' };
        }
        var fields = ['reason', 'task_delegation', 'destination', 'transportation', 'departure_pass', 'attachment'];
        fields.forEach(function(field) {
            var rule = rules[field] || 'optional';
            var $el = $('#field-' + field);
            var $input = field === 'reason' ? $('#leaveReason') : field === 'task_delegation' ? $('#taskDel') : field === 'destination' ? $('#destination') : field === 'attachment' ? $('#uploadFile') : null;
            if (rule === 'hidden') {
                $el.addClass('d-none');
                if ($input && $input.length) {
                    $input.removeAttr('required').removeAttr('data-parsley-required');
                    $input.val('').trigger('change');
                }
                $el.find('.reason-required-mark, .task_delegation-required-mark, .attachment-required-mark').addClass('d-none');
            } else {
                $el.removeClass('d-none');
                var isMandatory = (rule === 'mandatory');
                if (field === 'reason') {
                    if (isMandatory) {
                        $('#leaveReason').attr('required', 'required').attr('data-parsley-required', 'true');
                        $el.find('.reason-required-mark').removeClass('d-none');
                    } else {
                        $('#leaveReason').removeAttr('required').removeAttr('data-parsley-required');
                        $el.find('.reason-required-mark').addClass('d-none');
                    }
                } else if (field === 'task_delegation') {
                    if (isMandatory) {
                        $('#taskDel').attr('required', 'required').attr('data-parsley-required', 'true');
                        $el.find('.task_delegation-required-mark').removeClass('d-none');
                    } else {
                        $('#taskDel').removeAttr('required').removeAttr('data-parsley-required');
                        $el.find('.task_delegation-required-mark').addClass('d-none');
                    }
                } else if (field === 'attachment') {
                    if (isMandatory) {
                        $('#uploadFile').attr('required', 'required').attr('data-parsley-required', 'true');
                        $el.find('.attachment-required-mark').removeClass('d-none');
                    } else {
                        $('#uploadFile').removeAttr('required').removeAttr('data-parsley-required');
                        $el.find('.attachment-required-mark').addClass('d-none');
                    }
                }
            }
        });
        if ($('#leave-apply').parsley()) {
            $('#leave-apply').parsley().reset();
        }
    }
    $(document).ready(function () {
        // Ensure Parsley is loaded
        if (typeof $.fn.parsley !== 'function') {
            console.error('Parsley.js is not loaded correctly');
            return;
        }

        // Initialize the entire form with Parsley
        var $form = $("#leave-apply");
        $form.parsley({
            excluded: 'input[type=button], input[type=submit], input[type=reset]',
            trigger: 'change',
            successClass: 'is-valid',
            errorClass: 'is-invalid'
        });

        $('.dept-transportation-checkbox').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<span></span>',
            trigger: 'change'
        });
        $('.arrival-transportation-checkbox').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<span></span>',
            trigger: 'change'
        });
    
       
        // Optional: Custom validation message display
        window.Parsley.on('field:error', function() {
            this.$element.closest('.form-group').addClass('has-error');
        });
        
        window.Parsley.on('field:success', function() {
            this.$element.closest('.form-group').removeClass('has-error');
        });
        // Initialize the datepickers
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            startDate: new Date(), // Disable past dates
        }).on('changeDate', function () {
            // Trigger Parsley validation when the date changes
            $(this).parsley().validate();
        });

        // Init Leave Category selects (not .select2t-none so layout doesn't double-init and break selection)
        $(".leave-category-select2, .LeaveCate_id").each(function() {
            var $sel = $(this);
            if ($sel.hasClass('select2-hidden-accessible')) { try { $sel.select2('destroy'); } catch (e) {} }
            $sel.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Select Leave Category'
            });
        });
        // Init other Select2 (task delegation etc.) – skip if layout already inited
        $(".select2t-none").each(function() {
            var $sel = $(this);
            if ($sel.hasClass('select2-hidden-accessible')) return;
            $sel.select2({ width: '100%', allowClear: true });
        });

        // File upload UX: surface the chosen filename (input is opacity:0 so
        // the browser's "No file chosen" hint is invisible) and reject files
        // > 5 MB before they hit the server.
        $(document).on('change', '#uploadFile', function () {
            var f = this.files && this.files[0];
            if (!f) { $('#uploadFile-name').text(''); return; }
            if (f.size > 5 * 1024 * 1024) {
                toastr.error('File is larger than 5 MB. Please choose a smaller file.', 'Error', { positionClass: 'toast-bottom-right' });
                this.value = '';
                $('#uploadFile-name').text('');
                return;
            }
            $('#uploadFile-name').text(f.name);
        });

        // Airport destination dropdown — Select2 AJAX mode. The full IATA
        // dataset is in the `airports` table; we hit /resort/airports/search
        // as the user types so the page doesn't ship 9k <option>s.
        $(".select2-airport-search").each(function () {
            var $sel = $(this);
            if ($sel.hasClass('select2-hidden-accessible')) {
                try { $sel.select2('destroy'); } catch (e) {}
            }
            $sel.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Search airport (city, IATA code, or country)',
                minimumInputLength: 1,
                ajax: {
                    url: '{{ route("resort.airports.search") }}',
                    dataType: 'json',
                    delay: 200,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data) { return data; },
                    cache: true
                }
            });
        });

        // Pre-fill the previously-saved destination if any (e.g. when the
        // page reloads after a server-side validation error and we still
        // have an "old('destination')" value).
        var preselectedDestination = @json(old('destination'));
        if (preselectedDestination) {
            var parts = String(preselectedDestination).split(' - ');
            var $opt = new Option(parts.length === 2 ? (parts[0] + ' — ' + parts[1]) : preselectedDestination, preselectedDestination, true, true);
            $('#destination').append($opt).trigger('change');
        }

        // Apply leave-category-based validation (Mandatory/Optional/Hidden) when first leave category changes
        $(document).on('change', '#leaveCat1', function () {
            applyLeaveCategoryValidation();
        });
        applyLeaveCategoryValidation();

        // Trigger Parsley when any Select2 (leave category or task delegation) changes
        $(document).on('change', '.select2t-none, .select2-airport-search, .LeaveCate_id', function () {
            var parsleyField = $(this).parsley();
            if (parsleyField && parsleyField.validate) {
                parsleyField.validate();
                var $sel = $(this).next('.select2-container').find('.select2-selection');
                if ($sel.length) {
                    $sel.toggleClass('is-invalid', !parsleyField.isValid());
                }
            }
        });

        // Parsley field validation handler (both select2t-none and leave category Select2)
        window.Parsley.on('field:validated', function (fieldInstance) {
            var $element = fieldInstance.$element;
            var $select2Container = $element.next('.select2-container').find('.select2-selection');
            if ($select2Container.length) {
                if (fieldInstance.isValid()) {
                    $select2Container.removeClass('is-invalid');
                } else {
                    $select2Container.addClass('is-invalid');
                }
            }
        });

        // Event listener for adding new leave
        $('#leave-apply').on('.datepicker  change', function () {
            updateRequestSummary();
        });

        // When a transportation checkbox is toggled
        $(document).on('change', '.transportation-checkbox', function () {
            const checkbox = $(this);
            const transportId = checkbox.val();
            const transportName = checkbox.next('label').text();
            const datepickerId = `datepicker-${transportId}`;
            const timepickerId = `timepicker-${transportId}`;
            
            if (checkbox.is(':checked')) {
                // Get the leave period (From Date & To Date)
                const fromDateStr = $('[name="from_date[]"]').val();
                const toDateStr = $('[name="to_date[]"]').val();

                if (!fromDateStr || !toDateStr) {
                    toastr.error("Please select leave dates first!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    checkbox.prop('checked', false);
                    return;
                }

                // Parse dates (format: dd/mm/yyyy)
                const parseDate = (dateStr) => {
                    const [day, month, year] = dateStr.split('/');
                    return new Date(year, month - 1, day);
                };

                const minDate = parseDate(fromDateStr);
                const maxDate = parseDate(toDateStr);

           
                // Append datepicker inputs dynamically
                $('#datepickers-container').append(`
                    <div id="main-${transportId}">
                        <div class="row mb-3" id="${datepickerId}">
                            <div class="col-md-6">
                                <label class="form-label">Departure Date for ${transportName}</label>
                                <input type="text" class="form-control transport-departure-date"
                                    name="departure_date[${transportId}]"
                                    placeholder="Select Departure Date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Arrival Date for ${transportName}</label>
                                <input type="text" class="form-control transport-arrival-date"
                                    name="arrival_date[${transportId}]"
                                    placeholder="Select Arrival Date">
                            </div>
                        </div>
                        <!--
                            Departure / Arrival TIME fields hidden — employees
                            don't know exact flight times when filing leave.
                            Kept the markup commented so it can be re-enabled
                            without rebuilding the template if HR changes its
                            mind. Backend already nulls dept_time / arr_time
                            when missing, so leaving them out is safe.
                        <div class="row mb-3" id="timepicker-${transportId}">
                            <div class="col-md-6">
                                <label class="form-label">Departure Time for \${transportName}</label>
                                <input type="time" class="form-control departure-time" name="departure_time[\${transportId}]" placeholder="Select Departure Time">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Arrival Time for \${transportName}</label>
                                <input type="time" class="form-control arrival-time" name="arrival_time[\${transportId}]" placeholder="Select Arrival Time">
                            </div>
                        </div>
                        -->
                    </div>
                `);

                    // No window constraint — employees can pick any date for
                    // their flight. Sequence (arrival ≥ departure) is still
                    // enforced below in the on-change handler.
                    $(`#${datepickerId} .transport-departure-date`).datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true,
                    });
                    $(`#${datepickerId} .transport-arrival-date`).datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true,
                    });
            } else {
                $(`#main-${transportId}`).remove(); // Remove if unchecked
            }
        });


        $(document).on('change', '[name="from_date[]"], [name="to_date[]"]', function() {
            const fromDateStr = $('[name="from_date[]"]').val();
            const toDateStr = $('[name="to_date[]"]').val();

            if (!fromDateStr || !toDateStr) return;

            // Parse dates
            const parseDate = (dateStr) => {
                const [day, month, year] = dateStr.split('/');
                return new Date(year, month - 1, day);
            };

            const minDate = parseDate(fromDateStr);
            const maxDate = parseDate(toDateStr);

            // Per-transport and departure-pass datepickers are
            // intentionally unconstrained — employees can pick any date.
        });
            

        $('#datepickers-container').on('change', '.transport-arrival-date, .transport-departure-date', function() {
            const $row = $(this).closest('.row');
            const arrivalDate = $row.find('.transport-arrival-date').val();
            const departureDate = $row.find('.transport-departure-date').val();

            if (arrivalDate && departureDate) {
                const arrival = new Date(arrivalDate.split('/').reverse().join('-'));
                const departure = new Date(departureDate.split('/').reverse().join('-'));

                if (departure > arrival) {
                    toastr.error("Arrival date cannot be before departure date!");
                    $(this).val('').focus();
                }
            }
        });

        // For boarding pass dates
        $('#departure-options').on('change', '#arrDate, #depDate', function() {
            const arrDate = $('#arrDate').val();
            const depDate = $('#depDate').val();

            if (arrDate && depDate) {
                const arrival = new Date(arrDate.split('/').reverse().join('-'));
                const departure = new Date(depDate.split('/').reverse().join('-'));

                if (departure > arrival) {
                    toastr.error("Arrival date cannot be before departure date!");
                    $(this).val('').focus();
                }
            }
        });

        toggleDepartureOptions();

         $('input[name="departure"]').on('change', function () {
            toggleDepartureOptions();
        });

    });

    // Combine-info validator. Runs whenever the user picks a leave
    // category, but skips while we're already showing an error (so the
    // .val(null).trigger('change') below doesn't recurse and re-fire the
    // toast for the remaining selections), and dedupes in-flight calls
    // so Select2's double-fire doesn't produce two toasts.
    window.__combineInfoSuppressed = false;
    window.__combineInfoInflight = null;
    $(document).on('change', '.LeaveCate_id', function () {
        if (window.__combineInfoSuppressed) return;
        var $select = $(this);
        var runAjax = function () {
            // Only consider DROPDOWNS WITH AN ACTUAL VALUE — clicking
            // "Add another" creates an empty dropdown; including its
            // blank value previously triggered a spurious "No relation"
            // toast.
            var selectedValues = $("select[name^='leave_category_id']").map(function () {
                var v = $(this).val();
                return (v === null || v === '' || (Array.isArray(v) && !v.length)) ? null : v;
            }).get().filter(function (v) { return v !== null; });

            if (selectedValues.length < 2) return;

            // Cancel any in-flight request — the latest selection is the
            // only one that matters.
            if (window.__combineInfoInflight && window.__combineInfoInflight.readyState !== 4) {
                try { window.__combineInfoInflight.abort(); } catch (e) {}
            }

            window.__combineInfoInflight = $.ajax({
                url: "{{ route('leaves.combineInfo.get') }}",
                method: 'GET',
                data: { category_id: selectedValues },
                dataType: 'json',
                context: $select,
                success: function (response) {
                    if (response.status === 'error') {
                        toastr.error(response.message || 'Invalid selection', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Clear the just-picked value WITHOUT re-firing
                        // the validator — otherwise the remaining picks
                        // get re-checked and surface a duplicate toast.
                        window.__combineInfoSuppressed = true;
                        try {
                            $(this).val(null).trigger('change.select2');
                        } finally {
                            // Release the suppression on the next tick so
                            // future user selections still validate.
                            setTimeout(function () { window.__combineInfoSuppressed = false; }, 0);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'abort') return; // ignore deliberate cancels
                    toastr.error(xhr.responseJSON?.message || error || "Request failed", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        };
        setTimeout(runAjax, 0);
    });

    function toggleDepartureOptions() {
    const selectedVal = $('input[name="departure"]:checked').val();
    const $options = $('#departure-options');

    if (selectedVal === 'Yes') {
        $options.removeClass('d-none');

        // Get leave period
        const fromDateStr = $('[name="from_date[]"]').val();
        const toDateStr = $('[name="to_date[]"]').val();

        if (!fromDateStr || !toDateStr) {
            toastr.error("Please select leave dates first!", "Error", {
                positionClass: 'toast-bottom-right'
            });
            $('input[name="departure"][value="No"]').prop('checked', true);
            $options.addClass('d-none');
            return;
        }

        // Parse dates
        const parseDate = (dateStr) => {
            const [day, month, year] = dateStr.split('/');
            return new Date(year, month - 1, day);
        };

        const minDate = parseDate(fromDateStr);
        const maxDate = parseDate(toDateStr);

        // No window constraint — employees can pick any flight date.
        $('#depDate, #arrDate').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
        });
    } else {
        $options.addClass('d-none');
    }
}

    function initSelect2AndValidation(scope) {
        if (!$.fn.select2) return;
        var $target = scope ? $(scope).find('.select2t-none') : $(".select2t-none");
        $target.each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                var $sel = $(this);
                var opts = { width: '100%', allowClear: true };
                if ($sel.hasClass('LeaveCate_id') || ($sel.attr('id') && String($sel.attr('id')).indexOf('leaveCat') === 0)) {
                    opts.placeholder = 'Select Leave Category';
                }
                $sel.select2(opts);
            }
        });
        if ($.fn.parsley) {
            $target.off('change.leaveSelect2 select2:select.leaveSelect2').on('change.leaveSelect2', function() {
                $(this).parsley().validate();
            }).on('select2:select.leaveSelect2', function() {
                $(this).trigger('change');
            });
        }
    }

    // Function to update the right panel
    function updateRequestSummary() {
        let leaveSummary = '';
        let totalLeaveDays = 0; // Initialize total days
        let isFormValid = true; // Flag to check if form is valid

        $('.append-block').each(function (index) {
            const leaveCategory = $(this).find('select[name^="leave_category_id"] option:selected');
            const leaveCategoryText = leaveCategory.text();
            const leaveCategoryColor = leaveCategory.data('color') || '#cccccc'; // Fallback to default color
            const fromDate = $(this).find('[name="from_date[]"]').val();
            const toDate = $(this).find('[name="to_date[]"]').val();

            // Use available_days (includes carry forward) so extended leave can be applied; fallback to allocated - used
            const availableDays = parseInt(leaveCategory.data('available-days'), 10);
            const allowedLeaves = parseInt(leaveCategory.data('alloweddays'), 10) || 0;
            const usedLeaves = parseInt(leaveCategory.data('used-leaves'), 10) || 0;
            const remainingLeaves = (!isNaN(availableDays) && availableDays >= 0) ? availableDays : (allowedLeaves - usedLeaves);

            if (leaveCategoryText && fromDate && toDate) {
                const breakdown = calculateTotalDays(fromDate, toDate);
                const { totalDays, formattedRange, calendarDays, fridaysExcluded, holidaysExcluded } = breakdown;

                totalLeaveDays += totalDays; // Add total days
                const leaveExceedsBalance = totalDays > remainingLeaves; // Check balance
                const balanceRemaining = remainingLeaves - totalDays;

                if (leaveExceedsBalance) {
                    isFormValid = false; // Mark form as invalid
                }

                const warningText = leaveExceedsBalance
                    ? `<span style="color: red;">(Exceeds balance!)</span>`
                    : '';

                leaveSummary += `
                    <div class="regInclude-block" style="border-left: 5px solid ${leaveCategoryColor}; background-color: ${leaveCategoryColor}14;">
                        <div class="d-flex">
                            <h5 style="color: ${leaveCategoryColor};">${leaveCategoryText}</h5>
                            <h5>(${usedLeaves} / ${allowedLeaves})</h5>
                        </div>
                        <div class="d-flex">
                            <h6>${formattedRange}</h6><span>${totalDays} Days</span>
                        </div>
                        <p>Available Leave Balance: ${remainingLeaves} ${warningText}</p>
                    </div>

                    <!-- Leave Breakdown Summary card -->
                    <div class="leave-breakdown-card">
                        <h6 class="leave-breakdown-title">LEAVE BREAKDOWN SUMMARY</h6>

                        <div class="leave-breakdown-quadrants">
                            <div class="leave-breakdown-tile tile-soft-grey">
                                <span class="tile-label">Calendar days selected</span>
                                <span class="tile-value">${calendarDays}</span>
                            </div>
                            <div class="leave-breakdown-tile tile-soft-grey">
                                <span class="tile-label">Fridays (excluded)</span>
                                <span class="tile-value tile-amber">${fridaysExcluded}</span>
                            </div>
                            <div class="leave-breakdown-tile tile-soft-grey">
                                <span class="tile-label">Public holidays (excl.)</span>
                                <span class="tile-value tile-amber">${holidaysExcluded}</span>
                            </div>
                            <div class="leave-breakdown-tile tile-soft-grey">
                                <span class="tile-label">Actual working days</span>
                                <span class="tile-value tile-green">${totalDays}</span>
                            </div>
                        </div>

                        <div class="leave-breakdown-rows">
                            <div class="leave-breakdown-row">
                                <span class="row-label"><strong>Calendar days (${formattedRange})</strong></span>
                                <span class="row-pill pill-purple">${calendarDays} days</span>
                            </div>
                            <div class="leave-breakdown-row">
                                <span class="row-label"><strong>Fridays (not counted)</strong></span>
                                <span class="row-pill pill-blue">${fridaysExcluded > 0 ? '– ' : ''}${fridaysExcluded} days</span>
                            </div>
                            <div class="leave-breakdown-row">
                                <span class="row-label"><strong>Public holidays (not counted)</strong></span>
                                <span class="row-pill pill-amber">${holidaysExcluded > 0 ? '– ' : ''}${holidaysExcluded} days</span>
                            </div>
                            <div class="leave-breakdown-row">
                                <span class="row-label"><strong>Working days consumed</strong></span>
                                <span class="row-pill pill-green">${totalDays} days</span>
                            </div>
                            <div class="leave-breakdown-row">
                                <span class="row-label"><strong>Leave entitlement (${leaveCategoryText.trim()})</strong></span>
                                <span class="row-pill pill-purple">${remainingLeaves} days</span>
                            </div>
                            <div class="leave-breakdown-row">
                                <span class="row-label"><strong>Balance remaining</strong></span>
                                <span class="row-pill ${balanceRemaining < 0 ? 'pill-red' : 'pill-green-soft'}">${balanceRemaining} days</span>
                            </div>
                        </div>
                    </div>
                    <hr class="mt-1 mb-3">
                `;
            }
        });

        leaveSummary += `
            <div class="bg-themeGrayLight">
                <p>Total:</p> <span>${totalLeaveDays}</span>
            </div>
        `;

        $('#dynamic-summary').html(leaveSummary || '<p>No leave requests yet.</p>');

        // Enable or disable the submit button based on form validity
        if (isFormValid) {
            $('button[type="submit"]').prop('disabled', false);
        } else {
            $('button[type="submit"]').prop('disabled', true);
        }
    }

    // Function to adjust opacity of a color
    function adjustOpacity(color, opacity) {
        const hex = color.replace('#', '');
        const bigint = parseInt(hex, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    }

    // Set of YYYY-MM-DD public holiday dates from the controller. Used by
    // calculateTotalDays() to subtract holidays from the leave-day count.
    window.__publicHolidaySet = new Set(@json($holidayDates ?? []));

    // Function to calculate total days, EXCLUDING Fridays (resort weekly off)
    // and public holidays — the user shouldn't have those counted against
    // their leave balance. Returns the breakdown so the summary card can
    // render the four-quadrant view (calendar / fridays / holidays / working).
    function calculateTotalDays(from, to) {
        const fromParts = from.split('/');
        const toParts = to.split('/');
        const fromDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0]);
        const toDate = new Date(toParts[2], toParts[1] - 1, toParts[0]);

        if (isNaN(fromDate) || isNaN(toDate)) {
            console.error('Invalid date format:', { from, to });
            return {
                totalDays: 0,
                formattedRange: '',
                calendarDays: 0,
                fridaysExcluded: 0,
                holidaysExcluded: 0,
                holidayDetails: [],
            };
        }

        let calendarDays = 0;
        let fridaysExcluded = 0;
        let holidaysExcluded = 0;
        const holidayDetails = [];
        const cursor = new Date(fromDate);
        while (cursor <= toDate) {
            calendarDays++;
            const isFriday = cursor.getDay() === 5;
            const yyyy = cursor.getFullYear();
            const mm = String(cursor.getMonth() + 1).padStart(2, '0');
            const dd = String(cursor.getDate()).padStart(2, '0');
            const key = `${yyyy}-${mm}-${dd}`;
            const isHoliday = window.__publicHolidaySet.has(key);
            // Friday takes precedence — a public holiday that lands on a
            // Friday already wasn't being counted, so no double-deduction.
            if (isFriday) {
                fridaysExcluded++;
            } else if (isHoliday) {
                holidaysExcluded++;
                holidayDetails.push(key);
            }
            cursor.setDate(cursor.getDate() + 1);
        }

        const totalDays = calendarDays - fridaysExcluded - holidaysExcluded;
        const options = { day: '2-digit', month: 'short' };
        const fromFormatted = fromDate.toLocaleDateString('en-GB', options);
        const toFormatted = toDate.toLocaleDateString('en-GB', options);

        return {
            totalDays: totalDays,
            formattedRange: `${fromFormatted} - ${toFormatted}`,
            calendarDays: calendarDays,
            fridaysExcluded: fridaysExcluded,
            holidaysExcluded: holidaysExcluded,
            holidayDetails: holidayDetails,
        };
    }

    function initDatePicker() {
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true
            }).on('changeDate', function () {
                $(this).parsley().validate(); // Trigger validation on date change
            });
        }
    }

    document.getElementById('rowAdder').addEventListener('click', function () {
        // Get the container for adding new inputs
        const container = document.getElementById('newinput');

        // Create a unique identifier for new entries (using Date.now() and a random number to reduce the collision risk)
        const uniqueId = Date.now() + '-' + Math.floor(Math.random() * 1000);

        // HTML template for a new work experience section
        const newRow = `
            <div class="append-block mb-4">
                <div class="row align-items-end g-md-4 g-3" id="leave-row-${uniqueId}">
                    <div class="col-xl-6 col-sm-4">
                        <label for="leaveCat-${uniqueId}" class="form-label">LEAVE CATEGORY*</label>
                        <select class="form-select select2t-none LeaveCate_id" name="leave_category_id[]" id="leaveCat-${uniqueId}" data-parsley-required="true" data-parsley-errors-container="#leave-cat-error-${uniqueId}">
                            <option value="">Select Leave Category</option>
                            @if($leave_categories)
                                @foreach($leave_categories as $value)
                                    <option value="{{$value->leave_cat_id}}" data-alloweddays="{{$value->allocated_days}}" data-available-days="{{ $value->available_days ?? $value->allocated_days }}" data-used-leaves="{{$value->total_leave_days}}" data-color="{{$value->color}}">{{$value->leave_type}}</option>
                                @endforeach
                            @endif
                        </select>
                        <div id="leave-cat-error-${uniqueId}"></div>
                    </div>
                    <div class="col-xl-3 col-sm-4 col-6">
                        <label for="from-date-${uniqueId}" class="form-label">FROM DATE*</label>
                        <input type="text" class="form-control datepicker" id="from-date-${uniqueId}" 
                            placeholder="From Date" name="from_date[]" data-parsley-required="true"   data-parsley-errors-container="#from-date-error">
                        <div id="from-date-error"></div>
                    </div>
                    <div class="col-xl-3 col-sm-4 col-6">
                        <a href="#" class="close-btn append-close  float-end mb-2" data-row-id="${uniqueId}"><i class="fa-solid fa-xmark"></i></a>
                        <input type="text" class="form-control datepicker" id="to-date-${uniqueId}" 
                            placeholder="To Date" name="to_date[]" data-parsley-required="true" data-parsley-endgreaterthanstart="#from_date-${uniqueId}"   data-parsley-errors-container="#to-date-error">
                        <div id="to-date-error"></div>
                    </div>
                </div>
            </div>
        `;

        // Append the new row
        container.insertAdjacentHTML('beforeend', newRow);

        // Only init Select2 and datepicker for the new row (avoid re-initing existing selects)
        initSelect2AndValidation('#leave-row-' + uniqueId);
        initDatePicker();

        $('#leave-apply').parsley().destroy(); 
        $('#leave-apply').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<div></div>',
            trigger: 'change'
        });

        $('.alpha-only').on('input', function () {
            this.value = this.value.replace(/[^a-zA-Z ]/g, ''); // Allow only alphabetic characters and spaces
        });

        // Remove functionality
        document.querySelectorAll('.append-close').forEach(button => {
            button.addEventListener('click', function () {
                const rowId = this.getAttribute('data-row-id');
                document.getElementById(`leave-row-${rowId}`).remove();

                // Reinitialize Parsley validation for remaining fields after removal
                setTimeout(() => {
                    $('#leave-apply').parsley(); // Reinitialize Parsley on the entire form
                }, 100);
            });
        });
        
    });
     
    document.addEventListener('DOMContentLoaded', function() {
        function initSelect2AndValidationLocal(scope) {
            if (!$.fn.select2) return;
            var $target = scope ? $(scope).find('.select2t-none') : $(".select2t-none");
            $target.each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    var $sel = $(this);
                    var opts = { width: '100%', allowClear: true };
                    if ($sel.hasClass('LeaveCate_id') || ($sel.attr('id') && String($sel.attr('id')).indexOf('leaveCat') === 0)) {
                        opts.placeholder = 'Select Leave Category';
                    }
                    $sel.select2(opts);
                }
            });
            if ($.fn.parsley) {
                $target.off('change.leaveSelect2 select2:select.leaveSelect2').on('change.leaveSelect2', function() {
                    $(this).parsley().validate();
                }).on('select2:select.leaveSelect2', function() {
                    $(this).trigger('change');
                });
            }
        }

        function initDatePicker() {
            if ($.fn.datepicker) {
                $('.datepicker').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true
                }).on('changeDate', function () {
                    $(this).parsley().validate(); // Trigger validation on date change
                });
            }
        }

        // Initialize Parsley Validation
        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#leave-apply').parsley({
                    errorClass: 'is-invalid',
                    successClass: 'is-valid',
                    errorsWrapper: '<div class="invalid-feedback"></div>',
                    errorTemplate: '<div></div>',
                    trigger: 'change'
                });

                window.Parsley.addValidator('validateScript', {
                    validateString: function(value) {
                        // Pattern to match any <script> tags, even with attributes or content
                        const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                        return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
                    },
                    messages: {
                        en: 'Script tags are not allowed.'
                    }
                });

                window.Parsley.addValidator('endgreaterthanstart', {
                    validateString: function (endDateValue, startDateSelector) {
                        const startDateStr = $(startDateSelector).val();
                        const endDate = moment(endDateValue, 'DD/MM/YYYY', true);  // Parse end date
                        const startDate = moment(startDateStr, 'DD/MM/YYYY', true);  // Parse start date

                        // Check if both dates are valid
                        if (!startDate.isValid() || !endDate.isValid()) {
                            return true; // Skip validation if any date is invalid or missing
                        }

                        // Check that the end date is strictly after the start date
                        return endDate.isSame(startDate, 'day') || endDate.isAfter(startDate, 'day');
                    },
                    messages: {
                        en: 'To Date must be greater than From Date.'
                    }
                });

            }
        }

        // Alpha-only Input Handling
        function initAlphaOnlyInputs() {
            $('.alpha-only').on('keyup blur', function() {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
            });
        }
        
        // Form Submission Handling
        function initFormSubmission() {
            $('#leave-apply').on('submit', function(e) {
                // Prevent default submission
                e.preventDefault();

                // Validate entire form
                const form = $(this);
                if (form.parsley().validate()) {
                    // All validations passed
                    var formData = new FormData(this);

                    // Disable submit button to prevent multiple submissions
                    $('#submit')
                        .prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

                    // Ajax submission
                    $.ajax({
                        url: "{{ route('leave-applications.store') }}", // Update with your route
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log(response.status);
                            if(response.status == "success"){
                                // Handle successful submission
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                window.setTimeout(function() {
                                    window.location.href = response.redirect_url;
                                }, 2000);
                            }
                            else{
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                               
                        },
                        error: function(xhr) {
                            // Handle submission errors
                            var errorMessage = 'An error occurred while submitting your application.';
                            
                            // Check for specific error responses
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Construct error message from Laravel validation errors
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            // Show error alert
                            toastr.error(errorMessage, "Error", {
                                positionClass: 'toast-bottom-right'
                            });

                            // Re-enable submit button
                            $('#submit')
                                .prop('disabled', false)
                                .html('Submit Application');
                        },
                        complete: function() {
                            // Optional: Any cleanup or final actions
                            // Re-enable submit button if it's still disabled
                            $('#submit')
                                .prop('disabled', false)
                                .html('Submit Application');
                        }
                    });
                }
                else
                    return false; // Stop if validation fails
            });
        }

        // Initialize All Validations and Plugins
        function initializeFormValidation() {
            initSelect2AndValidation();
            initParsleyValidation();
            initDatePicker();
            initAlphaOnlyInputs();
            initFormSubmission();
        }

        // Call initialization when document is ready
        $(document).ready(initializeFormValidation);
        
    });
</script>
@endsection