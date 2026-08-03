@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
@php
    // Pre-fill state when continuing a draft.
    $oi   = $offlineInterview ?? null;
    $appl = $oi ? $oi->applicant : null;
    $oiId = $oi ? $oi->id : '';
    $step = $oi ? max(1, (int) $oi->current_step) : 1;
    $rec  = $oi && is_array($oi->recruitment_methods) ? $oi->recruitment_methods : [];

    // "Hiring for" context — shown once Step 1 (Hiring Requisition) is saved.
    $positionLabel = null;
    if ($oi) {
        $positionLabel = $oi->position_title
            ?: (optional($oi->position)->position_title ?? null);
    }
    $departmentLabel = $oi ? optional($oi->department)->name : null;
@endphp

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding">
            <div class="row justify-content-between g-md-2 g-1">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>{{ $page_title }}</h1>
                        @if($positionLabel)
                            <div class="mt-1 small text-muted">
                                <i class="fa-solid fa-briefcase me-1"></i>
                                Offline hiring for: <strong>{{ $positionLabel }}</strong>
                                @if($departmentLabel)
                                    <span class="text-muted"> &middot; {{ $departmentLabel }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('offline-interview.index') }}" class="btn ta-btn-ghost btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to list
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <form id="oiForm" data-parsley-validate>
                @csrf
                <input type="hidden" id="offline_interview_id" name="offline_interview_id" value="{{ $oiId }}">

                {{-- 5-step progress bar (Hiring Requisition / Applicant Info / Documents / Rounds / Selection) --}}
                <div class="progressbar-wrapper">
                    <ul id="progressbar" class="progressbar-tab d-flex justify-content-between align-items-center">
                        <li class="step-tab" data-tab-step="1"><span>Hiring Requisition Form</span></li>
                        <li class="step-tab" data-tab-step="2"><span>Applicant Information</span></li>
                        <li class="step-tab" data-tab-step="3"><span>Upload Candidate Documents</span></li>
                        <li class="step-tab" data-tab-step="4"><span>Interview Rounds</span></li>
                        <li class="step-tab" data-tab-step="5"><span>Selection &amp; Offer Process</span></li>
                    </ul>
                </div>

                {{-- ───────────────────── Step 1 ─ Pick a Vacancy ──────────────────────────── --}}
                {{-- HR picks an existing posted vacancy. The server hydrates department /
                     position / salary / etc. on the offline_interview shell so the rest
                     of the wizard already knows the requisition without re-typing. --}}
                <fieldset data-step="1">
                    <div class="card-title px-3 pt-3">
                        <h3>Choose a Vacancy</h3>
                        <p class="small text-muted mb-0">Pick one of the resort's currently posted positions to hire against. Step 2 onwards uses that vacancy's department, position and budget.</p>
                    </div>
                    <div class="px-3 pb-3">
                        <input type="hidden" id="selected_vacancy_id" name="vacancy_id"
                            value="{{ optional($oi)->position_id }}">
                        <div class="table-responsive">
                            <table class="table table-LearningProgram w-100 mb-0" id="vacancyPickerTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>No. of Positions</th>
                                        <th>Applicant</th>
                                        <th>Open Slots</th>
                                        <th>Application Date</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vacancies as $v)
                                        <tr class="vacancy-row" data-vacancy-id="{{ $v->vacancy_id }}" style="cursor:pointer;">
                                            <td><input type="radio" name="vacancy_pick" value="{{ $v->vacancy_id }}"></td>
                                            <td>{{ $v->position_title }}</td>
                                            <td>{{ $v->department_name }} <span class="badge badge-themeGrayLight ms-1">{{ $v->department_code }}</span></td>
                                            <td>{{ $v->no_of_positions }}</td>
                                            <td>{{ $v->application_count }}</td>
                                            {{-- Filled / Open slot indicator. Mirrors the employee-create
                                                 vacancy picker so HR sees consistent numbers across both
                                                 pages. Fully-filled vacancies are filtered out by the
                                                 controller before they ever reach this loop. --}}
                                            <td>
                                                <span class="badge badge-themeSuccess">{{ $v->remaining_slots }} of {{ $v->no_of_positions }} left</span>
                                                @if(($v->filled_count ?? 0) > 0)
                                                    <small class="text-muted d-block">({{ $v->filled_count }} already hired)</small>
                                                @endif
                                            </td>
                                            <td>{{ $v->application_date_label }}</td>
                                            <td>{{ $v->expiry_date_label }}</td>
                                        </tr>
                                    @empty
                                        {{-- Full explanation matches the employee-create page's
                                             validation copy so HR sees consistent wording across both
                                             flows. Shown when EITHER no vacancies exist OR every
                                             posted vacancy is already fully filled (the controller
                                             auto-hides those). --}}
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <div class="alert alert-warning mb-0">
                                                    <strong>No open vacancies available.</strong>
                                                    New offline interviews must be tied to an approved, unfilled vacancy.
                                                    Create one in <em>Talent Acquisition &rarr; Vacancies</em> and complete the
                                                    approval flow before returning here. Fully-filled vacancies are hidden automatically.
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Selected-vacancy preview card. Hidden until a row is picked. --}}
                        <div id="vacancyPreviewCard" class="cardBorder-block mt-3" style="display:none;">
                            <div class="card-title"><h3>Selected Vacancy</h3></div>
                            <div id="vacancyPreviewBody"></div>
                        </div>
                    </div>
                    <div class="card-footer text-end px-3 pb-3">
                        <button type="button" class="btn ta-btn-primary btn-sm step-next" data-from-step="1" id="vacancyNextBtn" disabled>Continue to Applicant Information</button>
                    </div>
                </fieldset>

                {{-- ───────────────────── Step 2 ─ Applicant Information ───────────────────── --}}
                <fieldset data-step="2" style="display:none;">
                    <div class="card-title px-3 pt-3"><h3>Applicant Information</h3></div>
                    <div class="px-3 pb-3">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">UPLOAD CV (PDF)</label>
                                <input type="file" name="curriculum_file" class="form-control" accept="application/pdf">
                                @if($appl && $appl->curriculum_vitae)
                                    <small class="text-muted d-block mt-1">Current: {{ basename($appl->curriculum_vitae) }}</small>
                                @endif
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">UPLOAD PASSPORT (PDF)</label>
                                <input type="file" name="passport_file" class="form-control" accept="application/pdf">
                                @if($appl && $appl->passport_file)
                                    <small class="text-muted d-block mt-1">Current: {{ basename($appl->passport_file) }}</small>
                                @endif
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">PASSPORT-SIZE PHOTO</label>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">FULL-LENGTH PHOTO</label>
                                <input type="file" name="full_length_photo" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="row g-md-4 g-3 mt-1">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">FIRST NAME <span class="red-mark">*</span></label>
                                <input type="text" name="first_name" class="form-control" required value="{{ optional($appl)->first_name }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">LAST NAME <span class="red-mark">*</span></label>
                                <input type="text" name="last_name" class="form-control" required value="{{ optional($appl)->last_name }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">GENDER <span class="red-mark">*</span></label>
                                <select name="gender" class="form-select select2t-none" required>
                                    <option value="">Select Gender</option>
                                    @foreach(['male' => 'Male','female' => 'Female','other' => 'Other'] as $v => $l)
                                        <option value="{{ $v }}" {{ optional($appl)->gender === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">DOB</label>
                                <input type="date" name="dob" class="form-control" value="{{ optional($appl)->dob }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">MOBILE NUMBER</label>
                                <input type="text" name="mobile_number" class="form-control" value="{{ optional($appl)->mobile_number }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">EMAIL ADDRESS</label>
                                <input type="email" name="email" class="form-control" value="{{ optional($appl)->email }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">MARITAL STATUS</label>
                                <select name="marital_status" class="form-select select2t-none">
                                    <option value="">Select Marital Status</option>
                                    @foreach(['married','unmarried'] as $v)
                                        <option value="{{ $v }}" {{ optional($appl)->marital_status === $v ? 'selected' : '' }}>{{ ucfirst($v) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">NUMBER OF CHILDREN</label>
                                <input type="number" min="0" name="number_of_children" class="form-control" value="{{ optional($appl)->number_of_children }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">ADDRESS LINE 1</label>
                                <input type="text" name="address_line_one" class="form-control" value="{{ optional($appl)->address_line_one }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">ADDRESS LINE 2</label>
                                <input type="text" name="address_line_two" class="form-control" value="{{ optional($appl)->address_line_two }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">CITY</label>
                                <input type="text" name="city" class="form-control" value="{{ optional($appl)->city }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">STATE</label>
                                <input type="text" name="state" class="form-control" value="{{ optional($appl)->state }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">COUNTRY</label>
                                <select name="country" class="form-select select2t-none">
                                    <option value="">Select Country</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c->id }}" {{ optional($appl)->country == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">PIN CODE</label>
                                <input type="text" name="pin_code" class="form-control" value="{{ optional($appl)->pin_code }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">PASSPORT NO</label>
                                <input type="text" name="passport_no" class="form-control" value="{{ optional($appl)->passport_no }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">PASSPORT EXPIRY DATE</label>
                                <input type="date" name="passport_expiry_date" class="form-control" value="{{ optional($appl)->passport_expiry_date }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end px-3 pb-3">
                        <button type="button" class="btn ta-btn-secondary btn-sm me-2 step-prev" data-from-step="2">Back</button>
                        <button type="button" class="btn ta-btn-neutral btn-sm me-2 step-save-draft" data-from-step="2">Save As Draft</button>
                        <button type="button" class="btn ta-btn-primary btn-sm step-next" data-from-step="2">Next</button>
                    </div>
                </fieldset>

                {{-- ───────────────────── Step 3 ─ Upload Candidate Documents ───────────────────── --}}
                <fieldset data-step="3" style="display:none;">
                    <div class="card-title px-3 pt-3"><h3>Upload Candidate Documents</h3></div>
                    <div class="px-3 pb-3">
                        <label class="form-label">OTHER RELEVANT DOCUMENTS (PNG, JPEG, PDF, Excel — multiple files allowed)</label>
                        <input type="file" name="documents[]" class="form-control" multiple accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls">

                        @if($oi && $oi->documents->where('category','documents')->count())
                            <div class="mt-3">
                                <label class="form-label small text-muted">Already uploaded:</label>
                                <ul class="mb-0">
                                    @foreach($oi->documents->where('category','documents') as $doc)
                                        <li><a href="{{ \Storage::url($doc->file_path) }}" target="_blank">{{ $doc->original_name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-end px-3 pb-3">
                        <button type="button" class="btn ta-btn-secondary btn-sm me-2 step-prev" data-from-step="3">Back</button>
                        <button type="button" class="btn ta-btn-neutral btn-sm me-2 step-save-draft" data-from-step="3">Save As Draft</button>
                        <button type="button" class="btn ta-btn-primary btn-sm step-next" data-from-step="3">Next</button>
                    </div>
                </fieldset>

                {{-- ───────────────────── Step 4 ─ Interview Rounds ───────────────────── --}}
                <fieldset data-step="4" style="display:none;">
                    <div class="card-title px-3 pt-3"><h3>Interview Rounds</h3></div>
                    <div class="px-3 pb-3">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="shortlisted_by_ai" value="1" id="ai_shortlisted" {{ optional($oi)->shortlisted_by_ai ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ai_shortlisted">Shortlisted by Wisdom AI</label>
                                        </div>
                                    </li>
                                    <li class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="hr_shortlisted" value="1" id="hr_shortlisted" {{ optional($oi)->hr_shortlisted ? 'checked' : '' }}>
                                            <label class="form-check-label" for="hr_shortlisted">HR Shortlisted</label>
                                        </div>
                                    </li>
                                    @foreach(['hr_round_status' => 'HR Round','hod_round_status' => 'HOD Round','gm_round_status' => 'GM Round'] as $field => $lbl)
                                        <li class="mb-3">
                                            <label class="form-label">{{ $lbl }}</label>
                                            <select name="{{ $field }}" class="form-select select2t-none">
                                                @foreach(['Pending','Passed','Failed','Skipped'] as $opt)
                                                    <option value="{{ $opt }}" {{ optional($oi)->{$field} === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">COMMENTS ABOUT PERFORMANCE</label>
                                <textarea name="round_comments" rows="9" class="form-control" placeholder="Type here">{{ optional($oi)->round_comments }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SCANNED DOCUMENTS (Round)</label>
                                <select name="round_category" class="form-select select2t-none mb-2">
                                    <option value="hr_round">HR Round</option>
                                    <option value="hod_round">HOD Round</option>
                                    <option value="gm_round">GM Round</option>
                                </select>
                                <input type="file" name="round_documents[]" class="form-control" multiple accept=".pdf,.png,.jpg,.jpeg">

                                @if($oi)
                                    @php $roundDocs = $oi->documents->whereIn('category',['hr_round','hod_round','gm_round']); @endphp
                                    @if($roundDocs->count())
                                        <ul class="mt-2 mb-0 small">
                                            @foreach($roundDocs as $doc)
                                                <li><span class="badge badge-themeGrayLight">{{ ucfirst(str_replace('_',' ',$doc->category)) }}</span>
                                                    <a href="{{ \Storage::url($doc->file_path) }}" target="_blank">{{ $doc->original_name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end px-3 pb-3">
                        <button type="button" class="btn ta-btn-secondary btn-sm me-2 step-prev" data-from-step="4">Back</button>
                        <button type="button" class="btn ta-btn-neutral btn-sm me-2 step-save-draft" data-from-step="4">Save As Draft</button>
                        <button type="button" class="btn ta-btn-primary btn-sm step-next" data-from-step="4">Next</button>
                    </div>
                </fieldset>

                {{-- ───────────────────── Step 5 ─ Selection & Offer ───────────────────── --}}
                <fieldset data-step="5" style="display:none;">
                    <div class="card-title px-3 pt-3"><h3>Selection &amp; Offer Process</h3></div>
                    <div class="px-3 pb-3">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-6">
                                <label class="form-label">IS CANDIDATE SELECTED?</label>
                                <select name="is_selected" id="is_selected" class="form-select select2t-none" required>
                                    <option value="">Select</option>
                                    <option value="Yes" {{ optional($oi)->is_selected === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No"  {{ optional($oi)->is_selected === 'No'  ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="offerLetterBlock">
                                <label class="form-label">Send Offer Letter and Contract (PNG, JPEG, PDF — multiple files allowed)</label>
                                <input type="file" name="offer_letter[]" class="form-control" accept=".pdf,.png,.jpg,.jpeg" multiple>
                                <small class="text-muted d-block mt-1">All selected files will be attached to a single email sent to the candidate.</small>
                                @if($oi)
                                    @php $offerDocs = $oi->documents->where('category', 'offer_letter'); @endphp
                                    @if($offerDocs->count())
                                        <ul class="mt-2 mb-0 small">
                                            @foreach($offerDocs as $doc)
                                                <li><a href="{{ \Storage::url($doc->file_path) }}" target="_blank">{{ $doc->original_name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0" id="selectedHelp" style="display:none;">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            On submit with <strong>Yes</strong>, an employee record will be created automatically
                            as <strong>Onboarding</strong>. HR can later activate them from People &rarr; Employee Details.
                        </div>
                    </div>
                    <div class="card-footer text-end px-3 pb-3">
                        <button type="button" class="btn ta-btn-secondary btn-sm me-2 step-prev" data-from-step="5">Back</button>
                        <button type="button" class="btn ta-btn-celebrate btn-sm" id="oiSubmit">Submit</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
@section('import-css')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
@endsection

@endsection

@section('import-scripts')
<script>
// ─── AI CV auto-fill (offline interview) ─────────────────────────────
//
// Mirrors the applicant-form behavior: when HR uploads the CV here,
// parse it via the AI proxy and pre-fill the visible applicant fields.
// Same Laravel endpoint as the public applicant form — proxy returns
// fields keyed by name= so this JS is a thin wrapper.
function aiAutofillFromCv(file) {
    if (!file) return;
    var fd = new FormData();
    fd.append('cv', file);
    fd.append('_token', $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}');

    var $banner = $('#ai-cv-banner');
    if ($banner.length === 0) {
        $banner = $('<div id="ai-cv-banner" class="alert alert-info py-1 px-2 my-2" style="font-size:13px;">&nbsp;</div>');
        $('input[name="curriculum_file"]').closest('.col-md-3').after($banner);
    }
    $banner.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Reading the CV…').show();

    $.ajax({
        url: @json(route('resort.applicant.cvExtract')),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function (resp) {
            if (!resp || !resp.success || !resp.fields) {
                $banner.removeClass('alert-info').addClass('alert-warning')
                    .text('Could not read the CV — fill the fields manually.');
                return;
            }
            var filled = 0;
            Object.keys(resp.fields).forEach(function (k) {
                var v = resp.fields[k];
                if (v === null || v === '') return;
                var $input = $('[name="' + k + '"]').first();
                if ($input.length && !$input.val()) {
                    $input.val(v).trigger('change');
                    filled++;
                }
            });
            $banner.removeClass('alert-info').addClass(filled > 0 ? 'alert-success' : 'alert-warning')
                .text(filled > 0
                    ? 'Pre-filled ' + filled + ' field' + (filled === 1 ? '' : 's') + ' from the CV.'
                    : 'Read the CV but found nothing new to pre-fill.');
        },
        error: function () {
            $banner.removeClass('alert-info').addClass('alert-warning')
                .text('AI service unavailable — fill the fields manually.');
        }
    });
}
$(document).on('change', 'input[name="curriculum_file"]', function () {
    if (this.files && this.files[0]) aiAutofillFromCv(this.files[0]);
});

$(document).ready(function () {
    $('.select2t-none').select2();

    // ── Step navigation ───────────────────────────────────────────────
    var currentStep = {{ (int) $step }};

    function showStep(n) {
        currentStep = n;
        $('fieldset[data-step]').hide();
        $('fieldset[data-step="' + n + '"]').show();
        $('#progressbar li.step-tab').removeClass('active current');
        $('#progressbar li.step-tab').each(function () {
            var s = parseInt($(this).data('tab-step'), 10);
            if (s < n) $(this).addClass('active');
            if (s === n) $(this).addClass('active current');
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    showStep(currentStep);

    // Toggle conditional fields.
    function toggleCasualBlock() {
        var t = $('input.emp-type:checked').val();
        var show = (t === 'Casual/Agency' || t === 'Temporary / Project');
        $('#casualAgencyBlock').toggle(show);
    }
    $(document).on('change', 'input.emp-type', toggleCasualBlock);
    toggleCasualBlock();

    function toggleSelectedHelp() {
        $('#selectedHelp').toggle($('#is_selected').val() === 'Yes');
    }
    $(document).on('change', '#is_selected', toggleSelectedHelp);
    toggleSelectedHelp();

    // ── Step 1 vacancy picker ─────────────────────────────────────────
    // Clicking a row (or its radio) loads that vacancy's details into
    // the preview card and unlocks the "Continue to Applicant Information"
    // button. The server hydrates the requisition fields from the chosen
    // vacancy in saveStep1, so no other form inputs are required here.
    function loadVacancyPreview(vacancyId) {
        if (!vacancyId) {
            $('#vacancyPreviewCard').hide();
            $('#vacancyPreviewBody').empty();
            $('#vacancyNextBtn').prop('disabled', true);
            return;
        }
        $.get('{{ url("/resort/offline-interview/vacancy") }}/' + vacancyId, function (resp) {
            if (!resp || !resp.success || !resp.vacancy) {
                toastr.error((resp && resp.message) || 'Could not load vacancy.', 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }
            var v = resp.vacancy;
            var row = function (label, val) {
                return '<tr><th class="text-muted" style="width:34%;">' + label + '</th><td>' + (val || '—') + '</td></tr>';
            };
            var fmtDate = function (d) {
                if (!d) return '—';
                var dt = new Date(d);
                return isNaN(dt) ? d : dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            };
            var html =
                '<div class="table-responsive"><table class="table mb-0 small">' +
                row('Position', v.position_title) +
                row('Department', v.department_name) +
                row('Section', v.section_name) +
                row('Division', v.division_name) +
                row('Reporting To', v.reporting_to_name && v.reporting_to_name.trim() ? v.reporting_to_name : '—') +
                row('Employee Type', v.employee_type) +
                row('Required From', fmtDate(v.required_starting_date)) +
                row('Budget Salary', v.budgeted_salary) +
                row('Proposed Salary', v.propsed_salary) +
                row('Allowances', v.allowance) +
                row('Medical', v.medical) +
                row('Insurance', v.insurance) +
                row('Pension', v.pension) +
                row('Service Charge', v.service_charge) +
                row('Uniform', v.uniform) +
                row('Recruitment Channels', v.recruitment) +
                '</table></div>';
            $('#vacancyPreviewBody').html(html);
            $('#vacancyPreviewCard').show();
            $('#vacancyNextBtn').prop('disabled', false);
        }).fail(function () {
            toastr.error('Could not load vacancy details.', 'Error', { positionClass: 'toast-bottom-right' });
        });
    }

    // Click row OR change radio → select that vacancy.
    $(document).on('click', '.vacancy-row', function () {
        var id = $(this).data('vacancy-id');
        $('input[name="vacancy_pick"][value="' + id + '"]').prop('checked', true);
        $('#selected_vacancy_id').val(id);
        loadVacancyPreview(id);
    });
    $(document).on('change', 'input[name="vacancy_pick"]', function () {
        var id = $(this).val();
        $('#selected_vacancy_id').val(id);
        loadVacancyPreview(id);
    });

    // When continuing a draft, the controller passes the previously-
    // chosen position_id via #selected_vacancy_id. Pre-load the preview.
    var preselectedVacancyId = $('#selected_vacancy_id').val();
    if (preselectedVacancyId) {
        // The hidden input stores position_id, not vacancy_id, when a
        // draft was saved under the old form. Only pre-load if a row
        // with that vacancy actually exists in the table.
        var $match = $('.vacancy-row[data-vacancy-id="' + preselectedVacancyId + '"]');
        if ($match.length) {
            $match.find('input[name="vacancy_pick"]').prop('checked', true);
            loadVacancyPreview(preselectedVacancyId);
        }
    }

    // ── Cascading dropdowns (division → dept → section + position → reporting) ─
    $(document).on('change', '#division_id', function () {
        var id = $(this).val();
        if (!id) return;
        $.get('{{ url("/resort/get-departments-by-divisions") }}/' + id, function (resp) {
            if (resp.success) {
                var $sel = $('#department_id').empty().append('<option value="">Select Department</option>');
                resp.departments.forEach(function (d) { $sel.append('<option value="' + d.id + '">' + d.name + '</option>'); });
                $sel.trigger('change.select2');
            }
        });
    });
    $(document).on('change', '#department_id', function () {
        var id = $(this).val();
        if (!id) return;
        // Sections
        $.get('{{ url("/resort/get-sections-by-department") }}/' + id, function (resp) {
            if (resp.success) {
                var $sel = $('#section_id').empty().append('<option value="">Select Section</option>');
                resp.sections.forEach(function (s) { $sel.append('<option value="' + s.id + '">' + s.name + '</option>'); });
                $sel.trigger('change.select2');
            }
        });
        // Positions
        $.get('{{ url("/resort/get-positions-by-department") }}/' + id, function (resp) {
            if (resp.success) {
                var $sel = $('#position_id').empty().append('<option value="">Select Position</option>');
                resp.positions.forEach(function (p) { $sel.append('<option value="' + p.id + '">' + p.position_title + '</option>'); });
                $sel.trigger('change.select2');
            }
        });
        // Reporting-to candidates (EXCOM/HOD in this dept)
        $.get('{{ url("/resort/get-reporting-employess-by-department") }}/' + id, function (resp) {
            if (resp.success) {
                var $sel = $('#reporting_to').empty().append('<option value="">Select</option>');
                resp.employees.forEach(function (e) { $sel.append('<option value="' + e.id + '">' + e.name + '</option>'); });
                $sel.trigger('change.select2');
            }
        });
    });

    // ── Save / Next handlers ─────────────────────────────────────────
    function buildStepFormData(step) {
        var fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('offline_interview_id', $('#offline_interview_id').val() || '');

        // Step-specific fields only (avoids accidentally posting fields from other steps).
        $('fieldset[data-step="' + step + '"]').find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name) return;
            if ($el.attr('type') === 'file') {
                if ($el[0].files && $el[0].files.length) {
                    if ($el.attr('multiple')) {
                        for (var i = 0; i < $el[0].files.length; i++) fd.append(name, $el[0].files[i]);
                    } else {
                        fd.append(name, $el[0].files[0]);
                    }
                }
            } else if ($el.attr('type') === 'checkbox') {
                if ($el.is(':checked')) fd.append(name, $el.val());
            } else if ($el.attr('type') === 'radio') {
                if ($el.is(':checked')) fd.append(name, $el.val());
            } else {
                fd.append(name, $el.val() || '');
            }
        });
        return fd;
    }

    var stepRoutes = {
        1: '{{ route("offline-interview.saveStep1") }}',
        2: '{{ route("offline-interview.saveStep2") }}',
        3: '{{ route("offline-interview.saveStep3") }}',
        4: '{{ route("offline-interview.saveStep4") }}',
        5: '{{ route("offline-interview.finalize") }}',
    };

    function postStep(step, advance) {
        var url = stepRoutes[step];
        if (!url) return;
        var fd = buildStepFormData(step);
        $.ajax({
            url: url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (resp) {
                if (resp.success) {
                    if (resp.offline_interview_id) {
                        $('#offline_interview_id').val(resp.offline_interview_id);
                    }
                    if (step === 5) {
                        toastr.success(resp.message || 'Submitted.', 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(function () { window.location.href = '{{ route("offline-interview.index") }}'; }, 1200);
                    } else if (advance) {
                        showStep(step + 1);
                        toastr.success('Saved.', 'Success', { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.success(resp.message || 'Saved as draft.', 'Success', { positionClass: 'toast-bottom-right' });
                    }
                } else {
                    toastr.error(resp.message || 'Could not save this step.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message
                            || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors).flat().join('\n'))))
                    || 'Could not save this step.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    }

    $(document).on('click', '.step-next',       function () { postStep(parseInt($(this).data('from-step'), 10), true);  });
    $(document).on('click', '.step-save-draft', function () { postStep(parseInt($(this).data('from-step'), 10), false); });
    $(document).on('click', '.step-prev',       function () { var n = parseInt($(this).data('from-step'), 10); if (n > 1) showStep(n - 1); });
    $(document).on('click', '#oiSubmit',        function () { postStep(5, true); });
});
</script>
@endsection
