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
                                    <label for="email_address" class="form-label">Email Address<span
                                            class="req_span">*</span></label>
                                    <input type="email" class="form-control" id="email_address" name="email_address"
                                        placeholder="Email Address" required data-parsley-type="email"
                                        data-parsley-required-message="Email address is required."
                                        data-parsley-type-message="Please enter a valid email address.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="mobile_num" class="form-label">Mobile Number<span
                                            class="req_span">*</span></label>
                                    <div>
                                        <input type="tel" class="form-control" id="mobile_num" name="mobile_num"
                                            placeholder="Mobile Number" required
                                            data-parsley-required-message="Mobile number is required."
                                            data-parsley-mobile_number
                                            data-parsley-mobile_number-message="Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code.">

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
                                        <option value="">select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">other</option>
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
                                <div class="col-lg-4 col-sm-6">
                                    <label for="passport_numb" class="form-label">PASSPORT NUMBER<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="passport_numb" name="passport_numb"
                                        placeholder="Passport Number" required
                                        data-parsley-required-message="Passport number is required."
                                        data-parsley-pattern="^[A-Za-z0-9]{5,20}$"
                                        data-parsley-pattern-message="Please enter a valid passport number (5-20 alphanumeric characters).">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="passport_expiry_date" class="form-label">Passport expiry date <span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control datepicker" id="passport_expiry_date"
                                        name="passport_expiry_date" placeholder="Passport Expiry Date" required
                                        data-parsley-required-message="Passport expiry date is required."
                                        data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                        data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format.">
                                </div>

                                <div class="col-lg-4 col-sm-6">
                                    <label for="nid" class="form-label">NID</label>
                                    <input type="text" class="form-control" id="nid" name="nid"
                                        placeholder="NID" data-parsley-required-message="NID is required."
                                        data-parsley-pattern="^[A-Z]{1,2}[0-9]{6,9}$"
                                        data-parsley-pattern-message="Please enter a valid Maldivian NID. It should start with 1-2 uppercase letters followed by 6-9 digits."
                                        data-parsley-trigger="input">
                                </div>
                                <div class="col-12">
                                    <div class="address-block">
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
                                    <input type="number" class="form-control" id="emg_number" name="emg_contact_number"
                                        placeholder="Number" required
                                        data-parsley-required-message="Mobile number is required."
                                        data-parsley-mobile_number
                                        data-parsley-mobile_number-message="Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code.">
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
                                    <label for="email_add_step2" class="form-label">Email Address<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="email_add_step2"
                                        name="email_add_step2" placeholder="Email Address" required>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="mobile_num_s2" class="form-label">Mobile Number<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="mobile_num_s2" name="mobile_num_s2"
                                        required placeholder="Mobile Number"
                                        data-parsley-required-message="Mobile number is required."
                                        data-parsley-mobile_number
                                        data-parsley-mobile_number-message="Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code.">
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
                                    <select class="form-select select2t-none" id="section" name="section"
                                        data-placeholder="Section">
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
                                    <label for="probation_exp_date" class="form-label">PROBATION EXP DATE<span
                                            class="req_span">*</span></label>
                                    <input type="text" class="form-control datepicker" id="probation_exp_date"
                                        name="probation_exp_date" placeholder="dd/mm/yyyy" disabled>
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
                                    <input type="number" min="1" class="form-control" id="basic_salary"
                                        name="basic_salary" placeholder="Basic Salary" required
                                        data-parsley-required-message="Basic salary is required."
                                        data-parsley-type="number" data-parsley-min="1"
                                        data-parsley-type-message="Please enter a valid number."
                                        data-parsley-min-message="Basic salary must be at least 1.">
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
                                        <input type="number" min="0" class="form-control" id="pension"
                                            name="pension" placeholder="Pension" data-parsley-required="false"
                                            data-parsley-required-message="Pension is required for Maldivian employees."
                                            data-parsley-min="0" data-parsley-min-message="Pension must be at least 0."
                                            data-parsley-trigger="change">
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
                                        <label for="entitle_switch" class="form-label">ENTITLE FOR SERVICE CHARGE</label>
                                        <div>
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="entitle_switch" name="entitle_switch">
                                                <label class="form-check-label" for="entitle_switch">Yes</label>
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
                                        <label for="entitle_public_holiday_overtime" class="form-label">ENTITLE FOR PUBLIC
                                            HOLIDAY
                                            OVERTIME</label>
                                        <div>
                                            <div class="form-check form-switch form-switchTheme switch-blue">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="entitle_public_holiday_overtime"
                                                    name="entitle_public_holiday_overtime">
                                                <label class="form-check-label"
                                                    for="entitle_public_holiday_overtime">Yes</label>
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
                                        <label class="form-label">IBAN <span class="req_span">*</span></label>
                                        <input type="text" class="form-control iban" name="bank[0][iban]"
                                            placeholder="IBAN" required data-parsley-required-message="IBAN is required."
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
                                            <input type="text" class="form-control reference_contact"
                                                name="experience[0][reference_contact]" placeholder="Reference Contact"
                                                required data-parsley-pattern="^\d{10,15}$"
                                                data-parsley-required-message="Reference contact is required."
                                                data-parsley-pattern-message="Please enter a valid contact number (10-15 digits).">
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
    </style>
@endsection

@section('import-scripts')

    {{-- old --}}
    <script type="text/javascript">
        $(document).ready(function() {
            initSelect2AndValidation();
            initParsleyValidation();
            initDatePicker();

            $('#date_birth').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true, // Close the picker after selection
                todayHighlight: true // Highlight today's date
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
                window.Parsley.addValidator('mobile_number', {
                    validateString: function(value) {
                        return /^(\+\d{1,4}\s?)?[0-9]{10}$/.test(value);
                    },
                    messages: {
                        en: 'Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code.'
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
            $('#basic_salary').on('keyup', function() {

                const nationality = $('#nationality').val();
                const $pensionField = $('#pension');

                if (nationality === 'Maldivian') {
                    $pensionField.attr('data-parsley-required', 'true');
                    $pensionField.attr('data-parsley-required-message',
                        'Pension is required for Maldivian employees.');
                    const basicSalary = parseFloat($(this).val()) || 0;
                    const calculatedPension = (basicSalary * 0.07).toFixed(2);
                    $pensionField.val(calculatedPension);
                }

                $pensionField.parsley().validate();
            });
        });
    </script>

    {{-- New --}}
    <script>
        $(document).ready(function() {
            $('#fileInput').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    $('#cv-file-name').text(file.name);
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
                let formData = new FormData(this);
                console.log(formData);
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
                            window.location.href = response.redirect_url;
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message, "Error", {
                            positionClass: 'toast-bottom-right'
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
            // Division -> Department
            $('#division').on('change', function() {
                let divisionId = $(this).val();
                $('#department').html('<option></option>').trigger('change');
                $('#section').html('<option></option>').trigger('change');
                $('#position').html('<option></option>').trigger('change');
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
                $('#section').html('<option></option>').trigger('change');
                $('#position').html('<option></option>').trigger('change');
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
                        } else {
                            // No sections, load positions directly
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
            function toggleProbationExpDate() {
                if ($('#employment_status').val() === 'Probationary') {
                    $('#probation_exp_date').prop('disabled', false).prop('required', true);
                } else {
                    $('#probation_exp_date').prop('disabled', true).val('').prop('required', false);
                }
            }
            $('#employment_status').on('change', toggleProbationExpDate);
            toggleProbationExpDate();

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
                        $('#entitle_switch').prop('checked', res.service === 'yes');
                        $('#entitle_public_holiday_overtime').prop('checked', res
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
@endsection
