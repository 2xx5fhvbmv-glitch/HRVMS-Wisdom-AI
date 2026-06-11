@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div class="card">
                <form id="msform" class="peopleEmpCreation-form" enctype="multipart/form-data">
                    <!-- progressbar -->
                    <div class="progressbar-wrapper">
                        <ul id="progressbar" class="progressbar-tab d-flex justify-content-between align-items-center ">
                            <li class="active current"> <span>Personal Details</span></li>
                            <li><span>Employment</span></li>
                            <li><span>Education/Qualification</span></li>
                            <li><span>Experience</span></li>
                        </ul>
                    </div>
                    <hr>
                    <fieldset data-setp="1">
                        {{-- ───────────────── Hire against a vacancy (REQUIRED) ──────────────────
                             Sits at the top of Step 1 and is now mandatory — every new hire must
                             be tied to an open TA-approved vacancy. The picker only shows
                             vacancies with remaining slots > 0; EmployeeController::store() re-
                             checks the slot count inside its DB transaction so two HRs can't
                             both fill the last slot of the same vacancy.
                             Server provides $vacancies from EmployeeController::create using the
                             same TA-final-approval filter as /talent-acquisition/get-offline-interview. --}}
                        <div class="cardBorder-block mb-3" id="vacancyPickerPanel">
                            <div class="card-title px-3 pt-3 d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse" data-bs-target="#vacancyPickerCollapse"
                                 aria-expanded="true" aria-controls="vacancyPickerCollapse"
                                 style="cursor: pointer;">
                                <div>
                                    <h3 class="mb-0">
                                        <i class="fa-solid fa-briefcase me-2 text-primary"></i>
                                        Hire against a vacancy
                                        <span class="text-danger ms-1">*</span>
                                    </h3>
                                    <p class="small text-muted mb-0 mt-1">
                                        Required. Pick an open vacancy — Department, Position and Division on Step 2 will be pre-filled and locked. Fully-filled vacancies are hidden automatically.
                                    </p>
                                </div>
                                <i class="fa-solid fa-chevron-down" id="vacancyPickerChevron"></i>
                            </div>
                            <div class="collapse show" id="vacancyPickerCollapse">
                                <div class="px-3 pb-3">
                                    <input type="hidden" id="selected_vacancy_id" name="vacancy_id" value="">
                                    <input type="hidden" id="selected_vacancy_position_id" name="vacancy_position_id" value="">
                                    <input type="hidden" id="selected_vacancy_department_id" name="vacancy_department_id" value="">
                                    <input type="hidden" id="selected_vacancy_division_id" name="vacancy_division_id" value="">

                                    @if($vacancies->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-LearningProgram w-100 mb-0" id="vacancyPickerTable">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Position</th>
                                                        <th>Department</th>
                                                        <th>Division</th>
                                                        <th>Open slots</th>
                                                        <th>Link expiry</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($vacancies as $v)
                                                        <tr class="vacancy-row"
                                                            data-vacancy-id="{{ $v->vacancy_id }}"
                                                            data-position-id="{{ $v->position_id }}"
                                                            data-position-title="{{ $v->position_title }}"
                                                            data-department-id="{{ $v->department_id }}"
                                                            data-department-name="{{ $v->department_name }}"
                                                            data-division-id="{{ $v->division_id }}"
                                                            data-division-name="{{ $v->division_name }}"
                                                            data-gm-approved-iso="{{ $v->gm_approved_at_iso }}"
                                                            data-gm-approved-label="{{ $v->gm_approved_at_label }}"
                                                            style="cursor: pointer;">
                                                            <td><input type="radio" name="vacancy_pick" value="{{ $v->vacancy_id }}" required></td>
                                                            <td>{{ $v->position_title }}</td>
                                                            <td>{{ $v->department_name }} <span class="badge bg-light text-dark ms-1">{{ $v->department_code }}</span></td>
                                                            <td>{{ $v->division_name ?? '—' }}</td>
                                                            <td>
                                                                <span class="badge bg-success-subtle text-success">
                                                                    {{ $v->remaining_slots }} of {{ $v->no_of_positions }} left
                                                                </span>
                                                            </td>
                                                            <td>{{ $v->expiry_date_label }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Selected-vacancy summary + clear button (visible only after a pick). --}}
                                        <div id="vacancyPickerSummary" class="alert alert-info mt-3 d-none" role="alert">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>Hiring against:</strong>
                                                    <span id="vacancyPickerSummaryText"></span>
                                                    <div class="small text-muted mt-1">
                                                        Department, Position and Division on Step 2 will be pre-filled and locked.
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="vacancyPickerClear">
                                                    Clear selection
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0" role="alert">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                            <strong>No open vacancies available.</strong>
                                            New hires must be tied to an approved, unfilled vacancy.
                                            Create one in <strong>Talent Acquisition &rarr; Vacancies</strong>
                                            and complete the approval flow before returning here.
                                            <div class="mt-2">
                                                <a href="{{ route('resort.vacancies.create') }}" class="btn btn-sm btn-themeBlue">
                                                    <i class="fa-solid fa-plus me-1"></i>Create a Vacancy
                                                </a>
                                            </div>
                                        </div>
                                        {{-- Flag for the page-load JS at the bottom of the file:
                                             when this is present we disable every input, file picker,
                                             select, textarea and Next/Submit button OUTSIDE the
                                             vacancy panel so HR can't fill out a hire that has no
                                             vacancy to attach to. The alert above tells them why. --}}
                                        <input type="hidden" id="no-vacancies-flag" value="1">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="peopleEmpCreationPersonalDetails-form mt-2">
                            <div class="row g-md-3 g-2 mb-md-5 mb-4">
                                <div class="col-12">
                                    <div class="upload-area drop-zone" id="uploadfile">
                                        <div class="d-flex align-items-center text-start drop-zone__prompt">
                                            <div class="img-box">
                                                <img src="{{ URL::asset('resorts_assets/images/upload.svg') }}"
                                                    alt="" class="img-fluid" />
                                            </div>
                                            <div>
                                                <h3>Upload Your CV</h3>
                                                <span>PDF Format</span>
                                            </div>
                                        </div>
                                        <p>Browse or Drag the file here</p>
                                        <input type="file" id="fileInput" name="cv" class="drop-zone__input"
                                            data-parsley-required="true"
                                            data-parsley-required-message="Please upload your CV" accept=".pdf" />
                                        <div id="cv-file-name" class="mt-2 text-center text-dark fw-bold"></div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <label class="form-label">PASSPORT-SIZE PHOTO<span class="red-mark">*</span></label>
                                    <div class="d-md-flex align-items-center">
                                        <div>
                                            <div class="profile-img-box">
                                                <img src="" id="profilePicturePreview" width="100" />
                                            </div>
                                            <div id="profile-picture-file-name" class="mt-2 text-center text-dark fw-bold">
                                            </div>
                                        </div>
                                        <div class="uploadFile-block mt-md-0 mt-3">
                                            <div class="uploadFile-btn me-0">
                                                <a href="javascript:void(0);" class="btn btn-themeBlue btn-sm"> Upload
                                                    Photo</a>
                                                <input type="file" name="profile_picture" id="profile_picture"
                                                    accept="image/*" data-parsley-required="true"
                                                    data-parsley-required-message="Please upload your passport size photo">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">FULL-LENGTH PHOTO<span class="red-mark">*</span></label>
                                    <div class="d-md-flex align-items-center">
                                        <div>
                                            <div class="profile-img-box">
                                                <img src="" id="profilePreviewfullimg" width="100" />
                                            </div>
                                            <div id="profile-full-length-photo-file-name"
                                                class="mt-2 text-center text-dark fw-bold"></div>
                                        </div>

                                        <div class="uploadFile-block mt-md-0 mt-3">
                                            <div class="uploadFile-btn me-0">
                                                <a href="javascript:void(0);" class="btn btn-themeBlue btn-sm"> Upload
                                                    Photo</a>
                                                <input type="file" name="full_length_photo" id="full_length_photos"
                                                    accept="image/*" data-parsley-required="true"
                                                    data-parsley-required-message="Please upload your full length photo">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="employeeF_name" class="form-label">First Name <span
                                            class="req_span">*</span> </label>
                                    <input type="text" class="form-control" id="employeeF_name" name="employeeF_name"
                                        placeholder="First Name" required data-parsley-pattern="^[a-zA-Z\s]+$"
                                        data-parsley-required-message="First name is required."
                                        data-parsley-pattern-message="Only letters are allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="employeeL_name" class="form-label">Last Name <span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="employeeL_name" name="employeeL_name"
                                        placeholder="Last Name" required data-parsley-pattern="^[a-zA-Z\s]+$"
                                        data-parsley-required-message="Last name is required."
                                        data-parsley-pattern-message="Only letters are allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="email_address" class="form-label">Official Email Address<span
                                            class="req_span">*</span></label>
                                    <input type="email" class="form-control" id="email_address" name="email_address"
                                        placeholder="Official Email Address" required data-parsley-type="email"
                                        data-parsley-required-message="Official email address is required."
                                        data-parsley-type-message="Please enter a valid email address.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="mobile_num" class="form-label">Official Mobile Number<span
                                            class="req_span">*</span></label>
                                    <div>
                                        <input type="tel" class="form-control" id="mobile_num" name="mobile_num"
                                            placeholder="e.g. +960 9123456" required
                                            inputmode="tel" autocomplete="tel" maxlength="25"
                                            data-parsley-required-message="Mobile number is required."
                                            data-parsley-mobile_number>
                                        <small class="form-text text-muted">Include country code, e.g. +91 9098765432</small>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="date_birth" class="form-label">Date of Birth <span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control datepicker" id="date_birth"
                                        name="date_birth" placeholder="Date of Birth" required
                                        data-parsley-required-message="Date of Birth is required."
                                        data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                        data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format."
                                        data-parsley-date="past">
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="gender" class="form-label">Gender <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        {{-- "Other" intentionally removed — HR policy: gender
                                             collected only for legal/visa records where
                                             Male / Female are the only accepted values. --}}
                                    </select>
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="marital_status" class="form-label">Marital Status <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="marital_status" required
                                        name="marital_status" data-placeholder="Marital Status">
                                        <option></option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="nationality" class="form-label" required>Nationality <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="nationality" required
                                        name="nationality" data-placeholder="Nationality">
                                        <option></option>
                                        @foreach ($nationalitys as $nationality)
                                            <option value="{{ $nationality }}">{{ $nationality }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="religion" class="form-label">Religion <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="religion" name="religion" required
                                        data-placeholder="Religion">
                                        <option></option>
                                        <option value="0">Non-Muslim</option>
                                        <option value="1">Muslim</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="blood_group" class="form-label">Blood Group <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="blood_group" name="blood_group"
                                        required data-placeholder="Blood Group">
                                        <option></option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                                {{-- Passport Number and NID are mutually-exclusive
                                     identification documents. The form requires AT
                                     LEAST ONE — passport-only (expat), NID-only
                                     (Maldivian), or both. The * markers + required
                                     state on each are recomputed every time either
                                     field changes by syncIdDocRequirements() bound
                                     in the scripts section.

                                     Additionally: Passport Expiry Date becomes
                                     required ONLY when a Passport Number is entered. --}}
                                <div class="col-lg-4 col-sm-6">
                                    <label for="passport_numb" class="form-label">
                                        PASSPORT NUMBER
                                        <span class="req_span passport-required-marker">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="passport_numb" name="passport_numb"
                                        placeholder="Passport Number"
                                        data-parsley-required-message="Enter either a Passport Number or NID."
                                        data-parsley-pattern="^[A-Za-z0-9]{5,20}$"
                                        data-parsley-pattern-message="Please enter a valid passport number (5-20 alphanumeric characters).">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="passport_expiry_date" class="form-label">
                                        Passport expiry date
                                        <span class="req_span passport-expiry-required-marker d-none">*</span>
                                    </label>
                                    <input type="text" class="form-control datepicker" id="passport_expiry_date"
                                        name="passport_expiry_date" placeholder="Passport Expiry Date"
                                        data-parsley-required-message="Passport expiry date is required when a passport number is entered."
                                        data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                        data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format.">
                                </div>

                                <div class="col-lg-4 col-sm-6">
                                    <label for="nid" class="form-label">
                                        NID
                                        <span class="req_span nid-required-marker">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nid" name="nid"
                                        placeholder="NID"
                                        data-parsley-required-message="Enter either a Passport Number or NID."
                                        data-parsley-pattern="^[A-Z]{1,2}[0-9]{6,9}$"
                                        data-parsley-pattern-message="Please enter a valid Maldivian NID. It should start with 1-2 uppercase letters followed by 6-9 digits."
                                        data-parsley-trigger="input">
                                </div>
                                {{-- PRESENT ADDRESS first (the source). Filling it powers the
                                     "Same as Present" toggle on the Permanent block below. --}}
                                <div class="col-12">
                                    <div class="address-block">
                                        <div class="row g-md-3 g-2 align-items-end">
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="present_addLine1" class="form-label">PRESENT ADDRESS<span
                                                        class="req_span">*</span></label>
                                                <input type="text" class="form-control" id="present_addLine1"
                                                    name="present_addLine1" placeholder="Address Line 1" required>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" name="present_addLine2"
                                                    placeholder="Address Line 2">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" id="present_city"
                                                    name="present_city" placeholder="Enter City" required>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" id="present_state"
                                                    name="present_state" placeholder="Enter State" required>
                                            </div>

                                            <div class="col-lg-4 col-sm-6">
                                                <input type="number" class="form-control" placeholder="Postal Code"
                                                    name="present_postal_code" required
                                                    data-parsley-required-message="Postal code is required."
                                                    data-parsley-type="digits"
                                                    data-parsley-type-message="Please enter a valid 5-digit postal code."
                                                    data-parsley-pattern="^\d{5}$"
                                                    data-parsley-pattern-message="Postal code must be exactly 5 digits.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                                <select class="form-select select2t-none" data-placeholder="Country"
                                                    name="present_country" required>
                                                    <option></option>
                                                    @foreach ($countries as $country)
                                                        <option value="{{ $country }}">{{ $country }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- PERMANENT ADDRESS second. The "Same as Present" toggle
                                     above the block copies the Present values down, live-
                                     mirrors edits while checked, and clears the Permanent
                                     fields on uncheck. JS: copyPresentToPermanent /
                                     clearPermanentAddress + the delegated change handler. --}}
                                <div class="col-12">
                                    <div class="address-block">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="sameAsPresentAddress">
                                            <label class="form-check-label small text-muted" for="sameAsPresentAddress">
                                                Permanent address is same as Present address
                                            </label>
                                        </div>
                                        <div class="row g-md-3 g-2 align-items-end">
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="permanent_addLine1" class="form-label">PERMANENT
                                                    ADDRESS<span class="req_span">*</span></label>
                                                <input type="text" class="form-control" id="permanent_addLine1"
                                                    name="permanent_addLine1" placeholder="Address Line 1" required>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" name="parmanent_addline2"
                                                    placeholder="Address Line 2">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" id="city"
                                                    name="parmanent_city" placeholder="Enter City" required>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" id="parmanent_state"
                                                    name="parmanent_state" placeholder="Enter State" required>
                                            </div>

                                            <div class="col-lg-4 col-sm-6">
                                                <input type="number" class="form-control" placeholder="Postal Code"
                                                    name="parmanent_postal_code" required
                                                    data-parsley-required-message="Postal code is required."
                                                    data-parsley-type="digits"
                                                    data-parsley-type-message="Please enter a valid 5-digit postal code."
                                                    data-parsley-pattern="^\d{5}$"
                                                    data-parsley-pattern-message="Postal code must be exactly 5 digits.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                                <select class="form-select select2t-none" data-placeholder="Country"
                                                    name="parmanent_country" required>
                                                    <option></option>
                                                    @foreach ($countries as $country)
                                                        <option value="{{ $country }}">{{ $country }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Emergency Contact Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_name" class="form-label">First Name<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="emg_first_name"
                                        name="emg_contact_fname" placeholder="First Name" required
                                        data-parsley-pattern="^[a-zA-Z\s]+$"
                                        data-parsley-required-message="First name is required."
                                        data-parsley-pattern-message="Only letters are allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_name" class="form-label">Last Name<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="emg_last_name"
                                        name="emg_contact_lname" placeholder="Last Name" required
                                        data-parsley-pattern="^[a-zA-Z\s]+$"
                                        data-parsley-required-message="Last name is required."
                                        data-parsley-pattern-message="Only letters are allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_name" class="form-label">Email<span class="req_span">*</span></label>
                                    <input type="email" class="form-control" id="emg_email" name="emg_contact_email"
                                        placeholder="Enter Email" required data-parsley-type="email"
                                        data-parsley-required-message="Email is required."
                                        data-parsley-type-message="Please enter a valid email address.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_number" class="form-label">Contact Number<span
                                            class="req_span">*</span></label>
                                    {{-- type=tel (not number) so the leading "+" character isn't
                                         silently stripped by the browser. --}}
                                    <input type="tel" class="form-control" id="emg_number" name="emg_contact_number"
                                        placeholder="e.g. +960 9123456" required
                                        inputmode="tel" autocomplete="tel" maxlength="25"
                                        data-parsley-required-message="Mobile number is required."
                                        data-parsley-mobile_number>
                                    <small class="form-text text-muted">Include country code, e.g. +91 9098765432</small>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="emg_relation" class="form-label">Relation <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="emg_relation"
                                        name="emg_contact_relation" required aria-label="Default select example">
                                        <option value="" selected disabled>Select Relation</option>
                                        <option value="father">Father</option>
                                        <option value="mother">Mother</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="friend">Friend</option>
                                        <option value="relative">Relative</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="emg_contact_nationality" class="form-label">Nationality <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="emg_contact_nationality"
                                        name="emg_contact_nationality" required aria-label="Default select">
                                        <option value="" selected disabled>Select Nationality</option>
                                        @foreach ($nationalitys as $nationality)
                                            <option value="{{ $nationality }}">{{ $nationality }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="address-block">
                                        <div class="row g-md-3 g-2 align-items-end">
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="emg_add_addLine1" class="form-label">ADDRESS</label>
                                                <input type="text" class="form-control" id="emg_add_addLine1"
                                                    name="emg_contact_add_addLine1" placeholder="Address Line 1">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" name="emg_add_line2"
                                                    placeholder="Address Line 2">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" id="city"
                                                    name="emg_cont_city" placeholder="Enter City" required>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" id="State"
                                                    name="emg_cont_state" placeholder="Enter State" required>
                                            </div>

                                            <div class="col-lg-4 col-sm-6">
                                                <input type="number" class="form-control" placeholder="Postal Code"
                                                    name="emg_cont_postal_code" required
                                                    data-parsley-required-message="Postal code is required."
                                                    data-parsley-type="digits"
                                                    data-parsley-type-message="Please enter a valid 5-digit postal code."
                                                    data-parsley-pattern="^\d{5}$"
                                                    data-parsley-pattern-message="Postal code must be exactly 5 digits.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                                <select class="form-select select2t-none" name="emg_cont_country"
                                                    data-placeholder="Country" required>
                                                    <option></option>
                                                    @foreach ($countries as $country)
                                                        <option value="{{ $country }}">{{ $country }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-title">
                                <h3>Additional Information</h3>
                            </div>
                            <div class="employeeLanguageRepeater-main">
                                <div class="employeeLanguageRepeater-block">
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3 align-items-end">
                                        <div class="col-lg-5 col-sm-6">
                                            <label class="form-label">Language</label>
                                            <input type="text" class="form-control language-input"
                                                placeholder="Language">
                                        </div>
                                        <div class="col-lg-5 col-sm-6 emp_createion_sel">
                                            <label class="form-label">Proficiency Level </label>
                                            <select class="form-select select2t-none proficiency-level-select">
                                                <option value="" selected disabled readonly>Select Level</option>
                                                <option value="Beginner">Beginner</option>
                                                <option value="Intermediate">Intermediate</option>
                                                <option value="Advanced">Advanced</option>
                                                <option value="Fluent">Fluent</option>
                                                <option value="Native">Native</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-1 col-sm-12 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <a href="javascript:void(0);"
                                                    class="btn btn-themeSkyblue btn-sm blockAdd-btn w-100">Add</a>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-danger btn-sm remove-btn w-100"
                                                    style="display:none;">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        <a href="javascript:void(0);" class="btn btn-themeBlue btn-sm float-end next ">Next</a>
                    </fieldset>

                    <fieldset data-setp="2">
                        <div class="mt-2">
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="employee_id" class="form-label">Employee ID </label>
                                    <input type="text" readonly class="form-control" id="employee_id"
                                        name="employee_id" value="{{ $employee_id }}" placeholder="Employee ID">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="email_add_step2" class="form-label">Official Email Address<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="email_add_step2"
                                        name="email_add_step2" placeholder="Official Email Address" required>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="mobile_num_s2" class="form-label">Official Mobile Number<span
                                            class="req_span">*</span></label>
                                    <input type="tel" class="form-control" id="mobile_num_s2" name="mobile_num_s2"
                                        required placeholder="e.g. +960 9123456"
                                        inputmode="tel" autocomplete="tel" maxlength="25"
                                        data-parsley-required-message="Mobile number is required."
                                        data-parsley-mobile_number>
                                    <small class="form-text text-muted">Include country code, e.g. +91 9098765432</small>
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="division" class="form-label">Division <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="division" name="division"
                                        data-placeholder="Select Division" required>
                                        <option></option>
                                        @foreach ($resort_divisions as $devision)
                                            <option value="{{ $devision->id }}">{{ $devision->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="department" class="form-label">Department <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="department" name="department"
                                        data-placeholder="Department" required>
                                        <option></option>
                                        {{-- Options loaded by AJAX --}}
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="section" class="form-label">Section</label>
                                    {{-- Starts disabled — Section is gated on Department
                                         (and on the chosen department actually HAVING sections).
                                         The cascade JS toggles this back on when there are options. --}}
                                    <select class="form-select select2t-none" id="section" name="section"
                                        data-placeholder="Section" disabled>
                                        <option></option>
                                        {{-- Options loaded by AJAX --}}
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="position" class="form-label">Position <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="position" name="position"
                                        data-placeholder="Position" required>
                                        <option></option>
                                        {{-- Options loaded by AJAX --}}
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="benefit_grid_level" class="form-label">Benefit Grid Level<span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="benefit_grid_level"
                                        name="benefit_grid_level" data-placeholder="Benefit Grid Level">
                                        <option></option>
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="reporting_person" class="form-label">Reporting Person <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="reporting_person"
                                        name="reporting_person" data-placeholder="Reporting Person" required>
                                        <option></option>
                                        {{-- Options loaded by AJAX --}}
                                    </select>
                                </div>

                                <input type="hidden" name="position_rank" id="position_rank" value="">

                                <div class="col-lg-4 col-sm-6">
                                    <label for="joining_date" class="form-label">Joining date<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control datepicker" id="joining_date"
                                        name="joining_date" placeholder="Joining date" required autocomplete="off"
                                        data-parsley-required-message="Passport expiry date is required."
                                        data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                        data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format.">
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="employment_status" class="form-label">EMPLOYMENT STATUS<span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="employment_status"
                                        name="employment_status" data-placeholder="Employment Status" required>
                                        <option></option>
                                        <option value="Full-Time">Full-Time</option>
                                        <option value="Part-Time">Part-Time</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Casual">Casual</option>
                                        <option value="Probationary">Probationary</option>
                                        <option value="Internship">Internship</option>
                                        <option value="Temporary">Temporary</option>
                                    </select>
                                </div>


                                <div class="col-lg-4 col-sm-6">
                                    <label for="probation_exp_date" class="form-label">
                                        PROBATION EXP DATE<span class="req_span">*</span>
                                    </label>
                                    {{-- Auto-derived from Joining Date + 3 months whenever
                                         EMPLOYMENT STATUS is Probationary. Disabled so HR
                                         can't drift the probation window off the standard
                                         policy. The server recomputes the same value on
                                         save, so a tampered/disabled-but-empty submission
                                         still lands the correct date. --}}
                                    <input type="text" class="form-control" id="probation_exp_date"
                                        name="probation_exp_date" placeholder="dd/mm/yyyy" disabled readonly
                                        title="Auto-set to Joining Date + 3 months">
                                    <small class="form-text text-muted">Auto: joining date + 3 months</small>
                                </div>

                                <div class="col-lg-4 col-sm-6">
                                    <label for="contract_type" class="form-label">Contract Type</label>
                                    <input type="text" class="form-control" id="contract_type" name="contract_type"
                                        placeholder="Enter Contract Type" required>

                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="tin" class="form-label">TIN</label>
                                    <input type="text" class="form-control" id="tin" name="tin"
                                        placeholder="TIN">
                                </div>


                            </div>
                            <div class="card-title">
                                <h3>Salary Details</h3>
                            </div>
                            <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="basic_salary" class="form-label">Basic Salary <span
                                            class="req_span">*</span></label>
                                    {{-- Currency-symbol prefix syncs with Currency Type.
                                         updateSalaryCurrencySymbol() below keeps both
                                         the basic-salary and pension prefixes in step. --}}
                                    <div class="input-group">
                                        <span class="input-group-text salary-currency-symbol" id="basic_salary_symbol">MVR</span>
                                        <input type="number" min="1" step="0.01" class="form-control" id="basic_salary"
                                            name="basic_salary" placeholder="Basic Salary" required
                                            data-parsley-required-message="Basic salary is required."
                                            data-parsley-type="number" data-parsley-min="1"
                                            data-parsley-type-message="Please enter a valid number."
                                            data-parsley-min-message="Basic salary must be at least 1.">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="currency_type" class="form-label">Currency Type <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="currency_type"
                                        name="basic_salary_currency" required aria-label="Default select example">
                                        <option value="MVR">MVR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>

                                <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                    <label for="payment_mode" class="form-label">PAYMENT MODE <span
                                            class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="payment_mode" name="payment_mode"
                                        required aria-label="Default select example">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank">Bank</option>
                                    </select>
                                </div>

                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="pension" class="form-label">Pension</label>
                                        <div class="input-group">
                                            <span class="input-group-text salary-currency-symbol" id="pension_symbol">MVR</span>
                                            <input type="number" min="0" step="0.01" class="form-control" id="pension"
                                                name="pension" placeholder="Pension" data-parsley-required="false"
                                                data-parsley-required-message="Pension is required for Maldivian employees."
                                                data-parsley-min="0" data-parsley-min-message="Pension must be at least 0."
                                                data-parsley-trigger="change">
                                        </div>
                                        <small class="form-text text-muted">7% of Basic Salary for Maldivian employees.</small>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <label for="ewt_status" class="form-label">EWT STATUS</label>
                                        {{-- <input type="text" class="form-control" id="" name="ewt_status" placeholder="EWT"> --}}
                                        <div>
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="ewt_status" name="ewt_status">
                                                <label class="form-check-label" for="ewt_status">Yes</label>
                                            </div>
                                            <span class="badge bg-info text-dark d-none mt-2" id="ewt_actvity">
                                                Earning MVR 30,000 or more — employee may be eligible for EWT registration
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <label for="entitle_service_charge" class="form-label">ENTITLE FOR SERVICE CHARGE</label>
                                        <div>
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="entitle_service_charge" name="entitle_service_charge">
                                                <label class="form-check-label" for="entitle_service_charge">Yes</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <label for="entitle_overtime" class="form-label">ENTITLE FOR OVERTIME</label>
                                        <div>
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="entitle_overtime" name="entitle_overtime">
                                                <label class="form-check-label" for="entitle_overtime">Yes</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <label for="entitle_public_holiday" class="form-label">ENTITLE FOR PUBLIC
                                            HOLIDAY
                                            OVERTIME</label>
                                        <div>
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="entitle_public_holiday"
                                                    name="entitle_public_holiday">
                                                <label class="form-check-label"
                                                    for="entitle_public_holiday">Yes</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="allowanceRepeater-main">
                                        <div class="row allowanceRepeater-block g-2 mb-2">
                                            <div class="col-4 emp_createion_sel">
                                                <label class="form-label">Allowance Type <span
                                                        class="req_span">*</span></label>
                                                <select class="form-select select2t-none allowance-type-select"
                                                    name="allowance[0][type]" required
                                                    data-parsley-required-message="Allowance type is required.">
                                                    <option value="">Select Allowance</option>
                                                    @foreach ($payrollAllowance as $allowance)
                                                        <option value="{{ $allowance->id }}">
                                                            {{ $allowance->particulars }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label">Amount <span class="req_span">*</span></label>
                                                <input type="number" min="0"
                                                    class="form-control allowance-amount-input"
                                                    name="allowance[0][amount]" placeholder="Amount" required
                                                    data-parsley-required-message="Allowance amount is required."
                                                    data-parsley-type="number" data-parsley-min="0"
                                                    data-parsley-type-message="Please enter a valid number."
                                                    data-parsley-min-message="Amount must be at least 0.">
                                            </div>
                                            <div class="col-3 emp_createion_sel">
                                                <label class="form-label">Currency <span class="req_span">*</span></label>
                                                <select class="form-select select2t-none allowance-currency-select"
                                                    name="allowance[0][currency]" required
                                                    data-parsley-required-message="Currency is required.">
                                                    <option value="">Select Currency</option>
                                                    <option value="MVR">MVR</option>
                                                    <option value="USD">USD</option>
                                                </select>
                                            </div>
                                            <div class="col-1 d-flex align-items-end">
                                                <a href="javascript:void(0);"
                                                    class="btn btn-themeSkyblue btn-sm allowanceAdd-btn">Add</a>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-danger btn-sm allowanceRemove-btn ms-1"
                                                    style="display:none;">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="card-title">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3>Bank Details</h3>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);"
                                            class="btn btn-themeSkyblue btn-sm bankAdd-btn">Add</a>
                                    </div>
                                </div>
                            </div>
                            <div class="bankRepeater-main">
                                <div class="row g-md-3 g-2 mb-md-4 mb-3 bankRepeater-block">
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">Bank Name <span class="req_span">*</span></label>
                                        <input type="text" class="form-control bank_name" name="bank[0][bank_name]"
                                            placeholder="Bank Name" required
                                            data-parsley-required-message="Bank name is required."
                                            data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                            data-parsley-pattern-message="Please enter a valid bank name.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">Bank Location/Branch <span
                                                class="req_span">*</span></label>
                                        <input type="text" class="form-control bank_branch"
                                            name="bank[0][bank_branch]" placeholder="Bank Location/Branch" required
                                            data-parsley-required-message="Bank branch is required."
                                            data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                            data-parsley-pattern-message="Please enter a valid branch name.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">Account Type <span class="req_span">*</span></label>
                                        <input type="text" class="form-control account_type"
                                            name="bank[0][account_type]" placeholder="Account Type" required
                                            data-parsley-required-message="Account type is required."
                                            data-parsley-pattern="^[a-zA-Z\s]+$"
                                            data-parsley-pattern-message="Please enter a valid account type.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">IFSC/SWIFT/BIC Code <span
                                                class="req_span">*</span></label>
                                        <input type="text" class="form-control ifsc" name="bank[0][ifsc]"
                                            placeholder="IFSC/SWIFT/BIC Code" required
                                            data-parsley-required-message="IFSC/SWIFT/BIC code is required."
                                            data-parsley-pattern="^[A-Za-z0-9]{6,15}$"
                                            data-parsley-pattern-message="Please enter a valid IFSC/SWIFT/BIC code.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">Account Holder's Name <span
                                                class="req_span">*</span></label>
                                        <input type="text" class="form-control account_name"
                                            name="bank[0][account_name]" placeholder="Account Holder's Name" required
                                            data-parsley-required-message="Account holder's name is required."
                                            data-parsley-pattern="^[a-zA-Z\s\.]+$"
                                            data-parsley-pattern-message="Please enter a valid name.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">Account Number <span class="req_span">*</span></label>
                                        <input type="text" class="form-control account_number"
                                            name="bank[0][account_number]" placeholder="Account Number" required
                                            data-parsley-required-message="Account number is required."
                                            data-parsley-pattern="^[0-9]{6,20}$"
                                            data-parsley-pattern-message="Please enter a valid account number (6-20 digits).">
                                    </div>
                                    <div class="col-lg-4 col-sm-6 emp_createion_sel">
                                        <label class="form-label">Currency <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none currency" name="bank[0][currency]"
                                            required data-parsley-required-message="Currency is required.">
                                            <option value="">Select Currency</option>
                                            <option value="MVR">MVR</option>
                                            <option value="USD">USD</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label class="form-label">IBAN</label>
                                        {{-- IBAN is optional — not every bank/country issues
                                             one (e.g. domestic Maldivian accounts). Pattern
                                             validator still fires when a value IS entered so
                                             a typo'd IBAN is caught. --}}
                                        <input type="text" class="form-control iban" name="bank[0][iban]"
                                            placeholder="IBAN (optional)"
                                            data-parsley-pattern="^[A-Z0-9]{8,34}$"
                                            data-parsley-pattern-message="Please enter a valid IBAN (8-34 alphanumeric characters, uppercase).">
                                    </div>

                                    <div class="col-lg-4 col-sm-6 d-flex align-items-end">

                                        <a href="javascript:void(0);" class="btn btn-danger btn-sm bankRemove-btn ms-2"
                                            style="display:none;">Remove</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <hr class="hr-footer ">
                        <a href="javascript:void(0);" class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href="javascript:void(0);"
                            class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Previous</a>
                    </fieldset>

                    <fieldset data-setp="3">
                        <div class="mt-2">
                            <div class="employeeEducationRepeater-main">
                                <div class="employeeEducationRepeater-block">
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                        <div class="col-12 d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label mb-0 fw-bold">Education / Qualification</label>
                                            <div class="d-flex gap-2">
                                                <a href="javascript:void(0);"
                                                    class="btn btn-themeSkyblue btn-sm education-add-btn">Add</a>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-danger btn-sm education-remove-btn"
                                                    style="display:none;">Remove</a>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">DOCUMENT UPLOAD / CERTIFICATE ATTACHMENT <span
                                                    class="req_span">*</span></label>
                                            <div class="uploadFile-block">
                                                <div class="uploadFile-btn">
                                                    <a href="javascript:void(0);"
                                                        class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                                    <input type="file" class="education-upload-input"
                                                        name="education[0][document]" required
                                                        data-parsley-required-message="Please upload your education certificate.">
                                                </div>
                                                <div class="education-file-name mt-2 text-dark fw-bold"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Education Level/Type <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control education_level"
                                                name="education[0][education_level]" placeholder="Education Level/Type"
                                                required data-parsley-required-message="Education level/type is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid education level/type.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Institution Name <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control institutio_name"
                                                name="education[0][institutio_name]" placeholder="Institution Name"
                                                required data-parsley-required-message="Institution name is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid institution name.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Field of Study / Major <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control field_study"
                                                name="education[0][field_study]" placeholder="Field of Study / Major"
                                                required data-parsley-required-message="Field of study/major is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid field of study/major.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">DEGREE/CERTIFICATE EARNED <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control degree_earned"
                                                name="education[0][degree_earned]" placeholder="Degree/Certificate Earned"
                                                required
                                                data-parsley-required-message="Degree/Certificate earned is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid degree/certificate.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Attendance Period <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control attendance_period"
                                                name="education[0][attendance_period]" placeholder="e.g., 2015 - 2019"
                                                required data-parsley-required-message="Attendance period is required."
                                                data-parsley-pattern="^(\d{4})\s*-\s*(\d{4})$"
                                                data-parsley-pattern-message="Please enter attendance period in format: 2015 - 2019">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Location <span class="req_span">*</span></label>
                                            <input type="text" class="form-control location"
                                                name="education[0][location]" placeholder="Location" required
                                                data-parsley-required-message="Location is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid location.">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer ">
                        <a href="javascript:void(0);" class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        <a href="javascript:void(0);"
                            class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Previous</a>
                    </fieldset>

                    <fieldset data-setp="4">
                        <div class="mt-2">
                            <div class="employeeProCreationProcessExp-main">
                                <div class="employeeProCreationProcessExp-block">
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                        <div class="col-12">
                                            <label class="form-label">DOCUMENT UPLOAD / CERTIFICATE ATTACHMENT <span
                                                    class="req_span">*</span></label>
                                            <div class="uploadFile-block">
                                                <div class="uploadFile-btn">
                                                    <a href="javascript:void(0);"
                                                        class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                                    <input type="file" class="uploadFile"
                                                        name="experience[0][document]" required
                                                        data-parsley-required-message="Please upload your experience certificate.">
                                                </div>
                                                <div class="certificate-file-name mt-2 text-center text-dark fw-bold">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="company_name" class="form-label">Company Name <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control company_name"
                                                name="experience[0][company_name]" placeholder="Company Name" required
                                                data-parsley-required-message="Company name is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid company name.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="job_title" class="form-label">Job Title / Position <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control job_title"
                                                name="experience[0][job_title]" placeholder="Job Title / Position"
                                                required data-parsley-required-message="Job title is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid job title.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="employment_type" class="form-label">Employment Type <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control employment_type"
                                                name="experience[0][employment_type]" placeholder="Employment Type"
                                                required data-parsley-required-message="Employment type is required."
                                                data-parsley-pattern="^[a-zA-Z\s]+$"
                                                data-parsley-pattern-message="Please enter a valid employment type.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="duration_employment" class="form-label">Duration of Employment
                                                <span class="req_span">*</span></label>
                                            <input type="text" class="form-control duration_employment"
                                                name="experience[0][duration]"
                                                placeholder="Duration ex: 01/2015 - 01/2019" required
                                                data-parsley-required-message="Duration of employment is required."
                                                data-parsley-pattern="^(\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{4})$"
                                                data-parsley-pattern-message="Please enter duration in format: MM/YYYY - MM/YYYY">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="location1" class="form-label">Location <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control location1"
                                                name="experience[0][location]" placeholder="Location" required
                                                data-parsley-required-message="Location is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid location.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="reason_leaving" class="form-label">Reason for Leaving <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control reason_leaving"
                                                name="experience[0][reason_for_leaving]"
                                                placeholder="Reason for Leaving" required
                                                data-parsley-required-message="Reason for leaving is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid reason.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="reference_name" class="form-label">Reference Name <span
                                                    class="req_span">*</span></label>
                                            <input type="text" class="form-control reference_name"
                                                name="experience[0][reference_name]" placeholder="Reference Name"
                                                required data-parsley-required-message="Reference name is required."
                                                data-parsley-pattern="^[a-zA-Z\s\.]+$"
                                                data-parsley-pattern-message="Please enter a valid name.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="reference_contact" class="form-label">Reference Contact <span
                                                    class="req_span">*</span></label>
                                            {{-- Same loosened phone-number rule as the
                                                 Personal / Emergency / Step-2 mobile inputs:
                                                 optional `+`, digits + separators, 7–15
                                                 digits total. Lets HR enter international
                                                 reference numbers like "+1 833 747-9077". --}}
                                            <input type="tel" class="form-control reference_contact"
                                                name="experience[0][reference_contact]" placeholder="e.g. +1 833 747-9077"
                                                inputmode="tel" autocomplete="tel" maxlength="25"
                                                required
                                                data-parsley-required-message="Reference contact is required."
                                                data-parsley-mobile_number>
                                            <small class="form-text text-muted">Include country code, e.g. +91 9098765432</small>
                                        </div>
                                        <div class="col-12">
                                            <a href="javascript:void(0);"
                                                class="btn btn-themeSkyblue btn-sm blockAdd-btn">Add More</a>
                                            <a href="javascript:void(0);" class="btn btn-danger btn-sm remove-btn"
                                                style="display:none;">Remove</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <hr class="hr-footer ">
                        <button type="submit" class="btn btn-themeBlue btn-sm float-end " style="margin-right: 10px;"
                            id="submit">Submit</button>
                        <a href="javascript:void(0);"
                            class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Previous</a>
                    </fieldset>
                </form>

            </div>

        </div>
    </div>


    <div id="uploadimageModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Crop Image</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div id="profile_picture_preview"></div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary crop-picture custom-btn"
                        data-dismiss="modal">Crop</button>
                    <!-- <button type="button" class="btn btn-default" id="closemodalpass" data-dismiss="modal">Close</button> -->
                    <button type="button" data-bs-dismiss="modal" class="btn btn-default">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="uploadimageModal_fullImg" class="modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Crop Image</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div id="profile_picture_preview_full_img"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary crop_picture_full_img custom-btn"
                        data-dismiss="modal">Crop</button>
                    <button type="button" data-bs-dismiss="modal" class="btn btn-default">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
    <style>
        .invalid-feedback {
            order: 2;
        }

        .select2-container {
            order: 1;
        }

        .emp_createion_sel {
            display: flex;
            flex-direction: column;
        }

        /* Vacancy-locked dropdowns on Step 2 — softer than `disabled` grey
           so the field still reads as filled, with a small lock icon hint. */
        .vacancy-locked,
        .select2-container--default.select2-container--disabled .select2-selection.vacancy-locked-widget {
            background-color: #eef5ff !important;
            border-color: #b3d4ff !important;
            cursor: not-allowed;
        }
        #vacancyPickerTable tbody tr.table-active {
            background-color: #eef5ff !important;
        }
        #vacancyPickerTable tbody tr:hover {
            background-color: #f7fbff;
        }
    </style>
@endsection

@section('import-scripts')

    {{-- old --}}
    <script type="text/javascript">
        // ----------------------------------------------------------------
        // Mutually-required ID docs + conditional Passport Expiry.
        // Rules:
        //   • At least one of {Passport Number, NID} must be entered.
        //   • Entering one removes the * (and `required`) from the other.
        //   • Passport Expiry Date is required ONLY when Passport Number
        //     is non-empty. Hides the * + clears `required` otherwise.
        // ----------------------------------------------------------------
        function syncIdDocRequirements() {
            var $pass = $('#passport_numb');
            var $nid  = $('#nid');
            var $expiry = $('#passport_expiry_date');

            var hasPass = ($pass.val() || '').trim() !== '';
            var hasNid  = ($nid.val()  || '').trim() !== '';

            // Passport ↔ NID at-least-one logic. When neither is filled,
            // both stay required so the user must enter one. Once either
            // is filled the other's required state is lifted.
            if (hasNid && !hasPass) {
                $pass.removeAttr('required');
                $('.passport-required-marker').addClass('d-none');
            } else {
                $pass.attr('required', 'required');
                $('.passport-required-marker').removeClass('d-none');
            }

            if (hasPass && !hasNid) {
                $nid.removeAttr('required');
                $('.nid-required-marker').addClass('d-none');
            } else {
                $nid.attr('required', 'required');
                $('.nid-required-marker').removeClass('d-none');
            }

            // Passport Expiry follows Passport Number.
            if (hasPass) {
                $expiry.attr('required', 'required');
                $('.passport-expiry-required-marker').removeClass('d-none');
            } else {
                $expiry.removeAttr('required');
                $('.passport-expiry-required-marker').addClass('d-none');
                // Clear Parsley's red error state if it was previously
                // flagged when there was no passport number anyway.
                if ($.fn.parsley && $expiry.parsley) {
                    try { $expiry.parsley().reset(); } catch (e) {}
                }
            }
        }

        // ----------------------------------------------------------------
        // "Same as Present Address" — copies the Present Address block
        // into the Permanent Address block when checked. Unchecking the
        // box clears the permanent fields so users don't end up with
        // accidentally-saved duplicate text.
        //
        // Field map: each Present field's id starts with `pres_` and the
        // corresponding Permanent field with `permanent_` — we mirror
        // them 1:1.
        // ----------------------------------------------------------------
        // Field map keyed by `name` attribute (the markup uses `present_*`
        // for Present and a mix of `permanent_addLine1` / `parmanent_*`
        // (legacy typo) for Permanent — `name=` is the only reliable
        // selector for both blocks.
        var PERMANENT_FIELD_MAP = [
            ['present_addLine1',     'permanent_addLine1'],
            ['present_addLine2',     'parmanent_addline2'],
            ['present_city',         'parmanent_city'],
            ['present_state',        'parmanent_state'],
            ['present_postal_code',  'parmanent_postal_code'],
            ['present_country',      'parmanent_country'],
        ];
        function copyPresentToPermanent() {
            PERMANENT_FIELD_MAP.forEach(function (pair) {
                var src = $('[name="' + pair[0] + '"]');
                var dst = $('[name="' + pair[1] + '"]');
                if (!src.length || !dst.length) return;
                var val = src.val();
                if (dst.is('select')) {
                    // Select2-wrapped selects need both `change` (so Parsley
                    // sees the new value and clears any "required" error)
                    // AND `change.select2` (so the visible label updates
                    // even when triggering via .val()). The country select
                    // shares the same option list as the Present-country
                    // select so the value is guaranteed to exist.
                    dst.val(val).trigger('change').trigger('change.select2');
                } else {
                    dst.val(val).trigger('input').trigger('change');
                }
                // Drop any stale Parsley "required" red state on the dest
                // — the copy just satisfied it but Parsley won't re-check
                // until the user types into the field otherwise.
                if ($.fn.parsley && dst.parsley) {
                    try { dst.parsley().reset(); } catch (e) {}
                }
            });
        }
        function clearPermanentAddress() {
            PERMANENT_FIELD_MAP.forEach(function (pair) {
                var dst = $('[name="' + pair[1] + '"]');
                if (!dst.length) return;
                if (dst.is('select')) {
                    dst.val('').trigger('change').trigger('change.select2');
                } else {
                    dst.val('').trigger('input').trigger('change');
                }
                if ($.fn.parsley && dst.parsley) {
                    try { dst.parsley().reset(); } catch (e) {}
                }
            });
        }

        $(document).ready(function() {
            initSelect2AndValidation();
            initParsleyValidation();
            initDatePicker();

            $('#date_birth').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true, // Close the picker after selection
                todayHighlight: true // Highlight today's date
            });

            // Bind the Passport / NID / Expiry conditional-require logic.
            $('#passport_numb, #nid').on('input change', syncIdDocRequirements);
            syncIdDocRequirements();

            // "Same as Present Address" toggle.
            //
            // Direction is one-way: PRESENT → PERMANENT. If the user
            // checks the toggle while PRESENT is empty (e.g. they filled
            // PERMANENT first under the old layout where it sat on top),
            // the naive copy would wipe their PERMANENT entries because
            // the source is blank. Guard against that: if PRESENT has no
            // required values, undo the toggle and show a toast pointing
            // them at the right block.
            $(document).on('change', '#sameAsPresentAddress', function () {
                var $cb = $(this);
                if ($cb.is(':checked')) {
                    var requiredPresentKeys = [
                        'present_addLine1', 'present_city',
                        'present_state', 'present_postal_code', 'present_country'
                    ];
                    var presentEmpty = requiredPresentKeys.every(function (k) {
                        var v = $('[name="' + k + '"]').val();
                        return !v || String(v).trim() === '';
                    });
                    if (presentEmpty) {
                        $cb.prop('checked', false);
                        if (typeof toastr !== 'undefined') {
                            toastr.warning(
                                'Fill in the Present Address first — then tick this to copy it into Permanent.',
                                'Present address is empty',
                                { positionClass: 'toast-bottom-right' }
                            );
                        }
                        return;
                    }
                    copyPresentToPermanent();
                } else {
                    clearPermanentAddress();
                }
            });

            // ----------------------------------------------------------------
            // "Hire against a vacancy" picker (collapsible panel above Step 1).
            // Picking a row stores the position/department/division ids in the
            // hidden inputs and pre-fills + LOCKS the matching Step 2 dropdowns
            // so HR can't accidentally drift off the chosen vacancy.
            // "Clear selection" unlocks them and lets HR fall back to a manual
            // pick. The whole panel is optional — skipping it leaves Step 2
            // exactly as it always was.
            // ----------------------------------------------------------------
            // Inject a single <option> for the chosen id/label if it isn't
            // already present, select it, and disable the dropdown. Crucially
            // we do NOT trigger `change` — that would fire the Division →
            // Department → Section → Position AJAX cascade and wipe sibling
            // values mid-prefill (this was the original "department isn't
            // getting auto-filled" bug). Select2 needs an `option` element to
            // exist before .val() will display the label.
            function lockStep2Field($field, valueId, valueLabel) {
                if (!$field.length || !valueId) return;
                valueId = String(valueId);
                if ($field.find('option[value="' + valueId + '"]').length === 0) {
                    $field.append(new Option(valueLabel || valueId, valueId, true, true));
                }
                $field.val(valueId);
                // Re-render the Select2 widget so the new option shows
                // without forcing a `change` event.
                if ($field.data('select2')) {
                    try { $field.trigger('change.select2'); } catch (e) {}
                }
                $field.prop('disabled', true).addClass('vacancy-locked');
                if ($field.data('select2')) {
                    try { $field.select2({ disabled: true }); } catch (e) {}
                }
                $field.attr('data-vacancy-locked-value', valueId);
                $field.attr('data-vacancy-locked-label', valueLabel || '');
            }
            function unlockStep2Field($field) {
                if (!$field.length) return;
                $field.prop('disabled', false).removeClass('vacancy-locked');
                if ($field.data('select2')) {
                    try { $field.select2({ disabled: false }); } catch (e) {}
                }
                $field.removeAttr('data-vacancy-locked-value');
                $field.removeAttr('data-vacancy-locked-label');
            }

            function applyVacancyToStep2($row) {
                var positionId    = $row.data('position-id');
                var positionTitle = $row.data('position-title');
                var deptId        = $row.data('department-id');
                var deptName      = $row.data('department-name');
                var divisionId    = $row.data('division-id');
                var divisionName  = $row.data('division-name');

                // Hidden inputs (these go to the server with the form).
                $('#selected_vacancy_id').val($row.data('vacancy-id'));
                $('#selected_vacancy_position_id').val(positionId || '');
                $('#selected_vacancy_department_id').val(deptId || '');
                $('#selected_vacancy_division_id').val(divisionId || '');

                // Inject + lock all three Step-2 fields. Order doesn't matter
                // here because lockStep2Field doesn't fire the cascade —
                // each field is independently set with its known option.
                if (divisionId) lockStep2Field($('#division'),   divisionId, divisionName);
                if (deptId)     lockStep2Field($('#department'), deptId,     deptName);
                if (positionId) lockStep2Field($('#position'),   positionId, positionTitle);

                // Reporting Person + Section dropdowns are normally filled
                // by the existing #department.on('change') cascade. We
                // deliberately suppress that cascade during the vacancy
                // lock (to keep sibling values intact), so call both
                // endpoints directly here. Helpers were defined inside a
                // closure and not reachable from this scope — inline the
                // AJAX so this code is self-contained.
                if (deptId) {
                    // --- Reporting Person ---
                    $.ajax({
                        url: '{{ route('people.getReportingPerson') }}',
                        type: 'GET',
                        data: { department_id: deptId },
                        success: function (res) {
                            var $rp = $('#reporting_person');
                            var html = '<option></option>';
                            $.each((res && res.data) || [], function (_, person) {
                                var name = '';
                                if (person.first_name || person.last_name) {
                                    name = ((person.first_name || '') + ' ' + (person.last_name || '')).trim();
                                } else if (person.name) {
                                    name = person.name;
                                }
                                html += '<option value="' + person.id + '">' + name + '</option>';
                            });
                            $rp.html(html);
                            // Re-render select2 without firing change so
                            // we don't accidentally clear sibling fields.
                            if ($rp.data('select2')) {
                                try { $rp.trigger('change.select2'); } catch (e) {}
                            }
                        }
                    });

                    // --- Section ---
                    // Section stays user-editable (not locked) — the
                    // vacancy doesn't have a section, just a department.
                    // Enable only if the dept actually has sections;
                    // otherwise keep it disabled like the manual cascade.
                    $.ajax({
                        url: '{{ route('people.getSectionByDepartment') }}',
                        type: 'GET',
                        data: { department_id: deptId },
                        success: function (res) {
                            var $sec = $('#section');
                            var sections = (res && res.sections) || [];
                            var html = '<option></option>';
                            $.each(sections, function (_, section) {
                                html += '<option value="' + section.id + '">' + section.name + '</option>';
                            });
                            $sec.html(html);
                            if ($sec.data('select2')) {
                                try { $sec.trigger('change.select2'); } catch (e) {}
                            }
                            // Disable when the dept has no sections; never
                            // override a vacancy-locked Section (the vacancy
                            // owns it). Mirrors the manual-cascade behaviour.
                            var sectionDisabled = sections.length === 0;
                            if (!$sec.hasClass('vacancy-locked')) {
                                $sec.prop('disabled', sectionDisabled);
                                if ($sec.data('select2')) {
                                    try { $sec.select2({ disabled: sectionDisabled }); } catch (e) {}
                                }
                            }
                        }
                    });
                }

                // ---- Joining Date floor = GM approval date of the vacancy.
                // An employee can't join before HR was even cleared to hire
                // against the position. We:
                //   • set a min date on the datepicker (so earlier dates
                //     aren't selectable),
                //   • add a Parsley `data-parsley-mindate` so paste/manual
                //     entry is still validated,
                //   • surface a small inline notice with the floor date.
                var gmIso   = $row.data('gm-approved-iso');   // YYYY-MM-DD
                var gmLabel = $row.data('gm-approved-label'); // d M Y
                if (gmIso) {
                    var parts = String(gmIso).split('-'); // [yyyy, mm, dd]
                    var dpMin = parts[2] + '/' + parts[1] + '/' + parts[0];
                    var $jd = $('#joining_date');
                    $jd.attr('data-min-iso', gmIso);
                    $jd.attr('data-parsley-mindate', gmIso);
                    $jd.attr('data-parsley-mindate-message',
                        'Joining date cannot be before the vacancy was approved by GM (' + gmLabel + ').');
                    // Re-init the datepicker with the new lower bound.
                    try {
                        $jd.datepicker('remove');
                        $jd.datepicker({
                            format: 'dd/mm/yyyy',
                            autoclose: true,
                            todayHighlight: true,
                            startDate: dpMin
                        });
                    } catch (e) {}
                    // Inline notice next to the field.
                    if ($('#joining_date_gm_hint').length === 0) {
                        $jd.after('<small id="joining_date_gm_hint" class="form-text text-info">' +
                            '<i class="fa-solid fa-circle-info me-1"></i>' +
                            'Must be on or after the vacancy\'s GM approval date (' + gmLabel + ').' +
                            '</small>');
                    } else {
                        $('#joining_date_gm_hint').html(
                            '<i class="fa-solid fa-circle-info me-1"></i>' +
                            'Must be on or after the vacancy\'s GM approval date (' + gmLabel + ').'
                        );
                    }
                    // If a date was already typed and is earlier than the
                    // floor, clear it so the user has to pick a valid one.
                    var existing = $jd.val();
                    if (existing && /^\d{2}\/\d{2}\/\d{4}$/.test(existing)) {
                        var existingParts = existing.split('/');
                        var existingIso = existingParts[2] + '-' + existingParts[1] + '-' + existingParts[0];
                        if (existingIso < gmIso) {
                            $jd.val('');
                        }
                    }
                }

                // Benefit Grid Level is derived from the position's rank.
                // The page already does this on #position change → calls
                // getBenefitGridByPosition, populates the dropdown + the
                // entitle switches + #position_rank. We can't rely on the
                // change event firing (we intentionally suppress it during
                // lock to avoid the AJAX cascade wiping other fields), so
                // call the same endpoint directly and re-apply the result.
                if (positionId) {
                    $.ajax({
                        url: '{{ route('people.getBenefitGridByPosition') }}',
                        type: 'GET',
                        data: { position_id: positionId },
                        success: function (res) {
                            if (!res) return;
                            lockStep2Field(
                                $('#benefit_grid_level'),
                                res.benfitGrid_emp_id,
                                res.emp_grade_name
                            );
                            $('#position_rank').val(res.position_rank || '');
                            $('#entitle_service_charge').prop('checked', res.service === 'yes');
                            $('#entitle_public_holiday').prop('checked', res.holiday_overtime === 'yes');
                            $('#entitle_overtime').prop('checked', res.overtime === 'yes');
                        }
                    });
                }

                // Visual: highlight the picked row + show summary alert.
                $('#vacancyPickerTable tbody tr').removeClass('table-active');
                $row.addClass('table-active');
                var summaryHtml = (positionTitle || '—')
                    + ' · ' + (deptName || '—')
                    + (divisionName ? ' · ' + divisionName : '');
                $('#vacancyPickerSummaryText').text(summaryHtml);
                $('#vacancyPickerSummary').removeClass('d-none');
            }

            function clearVacancySelection() {
                $('#selected_vacancy_id, #selected_vacancy_position_id, #selected_vacancy_department_id, #selected_vacancy_division_id').val('');
                $('#vacancyPickerTable input[name="vacancy_pick"]').prop('checked', false);
                $('#vacancyPickerTable tbody tr').removeClass('table-active');
                $('#vacancyPickerSummary').addClass('d-none');
                unlockStep2Field($('#department'));
                unlockStep2Field($('#position'));
                unlockStep2Field($('#division'));
                unlockStep2Field($('#benefit_grid_level'));
                // Restore the Joining Date to its default unbounded state.
                var $jd = $('#joining_date');
                $jd.removeAttr('data-min-iso')
                   .removeAttr('data-parsley-mindate')
                   .removeAttr('data-parsley-mindate-message');
                try {
                    $jd.datepicker('remove');
                    $jd.datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true });
                } catch (e) {}
                $('#joining_date_gm_hint').remove();
            }

            // Row click → select the radio + apply.
            $(document).on('click', '.vacancy-row', function (e) {
                if ($(e.target).is('button, a')) return;
                var $row = $(this);
                $row.find('input[name="vacancy_pick"]').prop('checked', true);
                applyVacancyToStep2($row);
            });
            // Radio change → apply (covers keyboard navigation).
            $(document).on('change', 'input[name="vacancy_pick"]', function () {
                var $row = $(this).closest('.vacancy-row');
                if ($row.length) applyVacancyToStep2($row);
            });
            // Clear button → unlock + reset.
            $(document).on('click', '#vacancyPickerClear', function () {
                clearVacancySelection();
            });
            // Chevron rotation when the collapse opens/closes.
            $(document).on('shown.bs.collapse', '#vacancyPickerCollapse', function () {
                $('#vacancyPickerChevron').addClass('fa-chevron-up').removeClass('fa-chevron-down');
            });
            $(document).on('hidden.bs.collapse', '#vacancyPickerCollapse', function () {
                $('#vacancyPickerChevron').addClass('fa-chevron-down').removeClass('fa-chevron-up');
            });
            // Re-mirror live while the toggle is on so edits to Present
            // propagate to Permanent without un-checking + re-checking.
            $(document).on('input change',
                '[name="present_addLine1"], [name="present_addLine2"], [name="present_city"], ' +
                '[name="present_state"], [name="present_postal_code"], [name="present_country"]',
                function () {
                    if ($('#sameAsPresentAddress').is(':checked')) {
                        copyPresentToPermanent();
                    }
                });
        });

        function initDatePicker() {
            if ($.fn.datepicker) {
                $('#txt-bod').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,
                    endDate: '-18y',
                    startDate: '-65y'
                }).on('changeDate', function() {
                    $(this).parsley().validate();
                });

                $('#passport_expiry_date').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,
                }).on('changeDate', function() {
                    $(this).parsley().validate();
                });

                $('.datepicker').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true
                }).on('changeDate', function() {
                    $(this).parsley().validate(); // Trigger validation on date change
                });
            }
        }

        function initSelect2AndValidation() {
            if ($.fn.select2 && $.fn.parsley) {
                // Initialize Select2
                $(".select2t-none").select2();

                // Add Parsley validation specifically for Select2
                $(".select2t-none").on('change', function() {
                    $(this).parsley().validate();
                });

                // Ensure Select2 trigger changes in Parsley
                $(".select2t-none").on('select2:select', function() {
                    $(this).trigger('change');
                });
            }
        }

        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#msform').parsley({
                    errorClass: 'is-invalid',
                    successClass: 'is-valid',
                    errorsWrapper: '<div class="invalid-feedback"></div>',
                    errorTemplate: '<div></div>',
                    trigger: 'change'
                });

                // Custom Parsley validators

                // mindate — used on Joining Date when a vacancy is picked.
                // requirement is an ISO date (YYYY-MM-DD); value is whatever
                // the datepicker stores (DD/MM/YYYY). Returns true if the
                // value's ISO form is >= requirement.
                window.Parsley.addValidator('mindate', {
                    requirementType: 'string',
                    validateString: function (value, requirement) {
                        if (!value || !requirement) return true;
                        var m = String(value).match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                        if (!m) return true; // pattern validator handles format errors
                        var iso = m[3] + '-' + m[2] + '-' + m[1];
                        return iso >= requirement;
                    },
                    messages: {
                        en: 'Date is earlier than the allowed minimum.'
                    }
                });

                // Mobile number — the old rule required EXACTLY 10 digits
                // and rejected anything else (incl. valid country-prefixed
                // numbers of other lengths). Loosen to ITU-T E.164: optional
                // `+`, then 7–15 digits after stripping common separators
                // (spaces, dashes, brackets). This matches the same rule
                // we use on the Employee Detail page.
                window.Parsley.addValidator('mobile_number', {
                    validateString: function(value) {
                        var stripped = String(value || '').replace(/[\s()\-]/g, '').replace(/^\+/, '');
                        return /^[0-9]{7,15}$/.test(stripped);
                    },
                    messages: {
                        en: 'Please enter a valid mobile number (7–15 digits, optionally prefixed with +country code).'
                    }
                });

                window.Parsley.addValidator('passport_no', {
                    validateString: function(value) {
                        return /^[A-Z]{1,2}[0-9]{6,9}$/.test(value);
                    },
                    messages: {
                        en: 'Please enter a valid passport number. It should start with 1-2 uppercase letters followed by 6-9 digits.'
                    }
                });
                window.Parsley.addValidator('pin_code', {
                    validateString: function(value) {
                        // Assumes 6-digit PIN code (modify for specific country requirements)
                        return /^\d{6}$/.test(value);
                    },
                    messages: {
                        en: 'Please enter a valid 6-digit PIN code.'
                    }
                });

                window.Parsley.addValidator('validateScript', {
                    validateString: function(value) {
                        // Pattern to match any <script> tags, even with attributes or content
                        const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                        return !scriptTagPattern.test(
                        value); // Return true if no script tags are found, false otherwise
                    },
                    messages: {
                        en: 'Script tags are not allowed.'
                    }
                });

                // Add a custom validator for email validation in Parsley
                window.Parsley.addValidator('customEmail', {
                    validateString: function(value) {
                        var emailRegex = /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        var disallowedEmailRegex = /(.)\1{2,}|(\+.*?\+)|(\.{2,})|(-{2,})|(@-|-\@)|(@\.)|(\.@)/;

                        if (!emailRegex.test(value) || disallowedEmailRegex.test(value)) {
                            return false;
                        }

                        var domain = value.split('@')[1];

                        if (domain.includes('..') || domain.match(/\.\w+\.\w+$/)) {
                            return false;
                        }

                        var validTLDs = ['com', 'org', 'net', 'co', 'in', 'uk', 'info'];
                        var domainParts = domain.split('.').reverse();

                        if (!validTLDs.includes(domainParts[0]) || (domainParts[0] === 'co' && !validTLDs
                                .includes(domainParts[1]))) {
                            return false;
                        }

                        return true;
                    },
                    messages: {
                        en: 'Invalid email address'
                    }
                });

                window.Parsley.addValidator('endgreaterthanstart', {
                    validateString: function(endDateValue, startDateSelector) {
                        const startDateStr = $(startDateSelector).val();
                        const endDate = moment(endDateValue, 'DD/MM/YYYY', true); // Parse end date
                        const startDate = moment(startDateStr, 'DD/MM/YYYY', true); // Parse start date

                        // Check if both dates are valid
                        if (!startDate.isValid() || !endDate.isValid()) {
                            return true; // Skip validation if any date is invalid or missing
                        }

                        // Check that the end date is strictly after the start date
                        return endDate.isAfter(startDate, 'day'); // Ensure day-level comparison
                    },
                    messages: {
                        en: 'End Date must be greater than Start Date.'
                    }
                });

                // Manage the "Currently Working Here" and "End Date" logic
                $('.currently-working-checkbox').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#txt-end-date').prop('disabled', true).val('').parsley().reset();
                    } else {
                        $('#txt-end-date').prop('disabled', false);
                    }
                    calculateExperience(); // Recalculate experience
                });

            }
        }


        $(document).ready(function() {
            // ------------------------------------------------------------
            // Currency-symbol sync. Basic Salary + Pension share the
            // same currency (Pension is 7% of Basic for Maldivian
            // employees, so they live in the same denomination). The
            // Currency Type dropdown drives both prefixes — MVR → "MVR",
            // USD → "$". One place to change if we ever add EUR/etc.
            // ------------------------------------------------------------
            var CURRENCY_SYMBOLS = {
                'MVR':    'MVR',
                'USD':    '$',
                'Dollar': '$'
            };
            function updateSalaryCurrencySymbol() {
                var c = $('#currency_type').val() || 'MVR';
                var label = CURRENCY_SYMBOLS[c] || c;
                $('.salary-currency-symbol').text(label);
            }
            $('#currency_type').on('change', updateSalaryCurrencySymbol);
            updateSalaryCurrencySymbol();

            // ------------------------------------------------------------
            // Pension auto-calc + required-flag for Maldivian. Was
            // bound to `keyup` only — broke when HR pasted the salary
            // or changed it via the spinner. `input` covers both. The
            // pension is 7% of Basic Salary in the SAME currency as the
            // Basic Salary; changing Currency Type doesn't shift the
            // ratio, so no extra hook needed there.
            // ------------------------------------------------------------
            function syncPensionFromBasic() {
                var nationality = $('#nationality').val();
                var $pensionField = $('#pension');
                if (nationality === 'Maldivian') {
                    $pensionField.attr('data-parsley-required', 'true');
                    $pensionField.attr('data-parsley-required-message',
                        'Pension is required for Maldivian employees.');
                    var basicSalary = parseFloat($('#basic_salary').val()) || 0;
                    $pensionField.val((basicSalary * 0.07).toFixed(2));
                } else {
                    $pensionField.attr('data-parsley-required', 'false');
                }
                try { $pensionField.parsley().validate(); } catch (e) {}
            }
            $('#basic_salary').on('input', syncPensionFromBasic);
            $('#nationality').on('change', syncPensionFromBasic);
        });
    </script>

    {{-- New --}}
    <script>
        // ─── AI CV auto-fill (Add Employee) ────────────────────────────
        //
        // Same proxy + field-map pattern as the applicant form / offline
        // interview. HR uploads the candidate's CV → AI parses it → form
        // fields get pre-populated by `name=`. The values are still
        // editable; this just skips retyping content already on the CV.
        function aiAutofillFromCv(file) {
            if (!file) return;
            var fd = new FormData();
            fd.append('cv', file);
            fd.append('_token', $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}');

            var $banner = $('#ai-cv-banner');
            if ($banner.length === 0) {
                $banner = $('<div id="ai-cv-banner" class="alert alert-info py-1 px-2 my-2" style="font-size:13px;">&nbsp;</div>');
                $('#cv-file-name').after($banner);
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

        $(document).ready(function() {
            $('#fileInput').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    $('#cv-file-name').text(file.name);
                    // Kick off the AI parse alongside the existing file
                    // name display — adds zero perceived latency.
                    aiAutofillFromCv(file);
                } else {
                    $('#cv-file-name').text('');
                }
                if (!file) return;
                var formData = new FormData();
                formData.append('document', file);
                formData.append('doc_type', 'cv');

                // Show loading spinner
                let $progressBar = $(`
                    <div id="cv-upload-progress" style="display:inline-block; width:150px; margin-left:10px; vertical-align:middle;">
                        <div class="progress" style="height: 1.5rem; background-color: #e0f2fe;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-themeBlue" 
                                role="progressbar" style="width: 60%; background: linear-gradient(90deg, #1e90ff 60%, #38b6ff 100%);" 
                                aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                                Uploading...
                            </div>
                        </div>
                    </div>
                `);
                $(this).after($progressBar);

                $.ajax({
                    url: '{{ route('people.employees.extract-details') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#cv-upload-progress').remove();
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                        // Remove spinner
                        // Autofill fields if data is present
                        if (response.data) {
                            if (response.data.extracted_fields.first_name) $('#employeeF_name')
                                .val(response.data.extracted_fields.first_name);
                            if (response.data.extracted_fields.last_name) $('#employeeL_name')
                                .val(response.data.extracted_fields.last_name);
                            if (response.data.extracted_fields.middle_name) $('#employeeM_name')
                                .val(response.data.extracted_fields
                                .middle_name); // if you have a middle name field
                            if (response.data.extracted_fields.email) $('#email_address').val(
                                response.data.extracted_fields.email);
                            if (response.data.extracted_fields.phone_no) $('#mobile_num').val(
                                response.data.extracted_fields.phone_no);
                            if (response.data.extracted_fields.dob) {
                                const rawDob = response.data.extracted_fields.dob;

                                // Try to parse with multiple formats
                                const parsed = moment(rawDob, [
                                    "Do MMMM YYYY", // 10th December 1987
                                    "DD.MM.YYYY", // 10.02.2020
                                    "DD/MM/YYYY", // 10/02/2000
                                    "DD MM YYYY", // 10 06 2001
                                    "YYYY-MM-DD", // fallback ISO
                                ], true); // strict parsing

                                if (parsed.isValid()) {
                                    const formatted = parsed.format("DD/MM/YYYY");
                                    $('#date_birth').val(formatted);
                                } else {
                                    console.warn("Invalid DOB format:", rawDob);
                                    $('#date_birth').val(''); // or show error
                                }
                            }
                            if (response.data.extracted_fields.gender) {
                                // Normalize gender value to lowercase and trim spaces
                                let genderVal = response.data.extracted_fields.gender
                                    .toLowerCase().trim();
                                // Accept also "m", "f", "o" as shortcuts
                                if (genderVal === 'm') genderVal = 'male';
                                if (genderVal === 'f') genderVal = 'female';
                                if (genderVal === 'o') genderVal = 'other';
                                $('#gender').val(genderVal).trigger('change');
                            }
                            if (response.data.extracted_fields.marital_status) {
                                // Normalize marital status value to lowercase and trim spaces
                                let maritalStatusVal = response.data.extracted_fields
                                    .marital_status.toLowerCase().trim();
                                // Accept also "single" as "unmarried"
                                if (maritalStatusVal === 'single') maritalStatusVal =
                                    'unmarried';
                                $('#marital_status').val(maritalStatusVal).trigger('change');
                            }

                            if (response.data.extracted_fields.nationality) $('#nationality')
                                .val(response.data.extracted_fields.nationality);

                            if (response.data.extracted_fields['blood group']) {
                                let bloodGroupVal = response.data.extracted_fields[
                                    'blood group'].toUpperCase().replace(/\s/g, '');
                                $('#blood_group').val(bloodGroupVal).trigger('change');
                            }
                            if (response.data.extracted_fields['passport number']) $(
                                '#passport_numb').val(response.data.extracted_fields[
                                'passport number']);
                            if (response.data.extracted_fields['passport_expiry_date']) $(
                                '#passport_expiry_date').val(response.data.extracted_fields[
                                'passport_expiry_date']);
                            if (response.data.extracted_fields.nid) $('#nid').val(response.data
                                .extracted_fields.nid);
                            // Autofill Present Address fields if present in extracted data
                            if (response.data.extracted_fields.address) {
                                // Split address by comma and fill Address Line 1 and 2
                                const addressParts = response.data.extracted_fields.address
                                    .split(',');
                                // Find the PRESENT ADDRESS block by label
                                const presentAddressBlock = $("label[for='present_addLine1']")
                                    .filter(function() {
                                        return $(this).text().trim().toUpperCase() ===
                                            'PRESENT ADDRESS';
                                    }).closest('.row');
                                // Fill Address Line 1
                                presentAddressBlock.find("input[id='present_addLine1']").val(
                                    addressParts[0] ? addressParts[0].trim() : '');
                                // Fill Address Line 2 (the next input after Address Line 1)
                                presentAddressBlock.find("input[id='present_addLine1']")
                                .parent().next().find('input').val(addressParts[1] ?
                                    addressParts[1].trim() : '');
                                // Optionally fill City, State, Postal Code, Country if you have those fields in extracted_fields
                                if (response.data.extracted_fields.present_city) {
                                    presentAddressBlock.find("select[data-placeholder='City']")
                                        .val(response.data.extracted_fields.present_city);
                                }
                                if (response.data.extracted_fields.present_state) {
                                    presentAddressBlock.find(
                                        "select[data-placeholder='State/Province']").val(
                                        response.data.extracted_fields.present_state);
                                }
                                if (response.data.extracted_fields.present_postal_code) {
                                    presentAddressBlock.find("input[placeholder='Postal Code']")
                                        .val(response.data.extracted_fields
                                        .present_postal_code);
                                }
                                if (response.data.extracted_fields.present_country) {
                                    presentAddressBlock.find(
                                        "select[data-placeholder='Country']").val(response
                                        .data.extracted_fields.present_country);
                                }
                            }
                            if (response.data.extracted_fields.languages_known) $('#language')
                                .val(response.data.extracted_fields.languages_known);
                            if (response.data.extracted_fields['proficiency in languages']) $(
                                '#proficiency_level').val(response.data.extracted_fields[
                                'proficiency in languages']);
                            // Add more fields as needed
                        }
                    },
                    error: function(xhr) {
                        $('#cv-upload-progress').remove();
                        let msg = "An error occurred";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        toastr.error(msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
        });
    </script>


    <script>
        // Image preview initialization
        $image_crop_profile = $('#profile_picture_preview').croppie({
            enableOrientation: true,
            viewport: {
                width: 200,
                height: 200,
                type: 'circle' //square
            },
            boundary: {
                width: 300,
                height: 300
            }
        });

        // Full length photo initialization
        $image_crop = $('#profile_picture_preview_full_img').croppie({
            enableOrientation: true,
            viewport: {
                width: 200,
                height: 200,
                type: 'square'
            },
            boundary: {
                width: 300,
                height: 300
            }
        });

        // Passport photo change handler
        $('#profile_picture').on('change', function() {
            var reader = new FileReader();
            var file = this.files[0];
            if (file) {
                $('#profile-picture-file-name').text(file.name); // Display the file name
            } else {
                $('#profile-picture-file-name').text(''); // Clear the file name if no file is selected
            }
            reader.onload = function(event) {
                $image_crop_profile.croppie('bind', {
                    url: event.target.result
                });
            }
            reader.readAsDataURL(file);
            $('#uploadimageModal').modal('show');
        });

        // Full length photo change handler
        $('#full_length_photos').on('change', function() {
            var reader = new FileReader();
            var file = this.files[0];
            if (file) {
                $('#profile-full-length-photo-file-name').text(file.name); // Display the file name
            } else {
                $('#profile-full-length-photo-file-name').text(''); // Clear the file name if no file is selected
            }
            reader.onload = function(event) {
                $image_crop.croppie('bind', {
                    url: event.target.result
                });
            }
            reader.readAsDataURL(file);
            $('#uploadimageModal_fullImg').modal('show');
        });

        // Crop functionality
        $('.crop-picture').click(function() {
            $image_crop_profile.croppie('result', {
                type: 'canvas',
                size: 'viewport'
            }).then(function(response) {
                $('#profilePicturePreview').attr("src", response);
                $('#uploadimageModal').modal('hide');
            });
        });

        $('.crop_picture_full_img').click(function() {
            $image_crop.croppie('result', {
                type: 'canvas',
                size: 'viewport'
            }).then(function(response) {
                $('#profilePreviewfullimg').attr("src", response);
                $('#uploadimageModal_fullImg').modal('hide');
            });
        });



        $(document).ready(function() {
            function toggleRemoveButtons() {
                const $blocks = $('.employeeLanguageRepeater-block');
                $blocks.each(function(i, block) {
                    if (i === 0) {
                        $(block).find('.blockAdd-btn').show();
                        $(block).find('.remove-btn').hide();
                    } else {
                        $(block).find('.blockAdd-btn').hide();
                        $(block).find('.remove-btn').show();
                    }
                });
            }

            function updateSkillNames() {
                $('.employeeLanguageRepeater-block').each(function(i, block) {
                    $(block).find('.language-input').attr('name', `language[${i}][0]`);
                    $(block).find('.proficiency-level-select').attr('name', `language[${i}][1]`);
                });
            }

            // Add new block
            $(document).on('click', '.employeeLanguageRepeater-main .blockAdd-btn', function(e) {
                e.preventDefault();
                const $main = $('.employeeLanguageRepeater-main');
                const $firstBlock = $main.find('.employeeLanguageRepeater-block').first();

                $firstBlock.find('select.select2t-none').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                const $newBlock = $firstBlock.clone();
                $newBlock.find('.select2-container').remove();
                $newBlock.find('select.select2t-none').removeAttr('data-select2-id').removeAttr(
                    'aria-hidden').show();

                // Reset inputs
                $newBlock.find('input[type="text"]').val('');
                $newBlock.find('select').prop('selectedIndex', 0);

                $main.append($newBlock);

                updateSkillNames();
                toggleRemoveButtons();

                // Re-init select2
                $newBlock.find('select.select2t-none').select2({
                    minimumResultsForSearch: Infinity,
                    width: '100%'
                });
                $firstBlock.find('select.select2t-none').select2({
                    minimumResultsForSearch: Infinity,
                    width: '100%'
                });
            });

            // Remove block
            $(document).on('click', '.employeeLanguageRepeater-main .remove-btn', function(e) {
                e.preventDefault();
                $(this).closest('.employeeLanguageRepeater-block').remove();
                updateSkillNames();
                toggleRemoveButtons();
            });

            // Initialize select2
            $('.employeeLanguageRepeater-block select.select2t-none').select2({
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            updateSkillNames();
            toggleRemoveButtons();
        });
    </script>

    <script>
        $(document).ready(function() {
            $(".next").click(function(e) {
                e.preventDefault();
                let $currentFieldset = $(this).closest("fieldset"),
                    stepData = $currentFieldset.find("input, select, textarea").serialize();
                stepData += "&step=" + $currentFieldset.data("setp");

                // Validate current step (example with Parsley)
                let isValid = true;
                $currentFieldset.find("input, select, textarea").each(function() {
                    if ($(this).parsley().validate() !== true) {
                        isValid = false;
                    }
                });
                if (!isValid) return;

                // Save step data
                $.post("{{ route('save.employeeinfo.step') }}", stepData, function(response) {
                    if (response.success) {
                        let $nextFieldset = $currentFieldset.next("fieldset");
                        $currentFieldset.hide();
                        $("#progressbar li").eq($("fieldset").index($currentFieldset)).removeClass(
                            "current");
                        $("#progressbar li").eq($("fieldset").index($nextFieldset)).addClass(
                            "active current");
                        $nextFieldset.show();
                    } else {
                        alert(response.message || "Error saving step data.");
                    }
                }).fail(function() {
                    alert("Error saving step data.");
                });
            });

            $(".previous").click(function(e) {
                e.preventDefault();
                let $currentFieldset = $(this).closest("fieldset"),
                    $prevFieldset = $currentFieldset.prev("fieldset"),
                    prevStep = $prevFieldset.data("setp");

                // Retrieve stored data for previous step
                $.post("{{ route('get.employeeinfo.draft') }}", {
                    step: prevStep
                }, function(response) {
                    if (response.success) {
                        // Populate fields
                        $.each(response.data, function(key, value) {
                            let $field = $prevFieldset.find(`[name="${key}"]`);
                            if ($field.is(":checkbox,:radio")) {
                                if (Array.isArray(value)) {
                                    $field.prop("checked", false);
                                    value.forEach(val => {
                                        $prevFieldset.find(
                                                `[name="${key}"][value="${val}"]`)
                                            .prop("checked", true);
                                    });
                                } else {
                                    $field.filter(`[value="${value}"]`).prop("checked",
                                        true);
                                }
                            } else {
                                $field.val(value).trigger("change");
                            }
                        });
                    }
                    $("#progressbar li").eq($("fieldset").index($currentFieldset)).removeClass(
                        "current active");
                    $("#progressbar li").eq($("fieldset").index($prevFieldset)).addClass(
                        "active current");
                    $currentFieldset.hide();
                    $prevFieldset.show();
                }).fail(function() {
                    alert("Error retrieving draft data.");
                });
            });
        });

        $(document).ready(function() {
            $('#msform').on('submit', function(e) {
                e.preventDefault();
                initParsleyValidation();
                // initSelect2AndValidation();

                // Hard guard: every new hire must be tied to an open vacancy.
                // The picker on Step 1 hides fully-filled vacancies, but if
                // the user collapsed the panel and skipped picking, fail loud
                // here so we don't post an empty vacancy_id to the server.
                if (!$('#selected_vacancy_id').val()) {
                    toastr.error(
                        'You must hire against an open vacancy. Pick one from the "Hire against a vacancy" panel at the top of Step 1.',
                        'Vacancy required',
                        { positionClass: 'toast-bottom-right' }
                    );
                    $('#vacancyPickerCollapse').collapse('show');
                    $('html, body').animate({ scrollTop: $('#vacancyPickerPanel').offset().top - 80 }, 300);
                    return;
                }

                // ----------------------------------------------------------
                // CRITICAL: disabled <select> elements are NOT serialized by
                // the browser, so the vacancy-locked Department/Position/
                // Division dropdowns (locked via .prop('disabled', true) in
                // lockStep2Field) would post as empty — and Eloquent crashes
                // with "Column 'Dept_id' cannot be null" on insert.
                //
                // Re-enable every .vacancy-locked field just long enough to
                // build FormData, then put the lock back. The user-visible
                // disabled state never flickers because the form was already
                // posted before the browser repaints.
                // ----------------------------------------------------------
                var $lockedFields = $('.vacancy-locked');
                $lockedFields.prop('disabled', false);

                let formData = new FormData(this);
                console.log(formData);

                $lockedFields.prop('disabled', true);

                // Pre-flight upload-size check. PHP's post_max_size on this
                // server is typically 8 MB; if the form is bigger PHP
                // discards the body before Laravel sees it, $request
                // arrives empty, and Eloquent throws confusing
                // "first_name cannot be null" errors. Warn the user up
                // front with the actual offending size.
                //
                // 16 MB is a safe ceiling that fits a generous-but-sane
                // budget even on default PHP installs. If the server is
                // tuned higher (see post_max_size in php.ini), this just
                // protects the user from accidentally trying to upload
                // a multi-hundred-MB CV.
                var MAX_UPLOAD_BYTES = 16 * 1024 * 1024; // 16 MB
                var totalBytes = 0;
                for (var pair of formData.entries()) {
                    if (pair[1] instanceof File) totalBytes += pair[1].size;
                }
                if (totalBytes > MAX_UPLOAD_BYTES) {
                    var mb = (totalBytes / (1024 * 1024)).toFixed(1);
                    toastr.error(
                        'The form is too large to upload (' + mb + ' MB). ' +
                        'Reduce the size of CV / certificate uploads and try again.',
                        'Upload too large',
                        { positionClass: 'toast-bottom-right' }
                    );
                    return;
                }

                $.ajax({
                    url: '{{ route('people.employees.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            // Tolerant of either `redirect_url` (current
                            // controller payload) or `redirect` (older
                            // pattern). Falls back to the People dashboard
                            // so the browser never lands at `undefined`
                            // and bounces to the super-admin URL.
                            var to = response.redirect_url || response.redirect
                                || '{{ route("people.hr.dashboard") }}';
                            window.location.href = to;
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr) {
                        // Resilient error surfacing.
                        //   • 422 Laravel validation → show every field's
                        //     first message,
                        //   • JSON error responses → show .message,
                        //   • 500 / non-JSON / network → generic message +
                        //     log the raw payload so we can diagnose.
                        // The old code did `xhr.responseJSON.message` and
                        // crashed when responseJSON was undefined (any
                        // 500/HTML response), masking the real failure.
                        var msg = 'Failed to save employee. Please try again.';
                        try {
                            if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                                var lines = [];
                                $.each(xhr.responseJSON.errors, function (_, arr) {
                                    if (Array.isArray(arr) && arr.length) lines.push(arr[0]);
                                });
                                if (lines.length) msg = lines.join('<br>');
                            } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                        } catch (e) { /* fall through to generic msg */ }
                        console.error('[employees.store] failed', xhr && xhr.status, xhr && xhr.responseText);
                        toastr.error(msg, "Error", {
                            positionClass: 'toast-bottom-right',
                            allowHtml: true
                        });
                    }
                });
            });
        });


        // upload education file
        $(document).ready(function() {
            $('#uploadFile').on('change', function(e) {
                let file = e.target.files[0];
                if (!file) return;

                let formData = new FormData();
                formData.append('document', file);
                formData.append('doc_type', 'education');

                let $progress = $(`
                <div id="certificate-progress" style="display:inline-block; width:150px; margin-left:10px;">
                    <div class="progress" style="height:1.5rem;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-themeBlue"
                                role="progressbar" style="width:50%" aria-valuenow="50"
                                aria-valuemin="0" aria-valuemax="100">
                                Uploading...
                        </div>
                    </div>
                </div>`);
                $(this).after($progress);

                $.ajax({
                    url: '{{ route('people.employees.extract-details') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#certificate-progress').remove();
                        $('#certificate-file-name').text(file.name);

                        if (response.data && response.data.extracted_fields) {
                            const extracted = response.data.extracted_fields;
                            if (extracted["Education level/type"]) {
                                $("#education_level").val(extracted["Education level/type"]);
                            }
                            if (extracted["Institution Name"]) {
                                $("#institutio_name").val(extracted["Institution Name"]);
                            }
                            if (extracted["Field of Study/Major"]) {
                                $("#field_study").val(extracted["Field of Study/Major"]);
                            }
                            if (extracted["degree/certificate earned"]) {
                                $("#degree_earned").val(extracted["degree/certificate earned"]);
                            }
                            if (extracted["attendance period"]) {
                                $("#attendance_period").val(extracted["attendance period"]);
                            }
                            if (extracted["location"]) {
                                $("#Location").val(extracted["location"]);
                            }
                        }

                    },
                    error: function() {
                        $('#certificate-progress').remove();
                    }
                });
            });
        });

        $(document).ready(function() {
            function updateRemoveAndAddButtons() {
                const $blocks = $('.employeeProCreationProcessExp-block');
                $blocks.each(function(i, block) {
                    const $block = $(block);
                    if (i === 0) {
                        $block.find('.blockAdd-btn').show();
                        $block.find('.remove-btn').hide();
                    } else {
                        $block.find('.blockAdd-btn').hide();
                        $block.find('.remove-btn').show();
                    }
                });
            }

            function updateExperienceFieldNames() {
                $('.employeeProCreationProcessExp-block').each(function(i, block) {
                    $(block).find('.company_name').attr('name', `experience[${i}][company_name]`);
                    $(block).find('.job_title').attr('name', `experience[${i}][job_title]`);
                    $(block).find('.employment_type').attr('name', `experience[${i}][employment_type]`);
                    $(block).find('.duration_employment').attr('name', `experience[${i}][duration]`);
                    $(block).find('.location1').attr('name', `experience[${i}][location]`);
                    $(block).find('.reason_leaving').attr('name', `experience[${i}][reason_for_leaving]`);
                    $(block).find('.reference_name').attr('name', `experience[${i}][reference_name]`);
                    $(block).find('.reference_contact').attr('name', `experience[${i}][reference_contact]`);
                    $(block).find('.uploadFile').attr('name', `experience[${i}][document]`);
                });
            }

            $(document).on('click', '.employeeProCreationProcessExp-main .blockAdd-btn', function(e) {
                e.preventDefault();
                const $firstBlock = $('.employeeProCreationProcessExp-block').first();
                const $newBlock = $firstBlock.clone();

                $newBlock.find('input[type="text"], input[type="file"]').val('');
                $newBlock.find('.certificate-file-name').text('');
                $newBlock.find('#certificate-progress').remove();

                $('.employeeProCreationProcessExp-main').append($newBlock);
                updateRemoveAndAddButtons();
                updateExperienceFieldNames();
            });

            $(document).on('click', '.employeeProCreationProcessExp-main .remove-btn', function(e) {
                e.preventDefault();
                $(this).closest('.employeeProCreationProcessExp-block').remove();
                updateRemoveAndAddButtons();
                updateExperienceFieldNames();
            });

            $(document).on('change', '.uploadFile', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const $block = $(this).closest('.employeeProCreationProcessExp-block');

                const $progress = $(`
                <div id="certificate-progress" style="display:inline-block; width:150px; margin-left:10px;">
                    <div class="progress" style="height:1.5rem;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-themeBlue"
                                role="progressbar" style="width:50%" aria-valuenow="50"
                                aria-valuemin="0" aria-valuemax="100">
                                Uploading...
                        </div>
                    </div>
                </div>
            `);

                $(this).after($progress);

                const formData = new FormData();
                formData.append('document', file);
                formData.append('doc_type', 'experience');

                $.ajax({
                    url: '{{ route('people.employees.extract-details') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $block.find('#certificate-progress').remove();
                        $block.find('.certificate-file-name').text(file.name);

                        const extracted = response.data?.extracted_fields || {};
                        if (extracted["company_name"]) $block.find('.company_name').val(
                            extracted["company_name"]);
                        if (extracted["position/job title"]) $block.find('.job_title').val(
                            extracted["position/job title"]);
                        if (extracted["employment_type"]) $block.find('.employment_type').val(
                            extracted["employment_type"]);
                        if (extracted["duration"]) $block.find('.duration_employment').val(
                            extracted["duration"]);
                        if (extracted["location"]) $block.find('.location1').val(extracted[
                            "location"]);
                        if (extracted["reason for leaving"]) $block.find('.reason_leaving').val(
                            extracted["reason for leaving"]);
                    },
                    error: function() {
                        $block.find('#certificate-progress').remove();
                        alert('Failed to extract data. Please fill manually.');
                    }
                });
            });

            updateRemoveAndAddButtons();
            updateExperienceFieldNames();
        });

        $(document).ready(function() {
            // EWT Activity logic: sum salary + all allowances (convert all to MVR)
            function toggleEwtActivity() {
                var salary = parseFloat($('#basic_salary').val()) || 0;
                var salaryCurrency = $('#currency_type').val();
                var thresholdMvr = 30000;
                var usdToMvr = 15.42; // 1 USD = 15.42 MVR (approx, update as needed)

                // Convert salary to MVR if needed
                var salaryMvr = salaryCurrency === 'USD' ? salary * usdToMvr : salary;

                // Sum all allowance amounts, convert each to MVR if needed
                var totalAllowanceMvr = 0;
                $('.allowanceRepeater-block').each(function() {
                    var amount = parseFloat($(this).find('.allowance-amount-input').val()) || 0;
                    var allowanceCurrency = $(this).find('.allowance-currency-select').val();
                    if (allowanceCurrency === 'USD') {
                        amount = amount * usdToMvr;
                    }
                    totalAllowanceMvr += amount;
                });

                var totalMvr = salaryMvr + totalAllowanceMvr;
                var show = totalMvr >= thresholdMvr;

                if (show) {
                    $('#ewt_actvity').removeClass('d-none');
                } else {
                    $('#ewt_actvity').addClass('d-none');
                }
            }

            $('#basic_salary, #currency_type').on('input change', toggleEwtActivity);
            $(document).on('input change', '.allowance-amount-input, .allowance-currency-select',
            toggleEwtActivity);
            toggleEwtActivity();

            // Reindex all names
            function updateAllowanceFieldNames() {
                $('.allowanceRepeater-block').each(function(i, block) {
                    $(block).find('.allowance-type-select').attr('name', `allowance[${i}][type]`);
                    $(block).find('.allowance-amount-input').attr('name', `allowance[${i}][amount]`);
                    $(block).find('.allowance-currency-select').attr('name', `allowance[${i}][currency]`);
                });
            }

            // Update Add/Remove button visibility
            function updateAllowanceButtons() {
                $('.allowanceRepeater-block').each(function(i, block) {
                    const $block = $(block);
                    if (i === 0) {
                        $block.find('.allowanceAdd-btn').show();
                        $block.find('.allowanceRemove-btn').hide();
                    } else {
                        $block.find('.allowanceAdd-btn').hide();
                        $block.find('.allowanceRemove-btn').show();
                    }
                });
            }

            // Reinitialize select2
            function reinitSelect2($context) {
                $context.find('select.select2t-none').select2();
            }

            // Add new block
            $(document).on('click', '.allowanceAdd-btn', function(e) {
                e.preventDefault();

                const $main = $('.allowanceRepeater-main');
                const $firstBlock = $main.find('.allowanceRepeater-block').first();

                // Destroy select2 on the original block before cloning
                $firstBlock.find('select.select2t-none').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                const $newBlock = $firstBlock.clone();

                // Clear values
                $newBlock.find('select').val('').trigger('change');
                $newBlock.find('input[type="text"], input[type="number"]').val('');

                // Remove any select2 DOM artifacts
                $newBlock.find('.select2-container').remove();
                $newBlock.find('select.select2t-none')
                    .removeAttr('data-select2-id')
                    .removeAttr('aria-hidden')
                    .removeAttr('tabindex')
                    .show();

                $main.append($newBlock);

                updateAllowanceButtons();
                updateAllowanceFieldNames();

                // Re-initialize select2 on both original and new blocks
                reinitSelect2($firstBlock);
                reinitSelect2($newBlock);

                // Re-bind allowance input event for new block
                $newBlock.find('.allowance-amount-input, .allowance-currency-select').on('input change',
                    toggleEwtActivity);
            });

            // Remove a block
            $(document).on('click', '.allowanceRemove-btn', function(e) {
                e.preventDefault();
                $(this).closest('.allowanceRepeater-block').remove();
                updateAllowanceButtons();
                updateAllowanceFieldNames();
                toggleEwtActivity();
            });

            // Initialize on load
            updateAllowanceButtons();
            updateAllowanceFieldNames();
            reinitSelect2($(document));
        });



        $(document).ready(function() {
            function updateBankNames() {
                $('.bankRepeater-block').each(function(i, block) {
                    $(block).find('.bank_name').attr('name', `bank[${i}][bank_name]`);
                    $(block).find('.bank_branch').attr('name', `bank[${i}][bank_branch]`);
                    $(block).find('.account_type').attr('name', `bank[${i}][account_type]`);
                    $(block).find('.ifsc').attr('name', `bank[${i}][ifsc]`);
                    $(block).find('.account_name').attr('name', `bank[${i}][account_name]`);
                    $(block).find('.account_number').attr('name', `bank[${i}][account_number]`);
                    $(block).find('.currency').attr('name', `bank[${i}][currency]`);
                    $(block).find('.iban').attr('name', `bank[${i}][iban]`);
                });
            }

            function updateBankButtons() {
                $('.bankRepeater-block').each(function(i, block) {
                    const $block = $(block);
                    if (i === 0) {
                        $block.find('.bankAdd-btn').show();
                        $block.find('.bankRemove-btn').hide();
                    } else {
                        $block.find('.bankAdd-btn').hide();
                        $block.find('.bankRemove-btn').show();
                    }
                });
            }

            function reinitSelect2($context) {
                $context.find('select.select2t-none').select2();
            }

            $(document).on('click', '.bankAdd-btn', function(e) {
                e.preventDefault();

                const $first = $('.bankRepeater-block').first();
                const $new = $first.clone();

                // Reset inputs
                $new.find('input[type="text"]').val('');
                $new.find('select').val('').trigger('change');

                // Remove select2 artifacts
                $new.find('.select2-container').remove();
                $new.find('select.select2t-none').removeAttr('data-select2-id').removeAttr('aria-hidden')
                    .show();

                $('.bankRepeater-main').append($new);

                updateBankNames();
                updateBankButtons();
                reinitSelect2($new);
            });

            $(document).on('click', '.bankRemove-btn', function(e) {
                e.preventDefault();
                $(this).closest('.bankRepeater-block').remove();
                updateBankNames();
                updateBankButtons();
            });

            // Init
            updateBankNames();
            updateBankButtons();
            reinitSelect2($(document));
        });
    </script>

    <script>
        $(document).ready(function() {
            // Helper: skip the cascading wipe on dependent fields that were
            // locked by the vacancy picker. Without this, picking Section
            // after applying a vacancy fired #section.on('change') which
            // wiped the locked Position dropdown — leaving "Position required".
            function wipeIfNotLocked($field) {
                if ($field.hasClass('vacancy-locked')) return false;
                $field.html('<option></option>').trigger('change');
                return true;
            }

            // Toggle disabled on a select2-backed field. Pure attr() doesn't
            // grey the Select2 widget — Select2 needs its own .select2({ disabled }).
            // Never enable a vacancy-locked field — the vacancy lock owns it.
            function setFieldDisabled($field, isDisabled) {
                if (!isDisabled && $field.hasClass('vacancy-locked')) return;
                $field.prop('disabled', !!isDisabled);
                if ($field.data('select2')) {
                    try { $field.select2({ disabled: !!isDisabled }); } catch (e) {}
                }
            }

            // Division -> Department
            $('#division').on('change', function() {
                // Don't repopulate Department if it's locked to a vacancy —
                // doing so would clobber the chosen department option.
                if ($('#department').hasClass('vacancy-locked')) return;
                let divisionId = $(this).val();
                wipeIfNotLocked($('#department'));
                wipeIfNotLocked($('#section'));
                wipeIfNotLocked($('#position'));
                // No department yet → Section has nothing to cascade from.
                setFieldDisabled($('#section'), true);
                if (!divisionId) return;
                $.ajax({
                    url: '{{ route('people.getDepartmentsByDivision') }}',
                    type: 'GET',
                    data: {
                        division_id: divisionId
                    },
                    success: function(res) {
                        let html = '<option></option>';
                        $.each(res.departments, function(_, department) {
                            html +=
                                `<option value="${department.id}">${department.name}</option>`;
                        });
                        $('#department').html(html).trigger('change');
                    }
                });
            });

            // Department -> Section
            $('#department').on('change', function() {
                let departmentId = $(this).val();
                wipeIfNotLocked($('#section'));
                wipeIfNotLocked($('#position'));
                // Default Section to disabled — flip back on only if the
                // chosen department actually has sections defined.
                setFieldDisabled($('#section'), true);
                if (!departmentId) return;
                getReportingPerson(departmentId);
                $.ajax({
                    url: '{{ route('people.getSectionByDepartment') }}',
                    type: 'GET',
                    data: {
                        department_id: departmentId
                    },
                    success: function(res) {
                        let html = '<option></option>';
                        if (res.sections.length > 0) {
                            $.each(res.sections, function(_, section) {
                                html +=
                                    `<option value="${section.id}">${section.name}</option>`;
                            });
                            $('#section').html(html).trigger('change');
                            setFieldDisabled($('#section'), false);
                        } else {
                            // No sections under this dept — leave Section
                            // disabled and load positions directly off dept.
                            loadPositions({
                                department_id: departmentId
                            });
                        }
                    }
                });
            });

            // Section -> Position
            $('#section').on('change', function() {
                let sectionId = $(this).val();
                // Don't wipe a locked Position — that's the bug where
                // picking Section after applying a vacancy left Position
                // empty + "Position required".
                if ($('#position').hasClass('vacancy-locked')) return;
                $('#position').html('<option></option>').trigger('change');
                if (!sectionId) return;
                loadPositions({
                    section_id: sectionId
                });
            });

            function loadPositions(params) {
                $.ajax({
                    url: '{{ route('people.getPositionBySection') }}',
                    type: 'GET',
                    data: params,
                    success: function(res) {
                        let html = '<option></option>';
                        $.each(res.positions, function(_, position) {
                            html +=
                                `<option value="${position.id}">${position.position_title}</option>`;
                        });
                        $('#position').html(html).trigger('change');
                    }
                });
            }

            function getReportingPerson(departmentId) {
                $.ajax({
                    url: '{{ route('people.getReportingPerson') }}',
                    type: 'GET',
                    data: {
                        department_id: departmentId
                    },
                    success: function(res) {
                        let html = '<option></option>';
                        $.each(res.data, function(_, person) {

                            let displayName = '';
                            if (person.first_name || person.last_name) {
                                displayName = (person.first_name ? person.first_name : '') +
                                    ' ' + (person.last_name ? person.last_name : '');
                                displayName = displayName.trim();
                            } else if (person.name) {
                                displayName = person.name;
                            }
                            html += `<option value="${person.id}">${displayName}</option>`;
                        });
                        $('#reporting_person').html(html).trigger('change');
                    }
                });
            }
        });

        $(document).ready(function() {
            // ------------------------------------------------------------
            // Probation End Date is ALWAYS derived from Joining Date + 3
            // months. HR doesn't pick it directly — the field stays
            // disabled. Probationary employees automatically appear in
            // the Probation module because the back-end sets
            // probation_status='Active' when employment_type=Probationary.
            // ------------------------------------------------------------
            function recomputeProbationEndDate() {
                if ($('#employment_status').val() !== 'Probationary') {
                    $('#probation_exp_date').val('');
                    return;
                }
                var jd = $('#joining_date').val();
                if (!/^\d{2}\/\d{2}\/\d{4}$/.test(jd)) {
                    $('#probation_exp_date').val('');
                    return;
                }
                var parts = jd.split('/');
                // Build a UTC date to avoid timezone roll-over flipping
                // the day around midnight.
                var dt = new Date(Date.UTC(
                    parseInt(parts[2], 10),
                    parseInt(parts[1], 10) - 1,
                    parseInt(parts[0], 10)
                ));
                dt.setUTCMonth(dt.getUTCMonth() + 3);
                var dd = String(dt.getUTCDate()).padStart(2, '0');
                var mm = String(dt.getUTCMonth() + 1).padStart(2, '0');
                var yyyy = dt.getUTCFullYear();
                $('#probation_exp_date').val(dd + '/' + mm + '/' + yyyy);
            }
            function toggleProbationExpDate() {
                // Stay disabled regardless — it's a derived field, not a
                // user input. Just recompute or clear depending on whether
                // the employment status is Probationary.
                $('#probation_exp_date').prop('disabled', true).prop('readonly', true);
                recomputeProbationEndDate();
            }
            $('#employment_status').on('change', toggleProbationExpDate);
            // bootstrap-datepicker fires `changeDate` on calendar pick;
            // listen for it explicitly so picking via the widget (not
            // typing) also rolls the probation date forward.
            $('#joining_date').on('change input changeDate', recomputeProbationEndDate);
            toggleProbationExpDate();

            // When HR manually changes the Benefit Grid Level (overriding
            // the position default), pull that level's entitlements so the
            // Service Charge / Overtime / Public Holiday OT switches stay
            // honest. Skips when the level was changed by us via the
            // vacancy-picker programmatic set (the picker already wrote
            // the switches based on the position's grid).
            $(document).on('change', '#benefit_grid_level', function () {
                var grade = $(this).val();
                if (!grade) return;
                if ($(this).hasClass('vacancy-locked')) return;
                $.ajax({
                    url: '{{ route('people.getBenefitGridByLevel') }}',
                    type: 'GET',
                    data: { benefit_grid_level: grade },
                    success: function (res) {
                        if (!res || !res.success) return;
                        $('#entitle_service_charge').prop('checked', res.service === 'yes');
                        $('#entitle_public_holiday').prop('checked', res.holiday_overtime === 'yes');
                        $('#entitle_overtime').prop('checked', res.overtime === 'yes');
                    }
                });
            });

            $('#position').on('change', function() {

                let positionId = $(this).val();
                $('#benefit_grid_level').html('<option></option>').trigger('change');
                if (!positionId) return;
                $.ajax({
                    url: '{{ route('people.getBenefitGridByPosition') }}',
                    type: 'GET',
                    data: {
                        position_id: positionId
                    },
                    success: function(res) {
                        console.log(res);
                        let html = '<option></option>';
                        html +=
                            `<option value="${res.benfitGrid_emp_id}" selected>${res.emp_grade_name}</option>`;
                        $('#entitle_service_charge').prop('checked', res.service === 'yes');
                        $('#entitle_public_holiday').prop('checked', res
                            .holiday_overtime === 'yes');
                        $('#entitle_overtime').prop('checked', res.overtime === 'yes');
                        $('#position_rank').val(res.position_rank);

                        $('#benefit_grid_level').html(html).trigger('change');
                    }
                });
            });
        });

        $(document).ready(function() {
            function updateEducationButtons() {
                const $blocks = $('.employeeEducationRepeater-block');
                $blocks.each(function(i, block) {
                    const $block = $(block);
                    if (i === 0) {
                        $block.find('.education-add-btn').show();
                        $block.find('.education-remove-btn').hide();
                    } else {
                        $block.find('.education-add-btn').hide();
                        $block.find('.education-remove-btn').show();
                    }
                });
            }

            function updateEducationFieldNames() {
                $('.employeeEducationRepeater-block').each(function(i, block) {
                    $(block).find('.education-upload-input').attr('name', `education[${i}][document]`);
                    $(block).find('.education_level').attr('name', `education[${i}][education_level]`);
                    $(block).find('.institutio_name').attr('name', `education[${i}][institutio_name]`);
                    $(block).find('.field_study').attr('name', `education[${i}][field_study]`);
                    $(block).find('.degree_earned').attr('name', `education[${i}][degree_earned]`);
                    $(block).find('.attendance_period').attr('name', `education[${i}][attendance_period]`);
                    $(block).find('.location').attr('name', `education[${i}][location]`);
                });
            }

            $(document).on('click', '.education-add-btn', function(e) {
                e.preventDefault();
                const $firstBlock = $('.employeeEducationRepeater-block').first();
                const $newBlock = $firstBlock.clone();

                $newBlock.find('input[type="text"], input[type="file"]').val('');
                $newBlock.find('.education-file-name').text('');
                $newBlock.find('#education-progress').remove();

                $('.employeeEducationRepeater-main').append($newBlock);
                updateEducationButtons();
                updateEducationFieldNames();
            });

            $(document).on('click', '.education-remove-btn', function(e) {
                e.preventDefault();
                $(this).closest('.employeeEducationRepeater-block').remove();
                updateEducationButtons();
                updateEducationFieldNames();
            });

            $(document).on('change', '.education-upload-input', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const $block = $(this).closest('.employeeEducationRepeater-block');
                $block.find('.education-file-name').text('');
                $block.find('#education-progress').remove();

                const $progress = $(`
                <div id="education-progress" style="display:inline-block; width:150px; margin-left:10px;">
                    <div class="progress" style="height:1.5rem;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-themeBlue"
                                role="progressbar" style="width:50%" aria-valuenow="50"
                                aria-valuemin="0" aria-valuemax="100">
                                Uploading...
                        </div>
                    </div>
                </div>
            `);
                $(this).closest('.uploadFile-btn').after($progress);

                const formData = new FormData();
                formData.append('document', file);
                formData.append('doc_type', 'education');

                $.ajax({
                    url: '{{ route('people.employees.extract-details') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $block.find('#education-progress').remove();
                        $block.find('.education-file-name').text(file.name);

                        const extracted = response.data?.extracted_fields || {};
                        if (extracted["Education level/type"]) $block.find('.education_level')
                            .val(extracted["Education level/type"]);
                        if (extracted["Institution Name"]) $block.find('.institutio_name').val(
                            extracted["Institution Name"]);
                        if (extracted["Field of Study/Major"]) $block.find('.field_study').val(
                            extracted["Field of Study/Major"]);
                        if (extracted["degree/certificate earned"]) $block.find(
                            '.degree_earned').val(extracted["degree/certificate earned"]);
                        if (extracted["attendance period"]) $block.find('.attendance_period')
                            .val(extracted["attendance period"]);
                        if (extracted["location"]) $block.find('.location').val(extracted[
                            "location"]);
                    },
                    error: function() {
                        $block.find('#education-progress').remove();
                        alert('Failed to extract data. Please fill manually.');
                    }
                });
            });

            updateEducationButtons();
            updateEducationFieldNames();
        });
    </script>

    {{-- ────────────────────────────────────────────────────────────────────
         "No vacancies" form lock-down. When the server-rendered hidden
         flag is present, disable every editable control OUTSIDE the
         vacancy picker panel so HR can't fill in a hire that has no
         vacancy to attach to. The alert at the top + the "Create a
         Vacancy" CTA still work normally because they live INSIDE the
         vacancy panel (or are anchor tags, which we leave alone).

         Done in JS rather than as a Blade @if wrap because the form
         is multi-step (4 fieldsets + Parsley + Select2 + drag-drop
         uploader); short-circuiting the markup would have meant deleting
         every step's HTML, which is brittle. Lock-down is purely
         defensive — the EmployeeController::store() validation already
         requires `vacancy_id`, so even a tampered submit would 422.
         ──────────────────────────────────────────────────────────────── --}}
    <script>
        $(function () {
            if (!document.getElementById('no-vacancies-flag')) return;

            var $form = $('#msform');
            if (!$form.length) return;
            var $vacancyPanel = $('#vacancyPickerPanel');

            // Disable every editable element NOT inside the vacancy panel.
            $form.find('input, select, textarea, button').each(function () {
                if ($vacancyPanel.has(this).length) return;       // leave the picker alone
                if ($(this).attr('type') === 'hidden') return;     // hidden inputs stay
                $(this).prop('disabled', true);
            });
            // Bootstrap step-nav buttons + the upload "Browse" links.
            // .action-button is the Next/Submit; .add-fullFinal/.btn are
            // the repeater "Add More" / "Upload Photo" anchor buttons.
            $form.find('.action-button, .next-step, .previous-step, .btn').each(function () {
                if ($vacancyPanel.has(this).length) return;
                $(this).addClass('disabled').attr('aria-disabled', 'true').css('pointer-events', 'none');
            });
            // Grey out every fieldset that isn't the vacancy panel so it
            // reads visually as locked.
            $form.find('fieldset').each(function () {
                if ($vacancyPanel.has(this).length || $(this).has($vacancyPanel).length) return;
                $(this).css({ opacity: 0.45, 'pointer-events': 'none' });
            });
            // Step 1 contains BOTH the vacancy panel and the locked
            // content. Grey only the post-vacancy children.
            $form.find('fieldset[data-setp="1"] > *').each(function () {
                if ($vacancyPanel.is(this) || $vacancyPanel.has(this).length) return;
                $(this).css({ opacity: 0.45, 'pointer-events': 'none' });
            });
        });
    </script>
@endsection
