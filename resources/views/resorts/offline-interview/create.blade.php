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
                    <a href="{{ route('offline-interview.index') }}" class="btn btn-themeGrayLight btn-sm">
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

                {{-- ───────────────────── Step 1 ─ Hiring Requisition Form ───────────────────── --}}
                <fieldset data-step="1">
                    <div class="card-title px-3 pt-3"><h3>Hiring Request Form</h3></div>
                    <div class="px-3 pb-3">
                        <div class="row g-md-4 g-3">
                            <div class="col-sm-6">
                                <label class="form-label">BUDGETED OR OUT OF BUDGET?</label>
                                <select name="budgeted_or_out_of_budget" class="form-select select2t-none">
                                    <option value="Budgeted" {{ optional($oi)->budgeted_or_out_of_budget === 'Budgeted' ? 'selected' : '' }}>Budgeted</option>
                                    <option value="Out of Budget" {{ optional($oi)->budgeted_or_out_of_budget === 'Out of Budget' ? 'selected' : '' }}>Out of Budget</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">REQUIRED STARTING DATE</label>
                                <input type="date" name="required_starting_date" class="form-control"
                                       value="{{ optional($oi)->required_starting_date ? \Carbon\Carbon::parse($oi->required_starting_date)->format('Y-m-d') : '' }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">DIVISION</label>
                                <select id="division_id" name="division_id" class="form-select select2t-none">
                                    <option value="">Select Division</option>
                                    @foreach($divisions as $d)
                                        <option value="{{ $d->id }}" {{ optional($oi)->division_id == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">DEPARTMENT</label>
                                <select id="department_id" name="department_id" class="form-select select2t-none">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}" {{ optional($oi)->department_id == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">SECTION</label>
                                <select id="section_id" name="section_id" class="form-select select2t-none">
                                    <option value="">Select Section</option>
                                    @foreach(($sections ?? collect()) as $s)
                                        <option value="{{ $s->id }}" {{ optional($oi)->section_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">POSITION</label>
                                <select id="position_id" name="position_id" class="form-select select2t-none">
                                    <option value="">Select Position</option>
                                    @foreach(($positions ?? collect()) as $p)
                                        <option value="{{ $p->id }}" {{ optional($oi)->position_id == $p->id ? 'selected' : '' }}>{{ $p->position_title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">POSITION TITLE</label>
                                <input type="text" name="position_title" class="form-control" value="{{ optional($oi)->position_title }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">RANK</label>
                                <input type="number" name="rank" class="form-control" value="{{ optional($oi)->rank }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">REPORTING TO</label>
                                <select id="reporting_to" name="reporting_to" class="form-select select2t-none">
                                    <option value="">Select</option>
                                    @foreach(($reportingCandidates ?? collect()) as $emp)
                                        <option value="{{ $emp['id'] }}" {{ optional($oi)->reporting_to == $emp['id'] ? 'selected' : '' }}>{{ $emp['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">EMPLOYEE TYPE</label>
                                @php $et = optional($oi)->employee_type; @endphp
                                @foreach(['Permanant','Casual/Agency','Trainee / Intern','Replacement','Temporary / Project'] as $type)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input emp-type" type="radio" name="employee_type" value="{{ $type }}" id="emp_{{ \Str::slug($type) }}" {{ $et === $type ? 'checked' : '' }}>
                                        <label class="form-check-label" for="emp_{{ \Str::slug($type) }}">{{ $type }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Casual/Agency conditional block --}}
                        <div id="casualAgencyBlock" class="row g-md-4 g-3 mt-1" style="display:none;">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">SERVICE PROVIDER NAME</label>
                                <input type="text" name="service_provider_name" class="form-control" value="{{ optional($oi)->service_provider_name }}">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">SALARY</label>
                                <input type="text" name="salary" class="form-control" value="{{ optional($oi)->salary }}">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">FOOD</label>
                                <input type="text" name="food" class="form-control" value="{{ optional($oi)->food }}">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">ACCOMMODATION</label>
                                <input type="text" name="accommodation" class="form-control" value="{{ optional($oi)->accommodation }}">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">TRANSPORTATION</label>
                                <input type="text" name="transportation" class="form-control" value="{{ optional($oi)->transportation }}">
                            </div>
                        </div>

                        <div class="card-title mt-3"><h3>Budget, Funding &amp; Benefits</h3></div>
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">BUDGET SALARY</label>
                                <input type="number" step="0.01" name="budget_salary" class="form-control" value="{{ optional($oi)->budget_salary }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">ACCOMMODATION (Benefit)</label>
                                <input type="text" name="benefit_accommodation" class="form-control" value="{{ optional($oi)->benefit_accommodation }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label d-block">SERVICE CHARGE</label>
                                @foreach(['Yes','No'] as $v)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="service_charge" value="{{ $v }}" id="sc_{{ $v }}" {{ optional($oi)->service_charge === $v ? 'checked' : '' }}>
                                        <label class="form-check-label" for="sc_{{ $v }}">{{ $v }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">PROPOSED SALARY</label>
                                <input type="number" step="0.01" name="proposed_salary" class="form-control" value="{{ optional($oi)->proposed_salary }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">ALLOWANCES</label>
                                <input type="text" name="allowances" class="form-control" value="{{ optional($oi)->allowances }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label d-block">UNIFORM</label>
                                @foreach(['Yes','No'] as $v)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="uniform" value="{{ $v }}" id="un_{{ $v }}" {{ optional($oi)->uniform === $v ? 'checked' : '' }}>
                                        <label class="form-check-label" for="un_{{ $v }}">{{ $v }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">MEDICAL</label>
                                <input type="text" name="medical" class="form-control" value="{{ optional($oi)->medical }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">INSURANCE</label>
                                <input type="text" name="insurance" class="form-control" value="{{ optional($oi)->insurance }}">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">PENSION</label>
                                <input type="text" name="pension" class="form-control" value="{{ optional($oi)->pension }}">
                            </div>
                        </div>

                        <div class="card-title mt-3"><h3>Recruitment</h3></div>
                        <div class="row g-md-4 g-3">
                            @foreach(['online_posting' => 'Online job posting','recruiter' => 'Recruiter','agency' => 'Agency'] as $val => $lbl)
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recruitment_methods[]" value="{{ $val }}" id="rm_{{ $val }}" {{ in_array($val, $rec, true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="rm_{{ $val }}">{{ $lbl }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer text-end px-3 pb-3">
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-save-draft" data-from-step="1">Save As Draft</button>
                        <button type="button" class="btn btn-themeSkyblue btn-sm step-next" data-from-step="1">Next</button>
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
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-prev" data-from-step="2">Back</button>
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-save-draft" data-from-step="2">Save As Draft</button>
                        <button type="button" class="btn btn-themeSkyblue btn-sm step-next" data-from-step="2">Next</button>
                    </div>
                </fieldset>

                {{-- ───────────────────── Step 3 ─ Upload Candidate Documents ───────────────────── --}}
                <fieldset data-step="3" style="display:none;">
                    <div class="card-title px-3 pt-3"><h3>Upload Candidate Documents</h3></div>
                    <div class="px-3 pb-3">
                        <label class="form-label">UPLOAD CV &amp; OTHER RELEVANT DOCUMENTS (PNG, JPEG, PDF, Excel — multiple files allowed)</label>
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
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-prev" data-from-step="3">Back</button>
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-save-draft" data-from-step="3">Save As Draft</button>
                        <button type="button" class="btn btn-themeSkyblue btn-sm step-next" data-from-step="3">Next</button>
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
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-prev" data-from-step="4">Back</button>
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-save-draft" data-from-step="4">Save As Draft</button>
                        <button type="button" class="btn btn-themeSkyblue btn-sm step-next" data-from-step="4">Next</button>
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
                                <label class="form-label">SEND AN OFFER LETTER (PNG, JPEG, PDF)</label>
                                <input type="file" name="offer_letter" class="form-control" accept=".pdf,.png,.jpg,.jpeg">
                                @if($oi && $oi->offer_letter_path)
                                    <small class="text-muted d-block mt-1">
                                        Current: <a href="{{ \Storage::url($oi->offer_letter_path) }}" target="_blank">{{ basename($oi->offer_letter_path) }}</a>
                                    </small>
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
                        <button type="button" class="btn btn-themeGrayLight btn-sm me-2 step-prev" data-from-step="5">Back</button>
                        <button type="button" class="btn btn-themeSkyblue btn-sm" id="oiSubmit">Submit</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
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
