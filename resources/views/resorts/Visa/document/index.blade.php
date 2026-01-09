@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

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
                        <span>VISA MANAGEMENT</span>
                        <h1>{{$page_title}}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card document-management-tabcard">
                    <form id="msform" class="VisaEmployteeForm" enctype="multipart/form-data">

                        <!-- progressbar -->
                        <div class="progressbar-wrapper visa-Management-progressbar">
                            <ul id="progressbar"
                                class="progressbar-tab d-flex justify-content-between align-items-center">
                                <li class="active current"> <span>Uploading Documents</span></li>
                                <li><span>Document Segregation & Naming</span></li>
                                <li><span>Document Validation & Processing</span></li>
                                <li><span>Database Creation</span></li>
                                <li><span>Employment<br>Information</span></li>
                                <li><span>Education / Qualification</span></li>
                                <li><span>Experience</span></li>
                            </ul>
                        </div>
                        <hr>
                        <fieldset>
                            <div class=" mb-md-5 mb-3">
                                <h2 class="text-center">Interview Rounds</h2>
                                <div class="row g-lg-4 g-3 mb-lg-4 mb-3">
                                    <div class=" col-md-6">
                                        <div class="card bg h-100">
                                            <div class="uploadFileNew-block h-100">
                                                <img src="{{URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                                <h5>Upload A Single Consolidated PDF File</h5>
                                                <p>Passport, CV, Qualifications, Etc</p>
                                                <input 
                                                    type="file" 
                                                    id="consolidatedFile" 
                                                    name="consolidatedFile" 
                                                    class="form-control" 
                                                    accept=".pdf" 
                                                    data-parsley-fileextension="pdf"
                                                    data-parsley-fileextension-message="Only PDF files are  allowed."
                                                    data-parsley-trigger="change"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <div class=" col-md-6">
                                        <div class="card bg h-100">
                                            <div class="uploadFileNew-block h-100">
                                                <img src="{{URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                                <h5>Upload Multiple Separate Files</h5>
                                                <p>Separate CV, Passport Copy, Photo</p>
                                                <input 
                                                    type="file" 
                                                    id="saperateFile" 
                                                    name="saperateFile" 
                                                    class="form-control" 
                                                    accept=".pdf" 
                                                    multiple ="multiple" 
                                                    data-parsley-multiple="true"
                                                    data-parsley-fileextension="pdf"
                                                    data-parsley-fileextension-message="Only PDF files are allowed."
                                                    data-parsley-trigger="change"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none d-md-block" style="height: 20px;"></div>
                                <div class="file-error-message error text-center"></div>
                            </div>
                            <hr class="hr-footer">
                            <a href="javascript:void(0);" class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                        </fieldset>
                        <fieldset>
                            <div class="mb-md-5 mb-3">
                                <h2 class="text-center">Document Segregation and Naming</h2>
                                <div class="row g-lg-4 g-3">
                                    <!-- Document boxes will be inserted here by JavaScript -->
                                </div>
                                <div class="doctype-error-message error text-center"></div>
                            </div>
                            <div class="d-none d-md-block" style="height: 104px;"></div>
                            <hr class="hr-footer">
                            <a href="javascript:void(0);" class="btn btn-themeBlue btn-sm float-end next">Next</a>
                            <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset>
                            <div class=" mb-md-5 mb-3">
                                <h2 class="text-center">Document Validation and Processing</h2>

                                <ul class="listing-wrapper document-proces-listing">
                                    <li>Passport copy converted to 200 DPI</li>
                                    <li>Employee photo digitized and background whitened</li>
                                    <li>Passport validity verified: Valid for XX years</li>
                                </ul>

                            </div>
                            <div class="d-none d-md-block" style="height: 209px;"></div>
                            <hr class="hr-footer">
                            <a href="javascript:void(0);" class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                            <a href="javascript:void(0);" class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>


                        <!-- <fieldset>
                            <div class=" mb-md-5 mb-3">
                                <h2 class="text-center">Folder Creation and Storage</h2>

                                <div class="generated-folName">
                                    <label for="total-ser" class="form-label">GENERATED FOLDER NAME</label>
                                    <div class="row g-md-4 g-2">
                                        <div class="col-lg-4 col-md-8 col-sm"><input type="text" class="form-control"
                                                id="total-ser" placeholder="$12,726">
                                        </div>
                                        <div class="col-auto"><a href="#" class="btn btn-themeSkyblue">Edit</a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="d-none d-md-block" style="height: 327px;"></div>
                            <hr class="hr-footer">
                            <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset> -->

                        <fieldset>
                            <div class=" mb-3">
                                <h2 class="text-center">Database Creation</h2>
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-first-name" class="form-label">FIRST NAME</label>
                                        <input type="text" name="first_name" class="form-control" id="txt-first-name"
                                            placeholder="Management">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-last-name" class="form-label">Middle NAME</label>
                                        <input type="text" name="middle_name" class="form-control" id="txt-middle-name"
                                            placeholder="Management">
                                    </div>
                                   <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-last-name" class="form-label">LAST NAME</label>
                                        <input type="text" name="last_name" class="form-control" id="txt-last-name"
                                            placeholder="Management">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-bod" class="form-label">DATE OF BIRTH</label>
                                        <input type="text" class="form-control datepicker" id="db_dob" name="dob" placeholder="Date of Birth">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-passport-number" class="form-label">PASSPORT NUMBER</label>
                                        <input type="text" class="form-control" name="passport_no"  id="txt-passport-number" placeholder="">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-issue-date" class="form-label">ISSUE DATE</label>
                                        <input type="text" class="form-control datepicker" name="Passport_issueDate" id="Passport_issueDate" placeholder="12/09/2024">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-expiry-date" class="form-label">EXPIRY DATE</label>
                                        <input type="text" class="form-control datepicker" id="Passport_expiry_date"  name="Passport_expiry_date"
                                            placeholder="12/09/2024">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-address" class="form-label">ADDRESS</label>
                                        <input type="text" class="form-control" id="address" placeholder="" name="address">
                                    </div>
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-qualifications" class="form-label">QUALIFICATIONS</label>
                                        <input type="text" class="form-control" id="txt-qualifications" name="qualifications"placeholder="">
                                    </div>
                                    <!-- <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-qualifications" class="form-label">CERTIFICATIONS</label>
                                        <p class="d-flex align-items-center mt-2 fw-600">
                                            <img src="assets/images/pdf1.svg" alt="" class="img-fluid me-2"> </p>
                                    </div> -->
                                    <div class="col-xl-4 col-sm-6 ">
                                        <label for="txt-experience" class="form-label">EXPERIENCE</label>
                                        <input type="text" class="form-control" id="experience"  name="experience" placeholder="">
                                    </div>
                                </div>

                                <div class="">
                                    <div class="card-title">
                                        <h3>Personal Information</h3>
                                    </div>
                                        <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="employeeF_name" class="form-label">First Name <span class="req_span">*</span> </label>
                                                <input type="text" class="form-control" id="employeeF_name" name="employeeF_name"
                                                    placeholder="First Name" required data-parsley-pattern="^[a-zA-Z\s]+$" 
                                                    data-parsley-required-message="First name is required."
                                                    data-parsley-pattern-message="Only letters are allowed.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="employeeL_name" class="form-label">Last Name <span class="req_span">*</span></label>
                                                <input type="text" class="form-control" id="employeeL_name" name="employeeL_name"
                                                    placeholder="Last Name" required data-parsley-pattern="^[a-zA-Z\s]+$" data-parsley-required-message="Last name is required."
                                                    data-parsley-pattern-message="Only letters are allowed.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="first_email_address" class="form-label">Email Address<span class="req_span">*</span></label>
                                                <input type="email" class="form-control" id="first_email_address" name="email_address"
                                                    placeholder="Email Address" required data-parsley-type="email"
                                                    data-parsley-required-message="Email address is required."
                                                    data-parsley-type-message="Please enter a valid email address.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="mobile_num" class="form-label">Mobile Number<span class="req_span">*</span></label>
                                                <div>
                                                    <input type="tel" class="form-control" id="mobile_num" name="mobile_num"
                                                        placeholder="Mobile Number" required
                                                        data-parsley-required-message="Mobile number is required."
                                                        data-parsley-mobile_number
                                                        data-parsley-mobile_number-message="Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code.">
                                                    
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="date_birth" class="form-label">Date of Birth <span class="req_span">*</span></label>
                                                <input type="text" class="form-control datepicker" id="date_birth" name="date_birth"
                                                placeholder="Date of Birth" required
                                                data-parsley-required-message="Date of Birth is required."
                                                data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                                data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format."
                                                data-parsley-date="past">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="gender" class="form-label">Gender <span class="req_span">*</span></label>
                                                <select class="form-select select2t-none" id="gender" name="gender" 
                                                    required 
                                                    data-parsley-errors-container="#gender-error"
                                                    data-parsley-error-message="Please Select Gender">
                                                    <option value="">select gender</option>
                                                    <option value="male">Male</option>
                                                    <option value="female">Female</option>
                                                    <option value="other">other</option>
                                                </select>
                                                
                                                <div id="gender-error"></div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="marital_status" class="form-label">Marital Status <span class="req_span">*</span></label>
                                                <select class="form-select select2t-none" id="marital_status" required name="marital_status"
                                                    data-placeholder="Marital Status"
                                                    data-parsley-errors-container="#marital_status_error"
                                                    data-parsley-error-message="Please Select Gender">
                                                    <option></option>
                                                    <option value="Single">Single</option>
                                                    <option value="Married">Married</option>
                                                    <option value="Divorced">Divorced</option>
                                                    <option value="Widowed">Widowed</option>
                                                </select>
                                                <div id="marital_status_error" class="invalid-feedback d-block"></div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="nationality" class="form-label" required>Nationality <span
                                                        class="req_span">*</span></label>
                                                <select class="form-select select2t-none" id="nationality" required name="nationality"
                                                    data-placeholder="Nationality"
                                                    data-parsley-errors-container="#Nationality_error"
                                                    data-parsley-error-message="Please Select Nationality">
                                                    <option></option>
                                                    @foreach($nationalitys as $nationality )
                                                        <option value="{{$nationality}}">{{$nationality}}</option>
                                                    @endforeach
                                                </select>
                                                <div id="Nationality_error" class="invalid-feedback d-block"></div>

                                                
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="religion" class="form-label">Religion <span class="req_span">*</span></label>
                                                <select class="form-select select2t-none" id="religion" name="religion" required
                                                    data-placeholder="Religion"
                                                     data-parsley-errors-container="#Religion_error"
                                                    data-parsley-error-message="Please Select Religion">
                                                    <option></option>
                                                    <option value="0">Non-Muslim</option>
                                                    <option value="1">Muslim</option>
                                                </select>
                                                <div id="Religion_error" class="invalid-feedback d-block"></div>

                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="blood_group" class="form-label">Blood Group <span class="req_span">*</span></label>
                                                <select class="form-select select2t-none" id="blood_group" name="blood_group" required
                                                    data-placeholder="Blood Group"
                                                     data-parsley-errors-container="#blood_group_error"
                                                    data-parsley-error-message="Please Select Blood Group">
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
                                                
                                                <div id="blood_group_error" class="invalid-feedback d-block"></div>

                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="passport_numb" class="form-label">PASSPORT NUMBER<span class="req_span">*</span></label>
                                                <input type="text" class="form-control" id="passport_numb" name="passport_numb"
                                                    placeholder="Passport Number" required
                                                    data-parsley-required-message="Passport number is required."
                                                    data-parsley-pattern="^[A-Za-z0-9]{5,20}$"
                                                    data-parsley-pattern-message="Please enter a valid passport number (5-20 alphanumeric characters).">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="passport_expiry_date" class="form-label">Passport expiry date <span
                                                        class="req_span">*</span></label>
                                                <input type="text" class="form-control datepicker" id="step2_passport_expiry_date"
                                                    name="passport_expiry_date" placeholder="Passport Expiry Date" required
                                                    data-parsley-required-message="Passport expiry date is required."
                                                    data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                                    data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format.">
                                            </div>
                                            
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="nid" class="form-label">NID</label>
                                                <input type="text" class="form-control" id="nid" name="nid" placeholder="NID" 
                                                    data-parsley-required-message="NID is required."
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
                                                            <input type="text" class="form-control" name="parmanent_addline2" placeholder="Address Line 2">
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
                                                            <input type="number" class="form-control" placeholder="Postal Code" name="parmanent_postal_code" required
                                                                data-parsley-required-message="Postal code is required."
                                                                data-parsley-type="digits"
                                                                data-parsley-type-message="Please enter a valid 5-digit postal code."
                                                                data-parsley-pattern="^\d{5}$"
                                                                data-parsley-pattern-message="Postal code must be exactly 5 digits.">
                                                        </div>
                                                        <div class="col-lg-4 col-sm-6">
                                                            <select class="form-select select2t-none" data-placeholder="Country" 
                                                            name="parmanent_country" required
                                                            data-parsley-errors-container="#Country_error"
                                                            data-parsley-error-message="Please Select Blood Group">
                                                                <option></option>
                                                                @foreach($countries as $country)
                                                                    <option value="{{ $country}}">{{ $country }}</option>
                                                                @endforeach
                                                            </select>
                                                            <div id="Country_error" class="invalid-feedback d-block"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="address-block">
                                                    <div class="row g-md-3 g-2 align-items-end">
                                                        <div class="col-lg-4 col-sm-6">
                                                            <label for="present_addLine1" class="form-label">PRESENT ADDRESS<span class="req_span">*</span></label>
                                                            <input type="text" class="form-control" id="present_addLine1" name="present_addLine1"
                                                                placeholder="Address Line 1" required>
                                                        </div>
                                                        <div class="col-lg-4 col-sm-6">
                                                            <input type="text" class="form-control" name="present_addLine2" placeholder="Address Line 2" >
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
                                                            <input type="number" class="form-control" placeholder="Postal Code" name="present_postal_code" required
                                                                data-parsley-required-message="Postal code is required."
                                                                data-parsley-type="digits"
                                                                data-parsley-type-message="Please enter a valid 5-digit postal code."
                                                                data-parsley-pattern="^\d{5}$"
                                                                data-parsley-pattern-message="Postal code must be exactly 5 digits.">
                                                        </div>
                                                        <div class="col-lg-4 col-sm-6">
                                                            <select class="form-select select2t-none" data-placeholder="Country" 
                                                            name="present_country" required
                                                            data-parsley-errors-container="#Country_error_one"
                                                            data-parsley-error-message="Please Select Blood Group">
                                                                <option></option>
                                                                @foreach($countries as $country)
                                                                    <option value="{{ $country}}">{{ $country }}</option>
                                                                @endforeach
                                                            </select>
                                                            
                                                            <div id="Country_error_one" class="invalid-feedback d-block"></div>

                                                        </div>
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
                                    <label for="emg_name" class="form-label">First Name<span class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="emg_first_name" name="emg_contact_fname" placeholder="First Name" required 
                                        data-parsley-pattern="^[a-zA-Z\s]+$" 
                                        data-parsley-required-message="First name is required." 
                                        data-parsley-pattern-message="Only letters are allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_name" class="form-label">Last Name<span class="req_span">*</span></label>
                                    <input type="text" class="form-control" id="emg_last_name" name="emg_contact_lname" placeholder="Last Name" required 
                                        data-parsley-pattern="^[a-zA-Z\s]+$" 
                                        data-parsley-required-message="Last name is required." 
                                        data-parsley-pattern-message="Only letters are allowed.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_name" class="form-label">Email<span class="req_span">*</span></label>
                                    <input type="email" class="form-control" id="emg_email" name="emg_contact_email" placeholder="Enter Email" required 
                                        data-parsley-type="email" 
                                        data-parsley-required-message="Email is required." 
                                        data-parsley-type-message="Please enter a valid email address.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_number" class="form-label">Contact Number<span class="req_span">*</span></label>
                                    <input type="number" class="form-control" id="emg_number" name="emg_contact_number" placeholder="Number" required 
                                        data-parsley-required-message="Mobile number is required."
                                        data-parsley-mobile_number
                                        data-parsley-mobile_number-message="Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code." >
                                </div>

                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_relation" class="form-label">Relation <span class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="emg_relation" 
                                        name="emg_contact_relation" required aria-label="Default select example"
                                          data-parsley-errors-container="#emg_relation_error_one"
                                          data-parsley-error-message="Please Select Emg Relation">
                                        <option value="" selected disabled>Select Relation</option>
                                        <option value="father">Father</option>
                                        <option value="mother">Mother</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="friend">Friend</option>
                                        <option value="relative">Relative</option>
                                        <option value="other">Other</option>
                                    </select>
                                     <div id="emg_relation_error_one" class="invalid-feedback d-block"></div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="emg_contact_nationality" class="form-label">Nationality <span class="req_span">*</span></label>
                                    <select class="form-select select2t-none" id="emg_contact_nationality" name="emg_contact_nationality"
                                     required aria-label="Default select"
                                        data-parsley-errors-container="#Nationality_error_one"
                                        data-parsley-error-message="Please Select Nationality">
                                        <option value="" selected disabled>Select Nationality</option>
                                          @foreach($nationalitys as $nationality )
                                            <option value="{{$nationality}}">{{$nationality}}</option>
                                        @endforeach
                                    </select>
                                    <div id="Nationality_error_one" class="invalid-feedback d-block"></div>
                                </div>
                                                               
                                <div class="col-12">
                                    <div class="address-block">
                                        <div class="row g-md-3 g-2 align-items-end">
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="emg_add_addLine1" class="form-label">ADDRESS</label>
                                                <input type="text" class="form-control" id="emg_add_addLine1" name="emg_contact_add_addLine1" placeholder="Address Line 1">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <input type="text" class="form-control" name="emg_add_line2" placeholder="Address Line 2">
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
                                                  <input type="number" class="form-control" placeholder="Postal Code" name="emg_cont_postal_code" required
                                                    data-parsley-required-message="Postal code is required."
                                                    data-parsley-type="digits"
                                                    data-parsley-type-message="Please enter a valid 5-digit postal code."
                                                    data-parsley-pattern="^\d{5}$"
                                                    data-parsley-pattern-message="Postal code must be exactly 5 digits.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <select class="form-select select2t-none" name="emg_cont_country"
                                                 data-placeholder="Country" required
                                                 data-parsley-errors-container="#emg_cont_country"
                                                 data-parsley-error-message="Please Select Nationality">
                                                    <option></option>
                                                    @foreach($countries as $country)
                                                        <option value="{{ $country}}">{{ $country }}</option>
                                                    @endforeach
                                                </select>
                                                 
                                      
                                            <div id="emg_cont_country" class="invalid-feedback d-block"></div>
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
                                            <input type="text" class="form-control language-input" placeholder="Language">
                                        </div>
                                        <div class="col-lg-5 col-sm-6">
                                            <label class="form-label">Proficiency Level</label>
                                            <select class="form-select select2t-none proficiency-level-select"
                                                data-parsley-errors-container="#Proficiency_error"
                                                data-parsley-error-message="Please Select Proficiency Level">
                                                <option value="" selected disabled readonly>Select Level</option>
                                                <option value="Beginner">Beginner</option>
                                                <option value="Intermediate">Intermediate</option>
                                                <option value="Advanced">Advanced</option>
                                                <option value="Fluent">Fluent</option>
                                                <option value="Native">Native</option>
                                            </select>
                                            <div id="Proficiency_error" class="invalid-feedback d-block"></div>

                                        </div>
                                        <div class="col-lg-1 col-sm-12 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm blockAdd-btn w-100">Add</a>
                                                <a href="javascript:void(0);" class="btn btn-danger btn-sm remove-btn w-100" style="display:none;">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <hr class="hr-footer">
                            <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset>
                            <div class="mt-2">
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="employee_id" class="form-label">Employee ID </label>
                                        <input type="text" readonly class="form-control" id="employee_id" name="employee_id"
                                            value="{{ $employee_id }}" placeholder="Employee ID">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="email_add_step2" class="form-label">Email Address<span class="req_span">*</span></label>
                                        <input type="text" class="form-control" id="email_add_step2" name="email_add_step2"
                                            placeholder="Email Address" required>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="mobile_num_s2" class="form-label">Mobile Number<span class="req_span">*</span></label>
                                        <input type="text" class="form-control" id="mobile_num_s2" name="mobile_num_s2" required
                                            placeholder="Mobile Number"
                                            data-parsley-required-message="Mobile number is required."
                                            data-parsley-mobile_number
                                            data-parsley-mobile_number-message="Please enter a valid 10-digit mobile number, optionally prefixed with a valid country code.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="division" class="form-label">Division <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="division" name="division"
                                            data-placeholder="Select Division" required
                                              data-parsley-errors-container="#division_error"
                                                data-parsley-error-message="Please Select Proficiency Level">
                                            <option></option>
                                            @foreach($resort_divisions as $devision)
                                                <option value="{{ $devision->id }}">{{ $devision->name }}</option>
                                            @endforeach
                                        </select>
                                       <div id="division_error"></div>

                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="department" class="form-label">Department <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="department" name="department"
                                            data-parsley-errors-container="#Department_error"
                                            data-parsley-error-message="Please Select Department"
                                            data-placeholder="Department" required>
                                            <option></option>
                                        </select>
                                          <div id="Department_error"></div>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="section" class="form-label">Section</label>
                                        <select class="form-select select2t-none" id="section" name="section"
                                            data-placeholder="Section">
                                            <option></option>
                                        </select>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="position" class="form-label">Position <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="position" name="position"
                                            data-placeholder="Position" required
                                            data-parsley-errors-container="#Position_error"
                                            data-parsley-error-message="Please Select Position">
                                            <option></option>
                                        </select>
                                        <div id="Position_error"></div>

                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="benefit_grid_level" class="form-label">Benefit Grid Level<span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="benefit_grid_level" name="benefit_grid_level"
                                            data-placeholder="Benefit Grid Level">
                                            <option></option>
                                        </select>
                                        
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="reporting_person" class="form-label">Reporting Person <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="reporting_person" name="reporting_person"
                                            data-placeholder="Reporting Person" required data-parsley-errors-container="#reporting_person_error"
                                            data-parsley-error-message="Please Select Reporting Person">
                                            <option></option>
                                        </select>
                                         <div id="reporting_person_error"></div>

                                    </div>

                                    <input type="hidden" name="position_rank" id="position_rank" value="">
                                                                
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="joining_date" class="form-label">Joining date<span class="req_span">*</span></label>
                                        <input type="text" class="form-control datepicker" id="joining_date" name="joining_date"
                                            placeholder="Joining date" required autocomplete="off"
                                            data-parsley-required-message="Passport expiry date is required."
                                            data-parsley-pattern="^\d{2}/\d{2}/\d{4}$"
                                            data-parsley-pattern-message="Please enter a valid date in DD/MM/YYYY format.">
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="employment_status" class="form-label">EMPLOYMENT STATUS<span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="employment_status" name="employment_status"
                                            data-placeholder="Employment Status" required
                                            data-parsley-errors-container="#EmployeeStatus_error"
                                            data-parsley-error-message="Please Select Status">
                                            <option></option>
                                            <option value="Full-Time">Full-Time</option>
                                            <option value="Part-Time">Part-Time</option>
                                            <option value="Contract">Contract</option>
                                            <option value="Casual">Casual</option>
                                            <option value="Probationary">Probationary</option>
                                            <option value="Internship">Internship</option>
                                            <option value="Temporary">Temporary</option>
                                        </select>
                                        <div id="EmployeeStatus_error"></div>

                                    </div>
                                

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="probation_exp_date" class="form-label">PROBATION EXP DATE<span class="req_span">*</span></label>
                                        <input type="text" class="form-control datepicker" id="probation_exp_date" name="probation_exp_date"
                                            placeholder="dd/mm/yyyy" disabled>
                                    </div>

                                    <div class="col-lg-4 col-sm-6">
                                        <label for="contract_type" class="form-label">Contract Type</label>
                                        <input type="text" class="form-control" id="contract_type" name="contract_type" placeholder="Enter Contract Type" required>
                                    
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="tin" class="form-label">TIN</label>
                                        <input type="text" class="form-control" id="tin" name="tin" placeholder="TIN">
                                    </div>

                                    
                                </div>
                                <div class="card-title">
                                    <h3>Salary Details</h3>
                                </div>
                                <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="basic_salary" class="form-label">Basic Salary <span class="req_span">*</span></label>
                                        <input type="number" min="1" class="form-control" id="basic_salary" name="basic_salary"
                                            placeholder="Basic Salary" required data-parsley-required-message="Basic salary is required."
                                            data-parsley-type="number" data-parsley-min="1" data-parsley-type-message="Please enter a valid number."
                                            data-parsley-min-message="Basic salary must be at least 1.">
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="currency_type" class="form-label">Currency Type <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="currency_type" name="basic_salary_currency" required
                                            aria-label="Default select example"
                                             data-parsley-errors-container="#currency_type_error"
                                            data-parsley-error-message="Please Select Status">
                                            <option value="MVR">MVR</option>
                                            <option value="USD">USD</option>
                                        </select>
                                        <div id="currency_type_error"></div>

                                        
                                    </div>
                                    
                                    <div class="col-lg-4 col-sm-6">
                                        <label for="payment_mode" class="form-label">PAYMENT MODE <span class="req_span">*</span></label>
                                        <select class="form-select select2t-none" id="payment_mode" name="payment_mode" required
                                            aria-label="Default select example"
                                             data-parsley-errors-container="#payment_mode_error"
                                            data-parsley-error-message="Please Select Status">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank">Bank</option>
                                        </select>
                                        <div id="payment_mode_error"></div>

                                    </div>
                                    
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                        <div class="col-lg-4 col-sm-6">
                                            <label for="pension" class="form-label">Pension</label>
                                            <input type="number" min="0" class="form-control" id="pension" name="pension" placeholder="Pension" 
                                                data-parsley-required="false" 
                                                data-parsley-required-message="Pension is required for Maldivian employees."
                                                data-parsley-min="0"
                                                data-parsley-min-message="Pension must be at least 0."
                                                data-parsley-trigger="change">
                                        </div>
                                        <div class="col-lg-2 col-sm-6">
                                            <label for="ewt_status" class="form-label">EWT STATUS</label>
                                            <div>
                                                <div class="form-check form-switch form-switchTheme switch-blue">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="ewt_status"
                                                        name="ewt_status" >
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
                                                    <input class="form-check-input" type="checkbox" role="switch" id="entitle_switch"
                                                    name="entitle_switch" >
                                                    <label class="form-check-label" for="entitle_switch">Yes</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-sm-6">
                                            <label for="entitle_overtime" class="form-label">ENTITLE FOR OVERTIME</label>
                                            <div>
                                                <div class="form-check form-switch form-switchTheme switch-blue">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="entitle_overtime"
                                                        name="entitle_overtime" >
                                                    <label class="form-check-label" for="entitle_overtime">Yes</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-sm-6">
                                            <label for="entitle_public_holiday_overtime" class="form-label">ENTITLE FOR PUBLIC HOLIDAY
                                                OVERTIME</label>
                                            <div>
                                                <div class="form-check form-switch form-switchTheme switch-blue">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="entitle_public_holiday_overtime" name="entitle_public_holiday_overtime"
                                                        >
                                                    <label class="form-check-label" for="entitle_public_holiday_overtime">Yes</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="allowanceRepeater-main">
                                            <div class="row allowanceRepeater-block g-2 mb-2">
                                                <div class="col-4">
                                                    <label class="form-label">Allowance Type <span class="req_span">*</span></label>
                                                    <select class="form-select select2t-none allowance-type-select" 
                                                    name="allowance[0][type]" required 
                                                    data-parsley-required-message="Allowance type is required."
                                                        data-parsley-errors-container="#allowance-error"
                                                        data-parsley-error-message="Please Select  Allowance Type">
                                                        <option value="">Select Allowance</option>
                                                        @foreach($payrollAllowance as $allowance)
                                                            <option value="{{ $allowance->id }}">{{ $allowance->particulars }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div id="allowance-error"></div>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label">Amount <span class="req_span">*</span></label>
                                                    <input type="number" min="0" class="form-control allowance-amount-input" name="allowance[0][amount]" placeholder="Amount" required
                                                        data-parsley-required-message="Allowance amount is required."
                                                        data-parsley-type="number"
                                                        data-parsley-min="0"
                                                        data-parsley-type-message="Please enter a valid number."
                                                        data-parsley-min-message="Amount must be at least 0.">
                                                </div>
                                                <div class="col-3">
                                                    <label class="form-label">Currency <span class="req_span">*</span></label>
                                                    <select class="form-select select2t-none allowance-currency-select"
                                                     name="allowance[0][currency]" required 
                                                     data-parsley-required-message="Currency is required."
                                                        data-parsley-errors-container="#allowance-currency"
                                                        data-parsley-error-message="Please Select Gender">
                                                        <option value="">Select Currency</option>
                                                        <option value="MVR">MVR</option>
                                                        <option value="USD">USD</option>
                                                    </select>
                                                   <div id="allowance-currency"></div>

                                                </div>
                                                <div class="col-1 d-flex align-items-end">
                                                    <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm allowanceAdd-btn">Add</a>
                                                    <a href="javascript:void(0);" class="btn btn-danger btn-sm allowanceRemove-btn ms-1" style="display:none;">Remove</a>
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
                                            <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm bankAdd-btn">Add</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bankRepeater-main">
                                    <div class="row g-md-3 g-2 mb-md-4 mb-3 bankRepeater-block">
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Bank Name <span class="req_span">*</span></label>
                                            <input type="text" class="form-control bank_name" name="bank[0][bank_name]" placeholder="Bank Name" required
                                                data-parsley-required-message="Bank name is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid bank name.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Bank Location/Branch <span class="req_span">*</span></label>
                                            <input type="text" class="form-control bank_branch" name="bank[0][bank_branch]" placeholder="Bank Location/Branch" required
                                                data-parsley-required-message="Bank branch is required."
                                                data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                data-parsley-pattern-message="Please enter a valid branch name.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Account Type <span class="req_span">*</span></label>
                                            <input type="text" class="form-control account_type" name="bank[0][account_type]" placeholder="Account Type" required
                                                data-parsley-required-message="Account type is required."
                                                data-parsley-pattern="^[a-zA-Z\s]+$"
                                                data-parsley-pattern-message="Please enter a valid account type.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">IFSC/SWIFT/BIC Code <span class="req_span">*</span></label>
                                            <input type="text" class="form-control ifsc" name="bank[0][ifsc]" placeholder="IFSC/SWIFT/BIC Code" required
                                                data-parsley-required-message="IFSC/SWIFT/BIC code is required."
                                                data-parsley-pattern="^[A-Za-z0-9]{6,15}$"
                                                data-parsley-pattern-message="Please enter a valid IFSC/SWIFT/BIC code.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Account Holder's Name <span class="req_span">*</span></label>
                                            <input type="text" class="form-control account_name" name="bank[0][account_name]" placeholder="Account Holder's Name" required
                                                data-parsley-required-message="Account holder's name is required."
                                                data-parsley-pattern="^[a-zA-Z\s\.]+$"
                                                data-parsley-pattern-message="Please enter a valid name.">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Account Number <span class="req_span">*</span></label>
                                            <input type="text" class="form-control account_number" name="bank[0][account_number]" placeholder="Account Number" required
                                                data-parsley-required-message="Account number is required."
                                                data-parsley-pattern="^[0-9]{6,20}$"
                                                data-parsley-pattern-message="Please enter a valid account number (6-20 digits).">
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">Currency <span class="req_span">*</span></label>
                                            <select class="form-select select2t-none currency" name="bank[0][currency]" required
                                                data-parsley-required-message="Currency is required.">
                                                <option value="">Select Currency</option>
                                                <option value="MVR">MVR</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label">IBAN <span class="req_span">*</span></label>
                                            <input type="text" class="form-control iban" name="bank[0][iban]" placeholder="IBAN" required
                                                data-parsley-required-message="IBAN is required."
                                                data-parsley-pattern="^[A-Z0-9]{8,34}$"
                                                data-parsley-pattern-message="Please enter a valid IBAN (8-34 alphanumeric characters, uppercase).">
                                        </div>

                                        <div class="col-lg-4 col-sm-6 d-flex align-items-end">
                                        
                                            <a href="javascript:void(0);" class="btn btn-danger btn-sm bankRemove-btn ms-2" style="display:none;">Remove</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr-footer ">
                            <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        
                        <fieldset>
                            <div class="mt-2">
                                <div class="employeeEducationRepeater-main">
                                    <div class="employeeEducationRepeater-block">
                                        <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                            <div class="col-12 d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label mb-0 fw-bold">Education / Qualification</label>
                                                <div class="d-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm education-add-btn">Add</a>
                                                    <a href="javascript:void(0);" class="btn btn-danger btn-sm education-remove-btn" style="display:none;">Remove</a>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">DOCUMENT UPLOAD / CERTIFICATE ATTACHMENT <span class="req_span">*</span></label>
                                                <div class="uploadFile-block">
                                                    <div class="uploadFile-btn">
                                                        <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                                        <input type="file" class="education-upload-input" name="education[0][document]" required
                                                            data-parsley-required-message="Please upload your education certificate.">
                                                    </div>
                                                    <div class="education-file-name mt-2 text-dark fw-bold"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label class="form-label">Education Level/Type <span class="req_span">*</span></label>
                                                <input type="text" class="form-control education_level education_level_1" name="education[0][education_level]" placeholder="Education Level/Type" required
                                                    data-parsley-required-message="Education level/type is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid education level/type.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label class="form-label">Institution Name <span class="req_span">*</span></label>
                                                <input type="text" class="form-control institutio_name institutio_name_1" name="education[0][institutio_name]" placeholder="Institution Name" required
                                                    data-parsley-required-message="Institution name is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid institution name.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label class="form-label">Field of Study / Major <span class="req_span">*</span></label>
                                                <input type="text" class="form-control field_study field_study_1" name="education[0][field_study]" placeholder="Field of Study / Major" required
                                                    data-parsley-required-message="Field of study/major is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid field of study/major.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label class="form-label">DEGREE/CERTIFICATE EARNED <span class="req_span">*</span></label>
                                                <input type="text" class="form-control degree_earned degree_earned_1" name="education[0][degree_earned]" placeholder="Degree/Certificate Earned" required
                                                    data-parsley-required-message="Degree/Certificate earned is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid degree/certificate.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label class="form-label">Attendance Period <span class="req_span">*</span></label>
                                                <input type="text" class="form-control attendance_period attendance_period_1" name="education[0][attendance_period]"  placeholder="e.g., 2015 - 2019" required
                                                    data-parsley-required-message="Attendance period is required."
                                                    data-parsley-pattern="^(\d{4})\s*-\s*(\d{4})$"
                                                    data-parsley-pattern-message="Please enter attendance period in format: 2015 - 2019">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label class="form-label">Location <span class="req_span">*</span></label>
                                                <input type="text" class="form-control location location_education_1" name="education[0][location]" placeholder="Location" required
                                                    data-parsley-required-message="Location is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid location.">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr-footer ">
                            <a href=" # " class=" btn btn-themeBlue btn-sm float-end next ">Next</a>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset>
                             <div class="mt-2">
                                <div class="employeeProCreationProcessExp-main">
                                    <div class="employeeProCreationProcessExp-block">
                                        <div class="row g-md-3 g-2 mb-md-4 mb-3">
                                            <div class="col-12">
                                                <label class="form-label">DOCUMENT UPLOAD / CERTIFICATE ATTACHMENT <span class="req_span">*</span></label>
                                                <div class="uploadFile-block">
                                                    <div class="uploadFile-btn">
                                                        <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm">Upload File</a>
                                                        <input type="file" class="uploadFile" name="experience[0][document]" required data-parsley-required-message="Please upload your experience certificate.">
                                                    </div>
                                                    <div class="certificate-file-name mt-2 text-center text-dark fw-bold"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="company_name" class="form-label">Company Name <span class="req_span">*</span></label>
                                                <input type="text" class="form-control company_name" name="experience[0][company_name]" placeholder="Company Name" required
                                                    data-parsley-required-message="Company name is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid company name.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="job_title" class="form-label">Job Title / Position <span class="req_span">*</span></label>
                                                <input type="text" class="form-control job_title" name="experience[0][job_title]" placeholder="Job Title / Position" required
                                                    data-parsley-required-message="Job title is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid job title.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="employment_type" class="form-label">Employment Type <span class="req_span">*</span></label>
                                                <input type="text" class="form-control employment_type" name="experience[0][employment_type]" placeholder="Employment Type" required
                                                    data-parsley-required-message="Employment type is required."
                                                    data-parsley-pattern="^[a-zA-Z\s]+$"
                                                    data-parsley-pattern-message="Please enter a valid employment type.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="duration_employment" class="form-label">Duration of Employment <span class="req_span">*</span></label>
                                                <input type="text" class="form-control duration_employment" name="experience[0][duration]" placeholder="Duration ex: 01/2015 - 01/2019" required
                                                    data-parsley-required-message="Duration of employment is required."
                                                    data-parsley-pattern="^(\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{4})$"
                                                    data-parsley-pattern-message="Please enter duration in format: MM/YYYY - MM/YYYY">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="location1" class="form-label">Location <span class="req_span">*</span></label>
                                                <input type="text" class="form-control location1" name="experience[0][location]" placeholder="Location" required
                                                    data-parsley-required-message="Location is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid location.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="reason_leaving" class="form-label">Reason for Leaving <span class="req_span">*</span></label>
                                                <input type="text" class="form-control reason_leaving" name="experience[0][reason_for_leaving]" placeholder="Reason for Leaving" required
                                                    data-parsley-required-message="Reason for leaving is required."
                                                    data-parsley-pattern="^[a-zA-Z0-9\s\.\-&]+$"
                                                    data-parsley-pattern-message="Please enter a valid reason.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="reference_name" class="form-label">Reference Name <span class="req_span">*</span></label>
                                                <input type="text" class="form-control reference_name" name="experience[0][reference_name]" placeholder="Reference Name" required
                                                    data-parsley-required-message="Reference name is required."
                                                    data-parsley-pattern="^[a-zA-Z\s\.]+$"
                                                    data-parsley-pattern-message="Please enter a valid name.">
                                            </div>
                                            <div class="col-lg-4 col-sm-6">
                                                <label for="reference_contact" class="form-label">Reference Contact <span class="req_span">*</span></label>
                                                <input type="text" class="form-control reference_contact" name="experience[0][reference_contact]" placeholder="Reference Contact" required
                                                    data-parsley-pattern="^\d{10,15}$"
                                                    data-parsley-required-message="Reference contact is required."
                                                    data-parsley-pattern-message="Please enter a valid contact number (10-15 digits).">
                                            </div>
                                            <div class="col-12">
                                                <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm blockAdd-btn">Add More</a>
                                                <a href="javascript:void(0);" class="btn btn-danger btn-sm remove-btn" style="display:none;">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="MergerFile" id="MergerFile">
                        </div>
                            <hr class="hr-footer ">
                            <button type="submit" class="btn btn-themeBlue btn-sm float-end " style="margin-right: 10px;" id="SubmitVisaform">Submit</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                    </form>
                </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js"></script>
<script> pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
<script>
    const documentTypes = {!! json_encode($documentTypes) !!};
    let selectFiles = [];

    $(document).ready(function() 
    {
        initSelect2AndValidation();
        var current_fs, next_fs, previous_fs; //fieldsets
        var opacity;
        var current = 1;
        var steps = $("fieldset").length;

        // setProgressBar(current);

        $("#date_birth").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        
        $("#db_dob").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        
        $("#Passport_expiry_date").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $("#step2_passport_expiry_date").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $("#Passport_issueDate").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        
        
        $("#joining_date").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $('#department').select2({
            width: '100%',
            placeholder: 'Select Department'
        });

        $('#division').select2({
            width: '100%',
            placeholder: 'Select Division'
        });
        $('#section').select2({
            width: '100%',
            placeholder: 'Select Section'
        });
        $('#position').select2({
            width: '100%',
            placeholder: 'Select Position'
        });

        
        $(document).on("click", "#SubmitVisaform", function(e) 
        {
            e.preventDefault();
            var formData = $('#msform').serialize();
            $.ajax({
                url: '{{ route("resort.visa.CreateEmployee") }}',
                type: 'POST',
                data: formData,
                processData: true,        
                contentType: 'application/x-www-form-urlencoded', // ✅ this is default for serialize()
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if(response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // reload the page
                        location.reload();
                    }else{
                        toastr.error(response.message || "An error occurred", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                   toastr.error(xhr.responseJSON.message || "An error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
        ////////////////////////////////////////////////////////////////////////////////Next Move ///////////////////////////////////////////
        
            $(".next").click(function(e) {
                    e.preventDefault();
                    const current_fs = $(this).parent();          // current fieldset
                    const next_fs    = current_fs.next();         // next fieldset
                    const currentIdx = $("fieldset").index(current_fs); // 0-based step index
                    let $currentFieldset = $(this).closest("fieldset");
                   
                    // Skip validation for first step (index 0) and second step (index 1)
                    if (currentIdx > 1) {
                        // Validate current step for steps 3 and onwards
                        let isValid = true;
                        
                        $currentFieldset.find("input, select, textarea").each(function() {
                            if ($(this).parsley().validate() !== true) {
                                isValid = false;
                            }
                        });
                        
                        // If validation fails, don't proceed to next step
                        if (!isValid) {
                            return;
                        }
                    }
                    
                    // Optional: Serialize form data for current step
                    // stepData = $currentFieldset.find("input, select, textarea").serialize();
                    // stepData += "&step=" + $currentFieldset.data("step");
                    
                    // Proceed to next step if validation passes or if it's step 1 or 2
                    if (currentIdx == 0  || currentIdx ==1 ) 
                    {
                      if (currentIdx === 0) 
                      {
                            const consolidatedFileSelected = $('#consolidatedFile')[0].files.length > 0;
                            const separateFilesSelected = $('#saperateFile')[0].files.length > 0;

                            if (!consolidatedFileSelected && !separateFilesSelected)
                            {
                                $('.file-error-message').html('Please select at least one file before proceeding.');
                                return false;
                            }

                            $('.file-error-message').html('');

                            if (consolidatedFileSelected) {
                                const file = $('#consolidatedFile')[0].files[0];
                                $("#MergerFile").val(file);                    
                                processPdfWithPdfLib(file); // async file handling
                                return false;
                            } 
                            else if (separateFilesSelected)
                            {
                                const selectedFiles = Array.from($('#saperateFile')[0].files);
                                if (selectedFiles.length > 0) 
                                {
                                    $('.file-error-message').html('Combining multiple files into one PDF...');
                                    
                                    // Create a new PDF document
                                    PDFLib.PDFDocument.create().then(async (mergedPdf) => {
                                        try {
                                            // Process each file and add to the merged PDF
                                            for (let i = 0; i < selectedFiles.length; i++) {
                                                const file = selectedFiles[i];
                                                $('.file-error-message').html(`Processing file ${i+1} of ${selectedFiles.length}...`);
                                                
                                                // Read the file as array buffer
                                                const arrayBuffer = await file.arrayBuffer();
                                                
                                                // Load the PDF
                                                const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
                                                
                                                // Copy all pages to the merged PDF
                                                const pages = await mergedPdf.copyPages(pdfDoc, pdfDoc.getPageIndices());
                                                pages.forEach(page => mergedPdf.addPage(page));
                                            }
                                            
                                            const mergedPdfBytes = await mergedPdf.save();
                                            const mergedBlob = new Blob([mergedPdfBytes], { type: 'application/pdf' });
                                            const mergedFile = new File([mergedBlob], 'merged_documents.pdf', { type: 'application/pdf' });
                                            
                               
                                            $('.file-error-message').html('Processing to PDF...');
                                            processPdfWithPdfLib(mergedFile);
                                                 $("#MergerFile").val(mergedFile);                    

                                        } catch (error) {
                                            $('.file-error-message').html(`Error combining files: ${error.message}`);
                                        }
                                    });
                                } else {
                                    $('.file-error-message').html('Please select at least one file to proceed.');
                                }
                                                                    
                                return false;
                            } 
                            else 
                            {
                                $('.file-error-message').html('Please select a file to proceed.');
                                return false;
                            }

                        }

                        // Step 1: Document Segregation
                        if (currentIdx === 1) {
                            let docTypeValid = true;
                            let hasPassport = false;
                            let hasPhoto = false;

                            $("fieldset:eq(1) select[name^='docType']").each(function () {
                                const selectedType = $(this).find('option:selected').text().toLowerCase();
                                if (!$(this).parsley().isValid()) {
                                    $(this).parsley().validate();
                                    docTypeValid = false;
                                }

                                if (selectedType.includes('passport main')) hasPassport = true;
                                if (selectedType.includes('photo')) hasPhoto = true;
                            });

                            if (!hasPassport || !hasPhoto) {
                                $('.doctype-error-message').html('Passport and Photo document are required.');
                                return false;
                            } else {
                                $('.doctype-error-message').html('');
                            }

                            if (!docTypeValid) return false;

                               
                            // Run all async checks in parallel
                            Promise.all([
                                verifyPassportDPI(),
                                verifyPhotoBackground(),
                                PassportExpiry(),
                                Cvcheck(),
                                EducationCheck(),
                                ExperienceCheck(),

                            ])
                            .then(([dpiResult, photoResult, expiryResult, cv]) => 
                            {
                                if (!dpiResult.success || !photoResult.success) return;
                                // Fill CV extracted data
                                if (cv.status && cv.data) {
                                    if (cv.data.first_name && cv.data.first_name !== "Unavailable")
                                    {
                                        $("#txt-first-name").val(cv.data.first_name || "");
                                        $("#employeeF_name").val(cv.data.first_name || "");
                                    }
                                    if (cv.data.middle_name && cv.data.middle_name !== "Unavailable")
                                    {
                                        $("#txt-middle-name").val(cv.data.middle_name || "");
                                    }
                                    if (cv.data.last_name && cv.data.last_name !== "Unavailable")
                                    {
                                        $("#txt-last-name").val(cv.data.last_name || "");
                                        $("#employeeL_name").val(cv.data.last_name || "");
                                    }
                                    if (cv.data.address && cv.data.address !== "Unavailable")
                                    {
                                        $("#address").val(cv.data.address || "");
                                    }
                                    if (cv.data.dob && cv.data.dob !== "Unavailable") 
                                    {
                                        var dob= cv.data.dob .split('-');
                                        dob  = `${dob[2]}/${dob[1]}/${dob[0]}`;
                                        $("#db_dob").val(dob);
                                        $("#date_birth").val(dob);
                                    }
                                    $("#email_add_step2").val(cv.data.email || "");
                                    $("#first_email_address").val(cv.data.email || "");
                                    
                                    $("#mobile_num, #mobile_num_s2").val(cv.data.phone_no || "");
                                    if (cv.data.nationality && cv.data.nationality !== "Unavailable")
                                    {
                                        $("#nationality").val(cv.data.nationality).trigger('change');
                                    }
                                    if (cv.data.religion && cv.data.religion !== "Unavailable")
                                    {
                                        $("#religion").val(cv.data.religion).trigger('change');
                                    }
                                    // if (cv.data.blood group && cv.data.blood group !== "Unavailable")
                                    // {
                                    //     let bloodgroup = cv.data.blood group.replace(/\s+/g, "");
                                    //     $("#blood_group").val(bloodgroup).trigger('change');
                                    // }
                                    
                                }

                                const photoListItem = $("fieldset:eq(2)").find('li:contains("Please Wait passport is verifying.")');
                                if (photoListItem.length) 
                                {
                                    // Convert date format from YYYY-MM-DD to DD/MM/YYYY
                                    if (expiryResult.expiryDate) {
                                        var formattedDate = expiryResult.expiryDate;
                                        
                            
                                            const dateParts = expiryResult.expiryDate.split('-');
                                            formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                                            console.log(dateParts);

                                         var issue_date = expiryResult.issue_date;
  
                                        $("#Passport_issueDate").val(issue_date || "");                                        
                                        $("#passport_numb").val(expiryResult.passportno || "");
                                        $("#txt-passport-number").val(expiryResult.passportno || "");
                                        $("#Passport_expiry_date").val(formattedDate);
                                        $("#step2_passport_expiry_date").val(formattedDate);
                                    }
                                    $("#passport_numb").val(expiryResult.passportno || "");
                                    $("#txt-passport-number").val(expiryResult.passportno || "");
                                    $("#Passport_expiry_date").val(formattedDate || "");
                                    if (expiryResult.status === "VALID") 
                                    {
                                        photoListItem.html(`✅ Passport validity verified: Valid until ${formattedDate}`);
                                    } 
                                    else if (expiryResult.status === "NOT VALID")
                                    {
                                        photoListItem.html(`❌ Passport validity verified: Not valid (expires ${formattedDate})`);
                                    }
                                    else 
                                    {
                                        photoListItem.html(`⚠ Passport status unclear. Please check manually.`);
                                    }
                                }

                                $(".next").removeAttr("disabled");
                                moveToNextStep(current_fs, next_fs);
                            });
                            return false;
                        }
                    }
                      moveToNextStep(current_fs, next_fs);
                });

    



        $(".previous").click(function () {

            current_fs = $(this).parent();
            previous_fs = $(this).parent().prev();

            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("current");
            $("#progressbar li").eq($("fieldset").index(previous_fs)).addClass("current");
            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

            //show the previous fieldset
            previous_fs.show();
            current_fs.animate({ opacity: 0 }, {
                step: function (now) {
                    opacity = 1 - now;
                    current_fs.css({
                        'display': 'none',
                        'position': 'relative'
                    });
                    previous_fs.css({ 'opacity': opacity });
                },
                duration: 500
            });
            // setProgressBar(--current);
        });

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

        $("<style>")
            .prop("type", "text/css")
            .html(`
                .opacity-50 {
                    opacity: 0.5;
                }
            `)
            .appendTo("head");

           $('#consolidatedFile, #saperateFile').removeAttr('required').attr('data-parsley-required', false); 
        
        // Add custom validation to ensure at least one is selected
        window.Parsley.addValidator('atleastOneFile', {
            requirementType: 'string',
            validateString: function() {
                return $('#consolidatedFile').val() || $('#saperateFile').val();
            },
            messages: {
                en: 'Please upload either a consolidated file or separate files'
            }
        });
        
        $('<input type="hidden" data-parsley-atleastOneFile id="fileValidation">').appendTo('#msform');

            $(document).on('click', '.employeeLanguageRepeater-main .blockAdd-btn', function (e) 
            {
                e.preventDefault();
                const $main = $('.employeeLanguageRepeater-main');
                const $firstBlock = $main.find('.employeeLanguageRepeater-block').first();

                $firstBlock.find('select.select2t-none').each(function () {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                const $newBlock = $firstBlock.clone();
                $newBlock.find('.select2-container').remove();
                $newBlock.find('select.select2t-none').removeAttr('data-select2-id').removeAttr('aria-hidden').show();

                // Reset inputs
                $newBlock.find('input[type="text"]').val('');
                $newBlock.find('select').prop('selectedIndex', 0);

                $main.append($newBlock);

                updateSkillNames();
                toggleRemoveButtons();

                // Re-init select2
                $newBlock.find('select.select2t-none').select2({ minimumResultsForSearch: Infinity, width: '100%' });
                $firstBlock.find('select.select2t-none').select2({ minimumResultsForSearch: Infinity, width: '100%' });
            });

            // Remove block
            $(document).on('click', '.employeeLanguageRepeater-main .remove-btn', function (e) {
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


            $(document).on('click', '.allowanceAdd-btn', function (e)
            {
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
                $newBlock.find('.allowance-amount-input, .allowance-currency-select').on('input change', toggleEwtActivity);
            });

            toggleEwtActivity();

            $(document).on('click', '.bankAdd-btn', function (e) {
                e.preventDefault();

                const $first = $('.bankRepeater-block').first();
                const $new = $first.clone();

                // Reset inputs
                $new.find('input[type="text"]').val('');
                $new.find('select').val('').trigger('change');

                // Remove select2 artifacts
                $new.find('.select2-container').remove();
                $new.find('select.select2t-none').removeAttr('data-select2-id').removeAttr('aria-hidden').show();

                $('.bankRepeater-main').append($new);

                updateBankNames();
                updateBankButtons();
                reinitSelect2($new);
            });
     

            $(document).on('click', '.allowanceRemove-btn', function (e) {
                e.preventDefault();
                $(this).closest('.allowanceRepeater-block').remove();
                updateAllowanceButtons();
                updateAllowanceFieldNames();
                toggleEwtActivity();
            });

           
            $(document).on('click', '.bankRemove-btn', function (e) {
                e.preventDefault();
                $(this).closest('.bankRepeater-block').remove();
                updateBankNames();
                updateBankButtons();
            });


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
                $('#division').on('change', function() {
                    let divisionId = $(this).val();
                    $('#department').html('<option></option>').trigger('change');
                    $('#section').html('<option></option>').trigger('change');
                    $('#position').html('<option></option>').trigger('change');
                    if (!divisionId) return;
                    $.ajax({
                        url: '{{ route("people.getDepartmentsByDivision") }}',
                        type: 'GET',
                        data: { division_id: divisionId },
                        success: function(res) {
                            let html = '<option></option>';
                            $.each(res.departments, function(_, department) {
                                html += `<option value="${department.id}">${department.name}</option>`;
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
                url: '{{ route("people.getSectionByDepartment") }}',
                type: 'GET',
                data: { department_id: departmentId },
                success: function(res) {
                    let html = '<option></option>';
                    if (res.sections.length > 0) {
                        $.each(res.sections, function(_, section) {
                            html += `<option value="${section.id}">${section.name}</option>`;
                        });
                        $('#section').html(html).trigger('change');
                    } else {
                        // No sections, load positions directly
                        loadPositions({ department_id: departmentId });
                    }
                }
            });
        });

        // Section -> Position
        $('#section').on('change', function() {
            let sectionId = $(this).val();
            $('#position').html('<option></option>').trigger('change');
            if (!sectionId) return;
            loadPositions({ section_id: sectionId });
        });
            $('#position').on('change', function() {
        
                let positionId = $(this).val();
                $('#benefit_grid_level').html('<option></option>').trigger('change');
                if (!positionId) return;
                $.ajax({
                    url: '{{ route("people.getBenefitGridByPosition") }}',
                    type: 'GET',
                    data: { position_id: positionId },
                    success: function(res) {
                        console.log(res);
                        let html = '<option></option>';
                        html += `<option value="${res.benfitGrid_emp_id}" selected>${res.emp_grade_name}</option>`;
                        $('#entitle_switch').prop('checked', res.service === 'yes');
                        $('#entitle_public_holiday_overtime').prop('checked', res.holiday_overtime === 'yes');
                        $('#entitle_overtime').prop('checked', res.overtime === 'yes');
                        $('#position_rank').val(res.position_rank);
                        $('#benefit_grid_level').html(html).trigger('change');
                    }
                });
            });
       
        
        $(document).on('click', '.education-remove-btn', function(e) {
            e.preventDefault();
            $(this).closest('.employeeEducationRepeater-block').remove();
            updateEducationButtons();
            updateEducationFieldNames();
        });

            
    });
    // Move to next step utility
    function moveToNextStep(current_fs, next_fs) 
    {
        $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("current");
        $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active current");

        next_fs.show();
        current_fs.animate({ opacity: 0 }, {
            step: function (now) {
                const opacity = 1 - now;
                current_fs.css({ 'display': 'none', 'position': 'relative' });
                next_fs.css({ 'opacity': opacity });
            },
            duration: 500
        });
    }

    function loadPositions(params) 
    {
        $.ajax({
            url: '{{ route("people.getPositionBySection") }}',
            type: 'GET',
            data: params,
            success: function(res) {
                let html = '<option></option>';
                $.each(res.positions, function(_, position) {
                    html += `<option value="${position.id}">${position.position_title}</option>`;
                });
                $('#position').html(html).trigger('change');
            }
        });
    }

     function getReportingPerson(departmentId) {
            $.ajax({
            url: '{{ route("people.getReportingPerson") }}',
            type: 'GET',
            data: { department_id: departmentId },
            success: function(res) {
                let html = '<option></option>';
                $.each(res.data, function(_, person) {
                
                    let displayName = '';
                    if (person.first_name || person.last_name) {
                        displayName = (person.first_name ? person.first_name : '') + ' ' + (person.last_name ? person.last_name : '');
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
    function updateEducationButtons() 
    {
        const $blocks = $('.employeeEducationRepeater-block');
        $blocks.each(function (i, block) {
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
    function updateEducationFieldNames() 
    {
        $('.employeeEducationRepeater-block').each(function (i, block) {
            $(block).find('.education-upload-input').attr('name', `education[${i}][document]`);
            $(block).find('.education_level').attr('name', `education[${i}][education_level]`);
            $(block).find('.institutio_name').attr('name', `education[${i}][institutio_name]`);
            $(block).find('.field_study').attr('name', `education[${i}][field_study]`);
            $(block).find('.degree_earned').attr('name', `education[${i}][degree_earned]`);
            $(block).find('.attendance_period').attr('name', `education[${i}][attendance_period]`);
            $(block).find('.location').attr('name', `education[${i}][location]`);
        });
    }
    function toggleEwtActivity() 
    {
        var salary = parseFloat($('#basic_salary').val()) || 0;
        var salaryCurrency = $('#currency_type').val();
        var thresholdMvr = 30000;
        var usdToMvr = 15.42; // 1 USD = 15.42 MVR (approx, update as needed)

        // Convert salary to MVR if needed
        var salaryMvr = salaryCurrency === 'USD' ? salary * usdToMvr : salary;

        // Sum all allowance amounts, convert each to MVR if needed
        var totalAllowanceMvr = 0;
        $('.allowanceRepeater-block').each(function () 
        {
            var amount = parseFloat($(this).find('.allowance-amount-input').val()) || 0;
            var allowanceCurrency = $(this).find('.allowance-currency-select').val();
            if (allowanceCurrency === 'USD') 
            {
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
    window.Parsley.addValidator('fileextension', {
        requirementType: 'string',
        validateString: function(value, requirement, parsleyInstance) {
            const fileInput = parsleyInstance.$element[0];
            const allowedExtensions = requirement.split(',');

            if (!fileInput.files.length) return false;

            for (let i = 0; i < fileInput.files.length; i++) {
                const file = fileInput.files[i];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(ext)) return false;
            }
            return true;
        },
        messages: {
            en: 'Invalid file extension.'
        }
    });

    function populateDocumentSegregation(files) {
        const documentContainer = $("fieldset:eq(1) .row.g-lg-4.g-3");
        documentContainer.empty();

        files.forEach((file, index) => {
            const fileUrl = file.pdfUrl || URL.createObjectURL(file);
            const fileName = file.name;

            const documentBox = `
                <div class="col-lg-3 col-sm-6 mb-4">
                    <div class="document-box" style="border: 1px solid #ddd; border-radius: 8px; padding: 12px; height: 100%; overflow: hidden;">
                        <div class="img-box" style="height: 220px; overflow: hidden; margin-bottom: 10px; border: 1px solid #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                            <iframe src="${fileUrl}" style="width: 100%; height: 100%; border: none; max-width: 100%;"></iframe>
                        </div>
                        <p class="text-truncate mb-2" title="${fileName}" style="font-size: 14px; max-width: 100%;">${fileName}</p>
                        <div class="border-top pt-2" style="max-width: 100%;">
                            <label for="docType_${index}" class="form-label">DOCUMENT TYPE</label>
                            <select class="form-select select2t-none" id="docType_${index}" name="docType[${index}]" data-parsley-required="true"
                            data-parsley-required-message="Please select a document type for this file"
                            data-parsley-errors-container="#docTypeError_${index}">
                                <option value="">Select document type</option>
                                ${documentTypes.map(type => `<option value="${type.id}">${type.documentname}</option>`).join('')}
                            </select>
                            <div id="docTypeError_${index}" class="parsley-errors-list error"></div>
                        </div>
                    </div>
                </div>
            `;
            documentContainer.append(documentBox);
        });

        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2t-none').select2({
                width: '100%'
            }).on('select2:close', function () {
                $(this).parsley().validate();
            });
        }
    }
    
    async function processPdfWithPdfLib(file)
    {
        
        try 
        {
            const arrayBuffer = await file.arrayBuffer();
            
            // Load the PDF document with pdf-lib
            const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);
            const totalPages = pdfDoc.getPageCount();
            
            $('.file-error-message').html(`Extracting ${totalPages} pages as individual PDFs...`);
            
            selectedFiles = [];
            let processedPages = 0;
            
            // Process each page
            for (let i = 0; i < totalPages; i++) 
            {
                // Create a new document with just this page
                const newPdfDoc = await PDFLib.PDFDocument.create();
                const [copiedPage] = await newPdfDoc.copyPages(pdfDoc, [i]);
                newPdfDoc.addPage(copiedPage);
                
                // Save the individual page as PDF
                const pdfBytes = await newPdfDoc.save();
                
                // Convert to blob and create URL
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                
                // Create page object
                const pageObj = {
                    id: i + 1,
                    name: `Page ${i + 1} - ${file.name}`,
                    pdfUrl: url,
                    page_number: i + 1,
                    original_name: file.name
                };
                
                selectedFiles.push(pageObj);
                processedPages++;
                
                $('.file-error-message').html(`Processed ${processedPages} of ${totalPages} pages...`);
                
                // When all pages are processed, proceed to next step
                if (processedPages === totalPages) {
                    $('.file-error-message').html('');
                    
                    // Go to next fieldset
                    current_fs = $("fieldset").eq(0);
                    next_fs = $("fieldset").eq(1);
                    
                    $("#progressbar li").eq(0).removeClass("current");
                    $("#progressbar li").eq(1).addClass("active current");
                    
                    populateDocumentSegregation(selectedFiles);
                    
                    next_fs.show();
                    current_fs.animate({ opacity: 0 }, {
                        step: function(now) {
                            opacity = 1 - now;
                            current_fs.css({
                                'display': 'none',
                                'position': 'relative'
                            });
                            next_fs.css({ 'opacity': opacity });
                        },
                        duration: 500
                    });
                }
            }
        } catch (error) {
            $('.file-error-message').html(`Error processing PDF: ${error.message}`);
        }
    }
    async function processSelectedSeprateFiles(selectedFiles) 
    {
        let selectedSeprateFiles = [];

        for (let fileIndex = 0; fileIndex < selectedFiles.length; fileIndex++)
        {
            const file = selectedFiles[fileIndex];
            const arrayBuffer = await file.arrayBuffer();
            const pdfDoc = await PDFLib.PDFDocument.load(arrayBuffer);

            const totalPages = pdfDoc.getPageCount();

            for (let pageIndex = 0; pageIndex < totalPages; pageIndex++) 
            {
                const newPdf = await PDFLib.PDFDocument.create();
                const [page] = await newPdf.copyPages(pdfDoc, [pageIndex]);
                newPdf.addPage(page);

                const pdfBytes = await newPdf.save();
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                selectedSeprateFiles.push({
                    id: `${fileIndex + 1}-${pageIndex + 1}`,
                    name: `Page ${pageIndex + 1} - ${file.name}`,
                    pdfUrl: url,
                    page_number: pageIndex + 1,
                    original_name: file.name
                });
            }
        }

                            populateDocumentSegregation(selectedSeprateFiles);

            next_fs.show();
            current_fs.animate({ opacity: 0 }, {
                step: function(now) {
                    opacity = 1 - now;
                    current_fs.css({
                        'display': 'none',
                        'position': 'relative'
                    });
                    next_fs.css({ 'opacity': opacity });
                },
                duration: 500
            });

    }


    let selectedFiles = [];

    $('#saperateFile').on('change', function () {
        selectedFiles = Array.from(this.files);

        if (selectedFiles.length > 0) {
            $('#consolidatedFile').attr('disabled', true).closest('.card').addClass('opacity-50');
            $('.file-error-message').html('');

            // Generate a pdfUrl for each file
            selectedFiles = selectedFiles.map(file => {
                file.pdfUrl = URL.createObjectURL(file);
                return file;
            });

            // Display selected files in fieldset 1
            let fileContainer = $(this).next('.selected-files');
            if (!fileContainer.length) {
                fileContainer = $('<div class="selected-files mt-3" style="border-radius: 4px;"></div>');
                $(this).after(fileContainer);
            }

            fileContainer.empty();
            selectedFiles.forEach((file) => {
                fileContainer.append(`
                    <div class="selected-file py-2 px-3 mb-2" style="border-radius: 4px; border: 1px dashed #ccc; display: flex; align-items: center;">
                        <i class="fa fa-file-pdf-o text-danger me-2"></i>
                        <span class="file-name">${file.name}</span>
                    </div>
                `);
            });
        } else {
            $('#consolidatedFile').attr('disabled', false).closest('.card').removeClass('opacity-50');
            $(this).next('.selected-files').remove();
        }
    });

    $('#consolidatedFile').on('change', function() {
        // Check if a file is selected and Disable separate file option
        if($(this).val()) {
            $('#saperateFile').attr('disabled', true)
                .removeAttr('required')
                .attr('data-parsley-required', false)
                .closest('.card').addClass('opacity-50');
                
            $(this).attr('required', true)
                .attr('data-parsley-required', true);
            $('.file-error-message').html('');
        } else {
            $('#saperateFile').attr('disabled', false)
                .closest('.card').removeClass('opacity-50');
        }

        // set the file container to display the selected file
        let fileContainer = $(this).next('.selected-files');
        if (!this.files.length) {
            if (fileContainer.length) {
                fileContainer.remove();
            }
            return;
        }
        
        if (!fileContainer.length) {
            fileContainer = $('<div class="selected-files mt-2" style="max-height: 100px; min-height: 20px; border: 1px dashed #ccc; padding: 5px; border-radius: 4px;"></div>');
            $(this).after(fileContainer);
        }
        
        fileContainer.empty();
        const fileName = this.files[0].name;
        fileContainer.append(
            `<div class="selected-file p-1">
                <i class="fa fa-file-pdf-o text-danger me-1"></i>
                <span class="file-name">${fileName}</span>
            </div>`
        );
    });

    async function verifyPassportDPI() {
        try {

            const passportFiles = [];
            $("fieldset:eq(1) select[name^='docType']").each(function (index) {
                const selectedType = $(this).find('option:selected').text().toLowerCase();
                if (selectedType.includes('passport')) {
                    const file = selectedFiles[index];
                    if (file) {
                        passportFiles.push(file);
                    }
                }
            });

            if (passportFiles.length === 0) {
                return { success: false, message: "No passport documents found" };
            }

            const passportListItem = $("fieldset:eq(2)").find('li:contains("Passport copy converted to 200 DPI")');
            passportListItem.html('Combining passport documents...');

            // Combine passport documents if there are multiple
            const combinedPassport = passportFiles.length > 1 ? await combinePassportDocuments(passportFiles) : passportFiles[0];
            const dpiInfo = await checkDocumentDPI(combinedPassport);

            if (dpiInfo.dpi !== 200) {
                passportListItem.html(`Converting passport from ${dpiInfo.dpi} DPI to exactly 200 DPI...`);
                const conversionResult = await convertDocumentToExact200DPI(combinedPassport);

                if (conversionResult.success) {
                    passportListItem.html('Passport copy converted to 200 DPI');
                } else {
                    passportListItem.html('<i class="fa fa-exclamation-circle text-danger me-2"></i> Error converting passport: ' + conversionResult.message);
                    return { success: false, message: conversionResult.message };
                }
            } else {
                passportListItem.html('Passport copy is already in 200 DPI');
            }

            return { success: true, message: "Passport DPI verified" };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    async function combinePassportDocuments(passportFiles) {
        const mergedPdf = await PDFLib.PDFDocument.create();

        for (const file of passportFiles) {
            const pdfBytes = await fetchFileBytes(file);
            const pdf = await PDFLib.PDFDocument.load(pdfBytes);
            const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices());
            copiedPages.forEach(page => mergedPdf.addPage(page));
        }

        const mergedBytes = await mergedPdf.save();
        const blob = new Blob([mergedBytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);

        return { pdfUrl: url, name: "Merged_Passport.pdf" };
    }

    async function checkDocumentDPI(document) {

        const pdfBytes = await fetchFileBytes(document);
        const pdfDoc = await PDFLib.PDFDocument.load(pdfBytes);
        const firstPage = pdfDoc.getPage(0);

        const widthInPoints = firstPage.getWidth();
        const heightInPoints = firstPage.getHeight();
        const widthInInches = widthInPoints / 72;
        const heightInInches = heightInPoints / 72;

        const dpiWidth = widthInPoints / widthInInches;
        const dpiHeight = heightInPoints / heightInInches;

        return { dpi: Math.round((dpiWidth + dpiHeight) / 2) };
    }

    async function convertDocumentToExact200DPI(document) {
        const pdfBytes = await fetchFileBytes(document);
        const pdfDoc = await PDFLib.PDFDocument.load(pdfBytes);
        const newPdfDoc = await PDFLib.PDFDocument.create();

        const totalPages = pdfDoc.getPageCount();
        for (let i = 0; i < totalPages; i++) {
            const [copiedPage] = await newPdfDoc.copyPages(pdfDoc, [i]);
            const originalWidth = copiedPage.getWidth();
            const originalHeight = copiedPage.getHeight();

            const currentDPI = Math.min(originalWidth / (originalWidth / 72), originalHeight / (originalHeight / 72));
            const scaleFactor = 200 / currentDPI;

            copiedPage.setWidth(originalWidth * scaleFactor);
            copiedPage.setHeight(originalHeight * scaleFactor);
            newPdfDoc.addPage(copiedPage);
        }

        const convertedBytes = await newPdfDoc.save();
        const blob = new Blob([convertedBytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);

        return { success: true, pdfUrl: url, message: "Document converted to 200 DPI successfully" };
    }

    async function fetchFileBytes(file) {
        if (file instanceof File || file instanceof Blob) {
            return await file.arrayBuffer();
        } else if (file.pdfUrl) {
            const response = await fetch(file.pdfUrl);
            return await response.arrayBuffer();
        } else if (file.dataUrl) {
            const base64Data = file.dataUrl.split(',')[1];
            const byteString = atob(base64Data);
            const ab = new ArrayBuffer(byteString.length);
            const ia = new Uint8Array(ab);
            for (let i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return ab;
        } else {
            throw new Error("Unsupported file format");
        }
    }

    async function verifyPhotoBackground() {
        try {
            const photoFiles = [];
            $("fieldset:eq(1) select[name^='docType']").each(function (index) {
                const selectedType = $(this).find('option:selected').text().toLowerCase();
                if (selectedType.includes('photo')) {
                    const file = selectedFiles[index];
                    if (file) {
                        photoFiles.push(file);
                    }
                }
            });

            if (photoFiles.length === 0) {
                return { success: false, message: "No photo documents found" };
            }

            const photoListItem = $("fieldset:eq(2)").find('li:contains("Employee photo digitized and background whitened")');
            // photoListItem.html('<i class="fa fa-spinner fa-spin me-2"></i> Checking photo background...');

            for (const file of photoFiles) {
                const result = await processPhotoBackground(file);

                if (result.success) {
                    displayPhotoPreview(result.photoUrl);
                } else {
                    photoListItem.html('<i class="fa fa-exclamation-circle text-danger me-2"></i> Error processing photo background: ' + result.message);
                    return { success: false, message: result.message };
                }
            }

            return { success: true, message: "Photo background verified" };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }


    async function PassportExpiry() 
    {
        let PassportBlobUrl = '';

        $("fieldset:eq(1) select[name^='docType']").each(function (index) {
            const selectedType = $(this).find('option:selected').text().toLowerCase();
            if (selectedType.includes('passport main')) 
            {
                const file = selectedFiles[index]; // Assuming selectedFiles exists
                if (file) 
                {
                    PassportBlobUrl = file['pdfUrl'] || URL.createObjectURL(file);
                }
            }
        });

        if (!PassportBlobUrl)
        {
            return Promise.reject("No passport file selected");
        }

        try {
            const response = await fetch(PassportBlobUrl);
            if (!response.ok) throw new Error("Failed to fetch blob URL");
            const blob = await response.blob();
            const file = new File([blob], 'passport.pdf', { type: blob.type });

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('flag', 'passport');

            var photoListItem = $("fieldset:eq(2)").find('li:contains("Passport validity verified")');
            photoListItem.html(`Please Wait passport is verifying.`);
            return new Promise((resolve, reject) => {

                $(".next").attr("disabled", true);
                $.ajax({
                    url: "{{ route('visa.passport.Checkexpiry') }}",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) 
                    {
                        if (response.status) 
                        {
                          resolve(response);
                        } 
                        else
                        {
                            photoListItem.html(response.message);
                            reject(response);
                        }
                    },
                    error: function (xhr, status, error) {
                   
                        reject(error);

                    }
                });
            });

        } catch (err) {
            return Promise.reject(err);
        }
    }

    async function Cvcheck() 
    {
        let cvBlobUrls = [];

        $("fieldset:eq(1) select[name^='docType']").each(function(index) {
            const selectedType = $(this).find('option:selected').text().toLowerCase();
            if (selectedType.includes('cv')) {
                const file = selectedFiles[index];
                if (file) {
                    const url = file['pdfUrl'] || URL.createObjectURL(file);
                    cvBlobUrls.push(url);
                }
            }
        });

        if (cvBlobUrls.length === 0) {
            toster.error("No CV files selected", "Error", {
                position: "bottom-right",
                duration: 3000,
                closeButton: true,
                progressBar: true
            });
            return Promise.reject("No CV files selected");
        }

        try {
            const MAX_PAGES_PER_DOCUMENT = 5;
            const MAX_TOTAL_PAGES = 20;
            let totalPageCount = 0;

            const mergedPdf = await PDFLib.PDFDocument.create();
            let processingMessage = "Processing CV documents...";
            $(".file-error-message").html(processingMessage);

            for (let i = 0; i < cvBlobUrls.length; i++) {
                const url = cvBlobUrls[i];
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Failed to fetch CV #${i + 1}`);

                const arrayBuffer = await response.arrayBuffer();
                const pdfToMerge = await PDFLib.PDFDocument.load(arrayBuffer);
                const pageCount = pdfToMerge.getPageCount();
                
                // Determine how many pages to copy
                const pagesToCopy = Math.min(pageCount, MAX_PAGES_PER_DOCUMENT);
                if (pageCount > MAX_PAGES_PER_DOCUMENT) {
                    processingMessage += `<br>⚠️ Document #${i+1} has ${pageCount} pages. Only first ${MAX_PAGES_PER_DOCUMENT} pages will be processed.`;
                    $(".file-error-message").html(processingMessage);
                }
                
                // Check if adding these pages would exceed the total limit
                if (totalPageCount + pagesToCopy > MAX_TOTAL_PAGES) {
                    const remainingSlots = MAX_TOTAL_PAGES - totalPageCount;
                    if (remainingSlots <= 0) {
                        processingMessage += `<br>⚠️ Maximum total page limit (${MAX_TOTAL_PAGES}) reached. Skipping remaining documents.`;
                        $(".file-error-message").html(processingMessage);
                        break;
                    }
                    processingMessage += `<br>⚠️ Only processing first ${remainingSlots} pages from document #${i+1} to stay within limit.`;
                    $(".file-error-message").html(processingMessage);
                    
                    // Only copy pages up to the remaining limit
                    const pageIndicesToCopy = Array.from({ length: remainingSlots }, (_, j) => j);
                    const copiedPages = await mergedPdf.copyPages(pdfToMerge, pageIndicesToCopy);
                    copiedPages.forEach(page => mergedPdf.addPage(page));
                    totalPageCount += remainingSlots;
                    break;
                }
                
                // Copy the determined number of pages
                const pageIndicesToCopy = Array.from({ length: pagesToCopy }, (_, j) => j);
                const copiedPages = await mergedPdf.copyPages(pdfToMerge, pageIndicesToCopy);
                copiedPages.forEach(page => mergedPdf.addPage(page));
                totalPageCount += pagesToCopy;
                
                processingMessage += `<br>✅ Processed document #${i+1} (${pagesToCopy} pages)`;
                $(".file-error-message").html(processingMessage);
            }

            // Clear message after successful processing
            setTimeout(() => {
                $(".file-error-message").html("");
            }, 3000);

            const mergedPdfBytes = await mergedPdf.save();
            const mergedBlob = new Blob([mergedPdfBytes], { type: 'application/pdf' });
            const mergedFile = new File([mergedBlob], 'merged_cv.pdf', { type: 'application/pdf' });

            const formData = new FormData();
            formData.append('file', mergedFile);
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('flag', 'cv');
            $(".next").attr("disabled", true);

            return new Promise((resolve, reject) => 
            {
                $.ajax({
                    url: "{{ route('resort.visa.CheckCv') }}",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response)
                    {
                        if(response.status) 
                        {
                    
                            resolve(response);
                        } 
                        else
                        {
                            reject(false);
                        }
                    },
                    error: function(xhr, status, error) 
                    {
                        reject(error);
                    }
                });
            });

        } catch (err) {
            return Promise.reject(err.message);
        }
    }
   

    
    async function processPhotoBackground(file) 
    {
        try {
            if (!file) return { success: false, message: "Invalid file" };

            const arrayBuffer = file instanceof File
                ? await file.arrayBuffer()
                : await fetchFileBytes(file);

            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const page = await pdf.getPage(1);

            const viewport = page.getViewport({ scale: 3 }); // Increased scale for better quality
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({ canvasContext: ctx, viewport }).promise;

            // Setup segmentation with better configuration
            const selfieSegmentation = new SelfieSegmentation({
                locateFile: (file) =>
                    `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`
            });

            // Use model 1 for better accuracy
            selfieSegmentation.setOptions({ 
                modelSelection: 1,
                selfieMode: false // Better for passport photos
            });

            const segmentationResult = await new Promise((resolve, reject) => {
                selfieSegmentation.onResults(resolve);
                selfieSegmentation.initialize().then(() => {
                    selfieSegmentation.send({ image: canvas });
                }).catch(reject);
            });

            const mask = segmentationResult.segmentationMask;
            const w = canvas.width;
            const h = canvas.height;

            // Create mask canvas and get mask data
            const maskCanvas = document.createElement("canvas");
            maskCanvas.width = w;
            maskCanvas.height = h;
            const maskCtx = maskCanvas.getContext("2d");
            maskCtx.drawImage(mask, 0, 0, w, h);

            const maskImageData = maskCtx.getImageData(0, 0, w, h);
            const maskData = maskImageData.data;
            
            // Get original image data
            const originalImageData = ctx.getImageData(0, 0, w, h);
            const pixels = originalImageData.data;

            // Create output image data
            const output = ctx.createImageData(w, h);
            const outPixels = output.data;

            // Improved background removal with better thresholding
            const personThreshold = 0.7; // Higher threshold for person detection
            const backgroundThreshold = 0.3; // Lower threshold for background
            
            // First pass: Clear background/person separation
            for (let i = 0; i < pixels.length; i += 4) {
                const maskValue = maskData[i] / 255;
                
                if (maskValue > personThreshold) {
                    // Definitely person - keep original colors
                    outPixels[i]     = pixels[i];     // R
                    outPixels[i + 1] = pixels[i + 1]; // G
                    outPixels[i + 2] = pixels[i + 2]; // B
                    outPixels[i + 3] = 255;           // A
                } else if (maskValue < backgroundThreshold) {
                    // Definitely background - pure white
                    outPixels[i]     = 255; // R
                    outPixels[i + 1] = 255; // G
                    outPixels[i + 2] = 255; // B
                    outPixels[i + 3] = 255; // A
                } else {
                    // Edge area - blend with white to avoid artifacts
                    const blendFactor = (maskValue - backgroundThreshold) / (personThreshold - backgroundThreshold);
                    outPixels[i]     = Math.round(pixels[i] * blendFactor + 255 * (1 - blendFactor));
                    outPixels[i + 1] = Math.round(pixels[i + 1] * blendFactor + 255 * (1 - blendFactor));
                    outPixels[i + 2] = Math.round(pixels[i + 2] * blendFactor + 255 * (1 - blendFactor));
                    outPixels[i + 3] = 255;
                }
            }

            // Apply the processed image back to canvas
            ctx.putImageData(output, 0, 0);

            // Additional edge cleanup to remove any remaining artifacts
            const cleanupImageData = ctx.getImageData(0, 0, w, h);
            const cleanupPixels = cleanupImageData.data;

            // Edge detection and cleanup
            for (let y = 1; y < h - 1; y++) {
                for (let x = 1; x < w - 1; x++) {
                    const i = (y * w + x) * 4;
                    const maskValue = maskData[i] / 255;
                    
                    // Check if this is an edge pixel
                    if (maskValue > 0.2 && maskValue < 0.8) {
                        // Check surrounding pixels for better edge definition
                        const neighbors = [
                            ((y-1) * w + (x-1)) * 4, ((y-1) * w + x) * 4, ((y-1) * w + (x+1)) * 4,
                            (y * w + (x-1)) * 4,                          (y * w + (x+1)) * 4,
                            ((y+1) * w + (x-1)) * 4, ((y+1) * w + x) * 4, ((y+1) * w + (x+1)) * 4
                        ];
                        
                        let personNeighbors = 0;
                        let backgroundNeighbors = 0;
                        
                        neighbors.forEach(ni => {
                            const neighborMask = maskData[ni] / 255;
                            if (neighborMask > personThreshold) personNeighbors++;
                            if (neighborMask < backgroundThreshold) backgroundNeighbors++;
                        });
                        
                        // If more neighbors are background, make this pixel white
                        if (backgroundNeighbors > personNeighbors) {
                            cleanupPixels[i]     = 255;
                            cleanupPixels[i + 1] = 255;
                            cleanupPixels[i + 2] = 255;
                            cleanupPixels[i + 3] = 255;
                        }
                    }
                }
            }

            ctx.putImageData(cleanupImageData, 0, 0);

            // Calculate passport photo dimensions (standard 2x2 inches at 300 DPI = 600x600px)
            const passportSize = 600;
            
            // Find the person bounds to center them properly
            let minX = w, maxX = 0, minY = h, maxY = 0;
            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const i = (y * w + x) * 4;
                    const maskValue = maskData[i] / 255;
                    if (maskValue > personThreshold) {
                        minX = Math.min(minX, x);
                        maxX = Math.max(maxX, x);
                        minY = Math.min(minY, y);
                        maxY = Math.max(maxY, y);
                    }
                }
            }

            // Add padding around the person
            const padding = 50;
            minX = Math.max(0, minX - padding);
            maxX = Math.min(w - 1, maxX + padding);
            minY = Math.max(0, minY - padding);
            maxY = Math.min(h - 1, maxY + padding);

            const personWidth = maxX - minX;
            const personHeight = maxY - minY;
            
            // Create final canvas with proper passport dimensions
            const finalCanvas = document.createElement("canvas");
            finalCanvas.width = passportSize;
            finalCanvas.height = passportSize;
            const finalCtx = finalCanvas.getContext("2d");

            // Fill entire canvas with pure white background
            finalCtx.fillStyle = "#ffffff";
            finalCtx.fillRect(0, 0, passportSize, passportSize);

            // Calculate scaling to fit person in passport photo with proper proportions
            const scale = Math.min(
                (passportSize * 0.8) / personWidth,  // 80% of canvas width
                (passportSize * 0.9) / personHeight  // 90% of canvas height (head to shoulders)
            );

            const scaledWidth = personWidth * scale;
            const scaledHeight = personHeight * scale;

            // Center the person in the passport photo
            const offsetX = (passportSize - scaledWidth) / 2;
            const offsetY = (passportSize - scaledHeight) / 2;

            // Draw the processed person centered in the passport format
            finalCtx.drawImage(
                canvas, 
                minX, minY, personWidth, personHeight,  // Source rectangle
                offsetX, offsetY, scaledWidth, scaledHeight  // Destination rectangle
            );

            // Clean up the canvas by removing any remaining artifacts
            const finalImageData = finalCtx.getImageData(0, 0, passportSize, passportSize);
            const finalPixels = finalImageData.data;

            // Final cleanup pass - ensure pure white background
            for (let i = 0; i < finalPixels.length; i += 4) {
                const r = finalPixels[i];
                const g = finalPixels[i + 1];
                const b = finalPixels[i + 2];
                
                // If pixel is very close to white or has blue tint, make it pure white
                if (r > 240 && g > 240 && b > 240) {
                    finalPixels[i]     = 255;
                    finalPixels[i + 1] = 255;
                    finalPixels[i + 2] = 255;
                    finalPixels[i + 3] = 255;
                }
                
                // Remove any blue artifacts specifically
                if (b > r && b > g && (b - Math.max(r, g)) > 20) {
                    finalPixels[i]     = 255;
                    finalPixels[i + 1] = 255;
                    finalPixels[i + 2] = 255;
                    finalPixels[i + 3] = 255;
                }
            }

            finalCtx.putImageData(finalImageData, 0, 0);

            // Export final image with high quality
            const blob = await new Promise(resolve =>
                finalCanvas.toBlob(resolve, "image/jpeg", 0.95)
            );
            const photoUrl = URL.createObjectURL(blob);

            // Update UI
            const photoListItem = $("fieldset:eq(2)").find('li:contains("Employee photo digitized and background whitened")');
            if (photoListItem.length) {
                photoListItem.html("Employee photo digitized and background whitened ✓");
            }

            return {
                success: true,
                photoUrl,
                message: "Background properly whitened in passport format (600x600px)"
            };

        } catch (error) {
            return { success: false, message: `Error: ${error.message}` };
        }
    }
    function displayPhotoPreview(photoUrl) {
        const fieldset3 = $("fieldset:eq(2)");
        const previewContainer = fieldset3.find(".photo-preview-container");   

        if (!previewContainer.length) {
            fieldset3.append(`
                <div class="photo-preview-container mt-4" style="width:30%;">
                    <h4>Photo Preview</h4>
                    <img src="${photoUrl}" style="width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px;" />
                    <a href="${photoUrl}" download="Whitened_Photo.jpg" class="btn btn-themeBlue btn-sm mt-3">Download Photo</a>
                </div>
            `);
        } else {
            previewContainer.find("img").attr("src", photoUrl);
            previewContainer.find("a").attr("href", photoUrl);
        }
    }
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

  async function EducationCheck() {
    let educationFiles = [];
    for (let index = 0; index < $("fieldset:eq(1) select[name^='docType']").length; index++) {
        const $select = $("fieldset:eq(1) select[name^='docType']").eq(index);
        const selectedType = $select.find('option:selected').text().toLowerCase();
        if (selectedType.includes('education')) {
            const fileWrapper = selectedFiles[index];
            let file = null;
            if (fileWrapper instanceof File || fileWrapper instanceof Blob) {
                file = fileWrapper;
            } else if (fileWrapper && fileWrapper.pdfUrl && fileWrapper.pdfUrl.startsWith('blob:')) {
                try {
                    const response = await fetch(fileWrapper.pdfUrl);
                    const blob = await response.blob();
                    file = new File([blob], fileWrapper.name || fileWrapper.original_name || 'document.pdf', {type: 'application/pdf'});
                } catch (error) {
                    continue;
                }
            }
            if (file instanceof File) educationFiles.push(file);
        }
    }

    // Remove all but first block
    const $repeater = $('.employeeEducationRepeater-main');
    $repeater.find('.employeeEducationRepeater-block:gt(0)').remove();

    // add (N-1) blocks if N > 1
    for (let i = 1; i < educationFiles.length; i++) {
        $repeater.find('.education-add-btn').first().trigger('click');
    }
    updateEducationButtons();
    updateEducationFieldNames();

    if (educationFiles.length === 0) {
        toastr.error("No Education files selected", "Error", {
            position: "bottom-right",
            duration: 3000,
            closeButton: true, progressBar: true
        });
        return Promise.reject("No Education files selected");
    }

    $(".next").attr("disabled", true);
    $(".file-error-message").html("Uploading education files...");
    try {
        for (let i = 0; i < educationFiles.length; i++) {
            const file = educationFiles[i];
            $(".file-error-message").html(`Uploading file ${i + 1} of ${educationFiles.length}...`);
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('flag', 'education');
            let response = await new Promise((resolve, reject) => {
                $.ajax({
                    url: "{{ route('resort.visa.Education') }}",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(resp) {
                        if (resp.status) resolve(resp);
                        else reject(new Error('Upload failed for file ' + (i+1)));
                    },
                    error: function(xhr, status, error) {
                        reject(error);
                    }
                });
            });

            const $currentBlock = $repeater.find('.employeeEducationRepeater-block').eq(i);

            // Hidden file path
            if (response.file) {
                let $hiddenInput = $currentBlock.find(`input[name="education[${i}][document]"]`);
                if ($hiddenInput.length === 0) {
                    $hiddenInput = $('<input type="hidden">').attr('name', `education[${i}][document]`);
                    $currentBlock.append($hiddenInput);
                }
                $hiddenInput.val(response.file);
            }
            $currentBlock.find('.education-upload-input').val('');
            $currentBlock.find('.education-file-name').text(`✓ ${file.name || 'document.pdf'} (Uploaded)`).addClass('text-success');
            $currentBlock.find('.uploadFile-btn').hide();

            // Auto-fill extracted values
            if (response.data) {
                $currentBlock.find('.education_level').val(response.data['Education level/type'] || '');
                $currentBlock.find('.institutio_name').val(response.data['Institution Name'] || '');
                $currentBlock.find('.field_study').val(response.data['Field of Study/Major'] || '');
                $currentBlock.find('.degree_earned').val(response.data['degree/certificate earned'] || '');
                $currentBlock.find('.attendance_period').val(response.data['attendance period'] || '');
                $currentBlock.find('.location').val(response.data['location'] || '');
            }
        }
        $(".file-error-message").html("All education files uploaded.");
        $(".next").attr("disabled", false);
        updateEducationButtons();
        updateEducationFieldNames();
        return Promise.resolve();
    } catch (err) {
        $(".file-error-message").html(`Error: ${err.message || err}`);
        $(".next").attr("disabled", false);
        return Promise.reject(err);
    }
}

   async function ExperienceCheck() {
    let experienceFiles = [];
    for (let index = 0; index < $("fieldset:eq(1) select[name^='docType']").length; index++) {
        const $select = $("fieldset:eq(1) select[name^='docType']").eq(index);
        const selectedType = $select.find('option:selected').text().toLowerCase();
        if (selectedType.includes('experience')) {
            const fileWrapper = selectedFiles[index];
            let file = null;
            if (fileWrapper instanceof File || fileWrapper instanceof Blob) {
                file = fileWrapper;
            } else if (fileWrapper && fileWrapper.pdfUrl && fileWrapper.pdfUrl.startsWith('blob:')) {
                try {
                    const response = await fetch(fileWrapper.pdfUrl);
                    const blob = await response.blob();
                    file = new File([blob], fileWrapper.name || fileWrapper.original_name || 'document.pdf', {type: 'application/pdf'});
                } catch (error) {
                    continue;
                }
            }
            if (file instanceof File) experienceFiles.push(file);
        }
    }

    // Remove all but first block
    const $repeater = $('.employeeProCreationProcessExp-main');
    $repeater.find('.employeeProCreationProcessExp-block:gt(0)').remove();
    // add (N-1) blocks if N > 1
    for (let i = 1; i < experienceFiles.length; i++) {
        $repeater.find('.blockAdd-btn').first().trigger('click');
    }
    updateRemoveAndAddButtons();
    updateExperienceFieldNames();

    if (experienceFiles.length === 0) {
        toastr.error("No Experience files selected", "Error", {
            position: "bottom-right",
            duration: 3000,
            closeButton: true, progressBar: true
        });
        return Promise.reject("No Experience files selected");
    }

    $(".next").attr("disabled", true);
    $(".file-error-message").html("Uploading experience files...");
    try {
        for (let i = 0; i < experienceFiles.length; i++) {
            const file = experienceFiles[i];
            $(".file-error-message").html(`Uploading file ${i + 1} of ${experienceFiles.length}...`);
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('flag', 'experience');
            let response = await new Promise((resolve, reject) => {
                $.ajax({
                    url: "{{ route('resort.visa.Experience') }}",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(resp) {
                        if (resp.status) resolve(resp);
                        else reject(new Error('Upload failed for file ' + (i+1)));
                    },
                    error: function(xhr, status, error) {
                        reject(error);
                    }
                });
            });

            const $currentBlock = $repeater.find('.employeeProCreationProcessExp-block').eq(i);

            if (response.file || (response.data && response.data.document)) {
                const filePath = response.file || response.data.document;
                let $hiddenInput = $currentBlock.find(`input[name="experience[${i}][document]"]`);
                if ($hiddenInput.length === 0) {
                    $hiddenInput = $('<input type="hidden">').attr('name', `experience[${i}][document]`);
                    $currentBlock.append($hiddenInput);
                }
                $hiddenInput.val(filePath);
            }

            $currentBlock.find('.uploadFile').val('');
            $currentBlock.find('.certificate-file-name').text(`✓ ${file.name || 'document.pdf'} (Uploaded)`).addClass('text-success');
            $currentBlock.find('.uploadFile-btn').hide();

            if (response.data) {
                $currentBlock.find('.company_name').val(response.data["company_name"] || '');
                $currentBlock.find('.job_title').val(response.data["position/job title"] || '');
                $currentBlock.find('.employment_type').val(response.data["employment_type"] || '');
                $currentBlock.find('.duration_employment').val(response.data["duration"] || '');
                $currentBlock.find('.location1').val(response.data["location"] || '');
                $currentBlock.find('.reason_leaving').val(response.data["reason for leaving"] || '');
                $currentBlock.find('.reference_name').val(response.data["reference Name"] || '');
                $currentBlock.find('.reference_contact').val(response.data["reference contact info"] || '');
            }
        }
        $(".file-error-message").html("All experience files uploaded.");
        $(".next").attr("disabled", false);
        updateRemoveAndAddButtons();
        updateExperienceFieldNames();
        return Promise.resolve();
    } catch (err) {
        $(".file-error-message").html(`Error: ${err.message || err}`);
        $(".next").attr("disabled", false);
        return Promise.reject(err);
    }
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

    function toggleRemoveButtons() 
    {
        const $blocks = $('.employeeLanguageRepeater-block');
        $blocks.each(function (i, block) {
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
        $('.employeeLanguageRepeater-block').each(function (i, block) {
            $(block).find('.language-input').attr('name', `language[${i}][0]`);
            $(block).find('.proficiency-level-select').attr('name', `language[${i}][1]`);
        });
    }
    function updateAllowanceButtons()
    {
        $('.allowanceRepeater-block').each(function (i, block) {
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
    function updateAllowanceFieldNames() 
    {
        $('.allowanceRepeater-block').each(function (i, block) {
            $(block).find('.allowance-type-select').attr('name', `allowance[${i}][type]`);
            $(block).find('.allowance-amount-input').attr('name', `allowance[${i}][amount]`);
            $(block).find('.allowance-currency-select').attr('name', `allowance[${i}][currency]`);
        });
    }
    function reinitSelect2($context) 
    {
        $context.find('select.select2t-none').select2();
    }

    function updateBankButtons() 
    {
        $('.bankRepeater-block').each(function (i, block) {
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
    function updateBankNames() 
     {
        $('.bankRepeater-block').each(function (i, block) {
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
       
</script>
@endsection
