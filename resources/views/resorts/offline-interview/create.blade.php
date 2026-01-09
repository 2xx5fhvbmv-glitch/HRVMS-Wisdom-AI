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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <form id="msform">
                        <!-- progressbar -->
                        <div class="progressbar-wrapper">
                            <ul id="progressbar"
                                class="progressbar-tab d-flex justify-content-between align-items-center">
                                <li class="active current"> <span>Hiring Requisition Form</span></li>
                                <li><span>Upload Candidate Documents</span></li>
                                <li><span>Interview Rounds</span></li>
                                <li><span>Selection and Offer Process</span></li>
                            </ul>
                        </div>
                        <!-- <fieldset data-step="1">
                            <div class=" mb-md-4 mb-3">
                                <div class="card-title">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Hiring Request Form</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-sm-6 ">
                                        <label for="select-budgeted" class="form-label">BUDGETED OR OUT OF BUDGET?</label>
                                        <select id="vacancy_status" class="form-select select2t-none" name="budgeted" required data-parsley-errors-container="#vacancy_status-error" >
                                            <option value="Budgeted">Budgeted</option>
                                        </select>
                                        <div id="vacancy_status-error"></div>
                                    </div>
                                    <div class="col-sm-6 d-md-inline-block d-none"> </div>
                                    <div class="col-sm-6  ">
                                        <label for="division" class="form-label">DIVISION</label>
                                        <select class="form-select select2t-none" id="division"
                                            aria-label="Default select example" required data-parsley-errors-container="#division-error">
                                            <option value="">Select Division</option>
                                            @if($divisions)
                                                @foreach($divisions as $div)
                                                    <option value="{{$div->id}}">{{$div->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="division-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="department" class="form-label">DEPARTMENT</label>
                                        <select class="form-select select2t-none" id="department" aria-label="Default select example" required data-parsley-errors-container="#department-error">
                                            <option value="">Select Department</option>
                                        </select>
                                        <div id="department-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="section" class="form-label">SECTION</label>
                                        <select class="form-select select2t-none" id="section" aria-label="Default select example" data-parsley-errors-container="#section-error">
                                            <option value="">Select Section</option>
                                        </select>
                                        <div id="section-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="position" class="form-label">POSITION</label>
                                        <select class="form-select select2t-none" id="position" aria-label="Default select example" required data-parsley-errors-container="#position-error">
                                            <option value="">Select Position</option>
                                        </select>
                                        <div id="position-error"></div>
                                    </div>
                                    <div class="col-sm-6  ">
                                        <label for="rank" class="form-label">RANK</label>
                                        <input type="text" class="form-control" id="txt-rank" placeholder="RANK" name="rank" disabled>
                                        <input type="hidden" class="form-control" id="rank_id" name="rank_id">
                                    </div>
                                    <div class="col-sm-6 ">
                                        <label for="txt-position-title" class="form-label">Required No of Vacancy</label>
                                        <input type="number" name="Total_position_required" id="Total_position_required" class="form-control" required/>
                                    </div>
                                    <div class="col-sm-6  ">
                                        <label for="txt-bod" class="form-label">REQUIRED STARTING DATE</label>
                                        <input type="text" class="form-control datepicker" id="txt-bod"
                                            placeholder="Required Startimg Date" required>
                                    </div>
                                    <div class="col-sm-6  ">
                                        <label for="reporting_to" class="form-label">REPORTING TO</label>
                                        <select class="form-select select2t-none" id="reporting_to" aria-label="Default select example" required data-parsley-errors-container="#reporting_to-error">
                                            <option value="">Reporting To</option>
                                        </select>
                                        <div id="reporting_to-error"></div>
                                    </div>
                                </div>
                            </div>
                            <div class=" mb-md-4 mb-3">
                                <div class="row g-md-4 g-3"> 
                                    <div class="col-12">
                                        <label for="select-selection" class="form-label">EMPLOYEE TYPE</label>
                                        <ul class="nav mt-2 ">
                                            <li class="form-radio">
                                                <input class="form-radio-input" type="radio" value="Permanant" id="radio-permanant" name="employee_type" checked>
                                                <label class="form-radio-label" for="radio-permanant">
                                                    Permanant
                                                </label>
                                            </li>
                                            <li class="form-radio ">
                                                <input class="form-radio-input" type="radio" value="Casual/Agency" id="radio-casual-Agency" name="employee_type">
                                                <label class="form-radio-label" for="radio-casual-Agency">
                                                    Casual/Agency
                                                </label>
                                            </li>
                                            <li class="form-radio ">
                                                <input class="form-radio-input" type="radio" value="Trainee / Intern" id="radio-trainee-intern" name="employee_type">
                                                <label class="form-radio-label" for="radio-trainee-intern">
                                                    Trainee / Intern
                                                </label>
                                            </li>
                                            <li class="form-radio ">
                                                <input class="form-radio-input" type="radio" value="Replacement" id="radio-replacement" name="employee_type">
                                                <label class="form-radio-label" for="radio-replacement">
                                                    Replacement
                                                </label>
                                            </li>
                                            <li class="form-radio ">
                                                <input class="form-radio-input" type="radio" value="Temporary / Project"
                                                    id="radio-temporary-project" name="employee_type">
                                                <label class="form-radio-label" for="radio-temporary-project">
                                                    Temporary / Project
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-12" id="temp-div" style="display: none;">
                                        <div id="" class="row g-md-4 g-3 row-cols-xl-5 row-cols-md-3  row-cols-sm-2 row-cols-1">
                                            <div class="col txt-service-provider" id="service-provider-container">
                                               
                                                <div>
                                                    
                                                    <label for="new_service_provider">New Service Provider</label>
                                                    <input type="text" name="new_service_provider" id="new_service_provider" placeholder="Enter new service provider" class="form-control">
                                                </div>

                                                <div>
                                                    
                                                    <label for="service_provider">Select Service Provider</label>
                                                    <select name="service_provider" id="service_provider" class="form-select">
                                                        <option value="">-- Select a service provider --</option>
                                                        @foreach($serviceProviders as $provider)
                                                            <option value="{{ $provider->name }}">{{ $provider->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col txt-salary">
                                                <label for="select-salary" class="form-label">SALARY</label>
                                                <input type="text" name="salary" id="salary" class="form-control" placeholder="SALARY"/>
                                            </div>
                                            <div class="col txt-food">
                                                <label for="select-food" class="form-label">FOOD</label>
                                                <input type="text" name="food" id="food" class="form-control" placeholder="FOOD"/>
                                            </div>
                                            <div class="col txt-accommodation">
                                                <label for="txt-accommodation" class="form-label">ACCOMMODATION</label>
                                                <input type="text" class="form-control" name="accommodation" id="txt-accommodation" placeholder="ACCOMMODATION">
                                            </div>
                                            <div class="col txt-transporatation">
                                                <label for="txt-transporatation" class="form-label">TRANSPORTATION</label>
                                                <input type="text" class="form-control" name="transportation" id="txt-TRANSPORTATION" placeholder="TRANSPORTATION">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="replacement-employee" style="display: none;">
                                <div class="col-md-4 col-sm-6">
                                    <label for="txt-employee" class="form-label">Employee Name</label>
                                    <input type="text" class="form-control" id="txt-employee-name" placeholder="Employee Name">
                                </div>
                            </div>

                            <div id="permanent-div">
                                <div class="col-12">
                                    <div class="card-title mt-md-4 mt-3">
                                        <div class="row justify-content-start align-items-center g-">
                                            <div class="col">
                                                <h3>Budget, Funding & Benefits</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-budget-salary" class="form-label">BUDGET SALARY</label>
                                        <input type="text" class="form-control" name="budget_salary" id="txt-budget-salary" placeholder="BUDGET SALARY">
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-acommocation2" class="form-label">ACOMMODATION</label>
                                        <input type="text" class="form-control" name="budgeted_accommodation" id="txt-acommocation2" placeholder="ACOMMODATION">
                                    </div>
                                    <div class="col-md-4 col-sm-6  ">
                                        <label for="txt-rank" class="form-label">SERVICE CHARGE</label>
                                        <ul class="d-flex navalign-items-center">
                                            <li class="form-check ">
                                                <input class="form-check-input" type="radio" name="service_charge" value="YES" id="flexCheckservicechares-yes" checked>
                                                <label class="form-check-label" for="flexCheckservicechares-yes">
                                                    Yes
                                                </label>
                                            </li>
                                            <li class="form-check ">
                                                <input class="form-check-input" type="radio" name="service_charge" value="NO"
                                                    id="flexCheckservicechares-no">
                                                <label class="form-check-label" for="flexCheckservicechares-no">
                                                    No
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-proposed-salary" class="form-label">PROPOSED SALARY</label>
                                        <input type="text" class="form-control" id="txt-proposed-salary"
                                            placeholder="Proposed Salary" name="proposed_salary">
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-allowances" class="form-label">ALLOWANCES</label>
                                        <input type="text" class="form-control" name="allowance" id="txt-allowances" placeholder="Allowances">
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-rank" class="form-label">UNIFORM</label>
                                        <ul class="d-flex nav  align-items-center">
                                            <li class="form-check ">
                                                <input class="form-check-input" type="radio" name="uniform" value="YES" id="flexCheckUNIFORM-yes" checked>
                                                <label class="form-check-label" for="flexCheckUNIFORM-yes">
                                                    Yes
                                                </label>
                                            </li>
                                            <li class="form-check ">
                                                <input class="form-check-input" type="radio" name="uniform" value="NO"  id="flexCheckUNIFORM-no">
                                                <label class="form-check-label" for="flexCheckUNIFORM-no">
                                                    No
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-Medical" class="form-label">MEDICAL</label>
                                        <input type="text" class="form-control" name="medical" id="txt-Medical" placeholder="Medical">
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-Insurance" class="form-label">INSURANCE</label>
                                        <input type="text" class="form-control" name="insurance" id="txt-Insurance" placeholder="Insurance">
                                    </div>
                                    <div class="col-md-4 col-sm-6 ">
                                        <label for="txt-Pension" class="form-label">PENSION</label>
                                        <input type="text" class="form-control" name="pension" id="txt-Pension" placeholder="Pension">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="card-title mt-md-4 mt-3">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Recruitment</h3>
                                        </div>
                                    </div>
                                </div>


                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="servicechares" value=""
                                        id="flexCheckonline-job-posting-yes">
                                    <label class="form-check-label" for="flexCheckonline-job-posting-yes">
                                        Online job posting
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="servicechares" value=""
                                        id="flexCheckrecruiter-no">
                                    <label class="form-check-label" for="flexCheckrecruiter-no">
                                        Recruiter
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="servicechares" value=""
                                        id="flexCheckagency-no">
                                    <label class="form-check-label" for="flexCheckagency-no">
                                        Agency
                                    </label>
                                </div>

                            </div>

                            <hr class="hr-footer">
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>

                        </fieldset>
                        <fieldset data-step="2">
                            <div class="pb-md-5 pb-4">
                                <div class="card-title">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Upload Candidate Documents</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-6"> 
                                        <div class="upload-area drop-zone " id="uploadfile">
                                            <div class="d-flex align-items-center text-start drop-zone__prompt">
                                                <div class="img-box">
                                                    <img src="{{ URL::asset('resorts_assets/images/upload.svg')}} " alt="" class="img-fluid" />
                                                </div>
                                                <div>
                                                    <h3>Upload A Curriculum Vitae</h3>
                                                    <span>PDF Format</span>
                                                </div>
                                            </div>
                                            <p>Browse or Drag the file here</p>
                                            <input type="file" id="fileInput" name="curriculum_file" class="drop-zone__input"  data-parsley-required="true" data-parsley-required-message="Please upload your CV">
                                        </div>
                                        <div class="error-message-container"></div>
                                    </div>

                                    <div class="col-6">
                                        <div class="upload-area drop-zone " id="uploadfile">
                                            <div class="d-flex align-items-center text-start drop-zone__prompt">
                                                <div class="img-box">
                                                    <img src="{{ URL::asset('resorts_assets/images/upload.svg')}}" alt="" class="img-fluid" />
                                                </div>
                                                <div>
                                                    <h3>Upload Your Passport</h3>
                                                    <span>PDF Format</span>
                                                </div>
                                            </div>
                                            <p>Browse or Drag the file here</p>
                                            <input type="file" id="fileInput" name="passport" class="drop-zone__input">
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">PASSPORT-SIZE PHOTO<span class="red-mark">*</span></label>
                                        <div class="d-md-flex align-items-center">
                                            <div class="profile-img-box">
                                                <img src="" id="profilePicturePreview" width="100" />
                                            </div>
                                            <div class="uploadFile-block mt-md-0 mt-3">
                                                <div class="uploadFile-btn me-0">
                                                    <a href="#" class="btn btn-themeBlue btn-sm"> Upload Photo</a>
                                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" data-parsley-required="true"
                                                    data-parsley-required-message="Please upload your passport size photo">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">FULL-LENGTH PHOTO<span class="red-mark">*</span></label>
                                        <div class="d-md-flex align-items-center">
                                            <div class="profile-img-box">
                                                <img src="" id="profilePreviewfullimg" width="100" />
                                            </div>
                                            <div class="uploadFile-block mt-md-0 mt-3">
                                                <div class="uploadFile-btn me-0">
                                                    <a href="#" class="btn btn-themeBlue btn-sm"> Upload Photo</a>
                                                    <input type="file" name="full_length_photo" id="full_length_photos" accept="image/*" data-parsley-required="true" data-parsley-required-message="Please upload your full length photo">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                    <label for="txt-first-name" class="form-label">FIRST NAME<span class="red-mark">*</span></label>
                                    <input type="text" name="first_name" id="txt-first-name"  class="form-control  alpha-only" value="" placeholder="First Name" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6">
                                    <label for="txt-last-name" class="form-label">LAST NAME<span class="red-mark">*</span></label>
                                    <input type="text" name="last_name" id="txt-last-name"  class="form-control  alpha-only" value="" placeholder="Last Name" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="select-gender" class="form-label">GENDER<span class="red-mark">*</span></label>
                                    <select 
                                        class="form-select select2t-none" 
                                        name="gender" 
                                        id="select-gender" 
                                        aria-label="Default select example" 
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#gender-error" 
                                    >
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div id="gender-error"></div>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-bod" class="form-label">DOB <span class="red-mark">*</span></label>
                                    <input type="text" name="dob" class="form-control datepicker" id="txt-bod" placeholder="DD/MM/YYYYY" required>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-mobile-number" class="form-label">MOBILE
                                        NUMBER<span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="mobile_number" id="txt-mobile-number" placeholder="Mobile Number" required data-parsley-type="digits" required data-parsley-mobile_number data-parsley-trigger="change">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-email-address" class="form-label">EMAIL
                                        ADDRESS<span class="red-mark">*</span></label>
                                    <input type="email" class="form-control" name="email"  id="txt-email-address" placeholder="Email Address" required data-parsley-type="email" data-parsley-trigger="change"  data-parsley-custom-email="true">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="select-marital-status" class="form-label">MARITAL
                                        STATUS<span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" name="marital_status" value="" id="select-marital-status" aria-label="Default select example" required data-parsley-errors-container="#marital-status-error">
                                        <option value="">Select Marital Status</option>
                                        <option value="married">Married </option>
                                        <option value="unmarried">Unmarried</option>
                                    </select>
                                    <div id="marital-status-error"></div>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-number-children" class="form-label">NUMBER OF
                                        CHILDREN<span class="red-mark">*</span></label>
                                    <input type="text" name="number_of_children" value="" class="form-control" id="txt-number-children" placeholder="Number of children" required data-parsley-type="digits" data-parsley-trigger="change">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-address-line-1" class="form-label">ADDRESS LINE
                                        1<span class="red-mark">*</span></label>
                                    <input type="text" name="address_line_one" value="" class="form-control" id="txt-address-line-1" placeholder="Address line 1" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-address-line-2" class="form-label">ADDRESS LINE
                                        2<span class="red-mark">*</span></label>
                                    <input type="text" name="address_line_two" value="" class="form-control" id="txt-address-line-2" placeholder="Address line 2" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                
                                <div class="col-md-6 ">
                                    <label for="select-country" class="form-label">COUNTRY<span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="select-country"
                                        aria-label="Default select example" name="country" data-parsley-required="true" data-parsley-errors-container="#country-error">
                                        <option value="">Select Country </option>
                                        @foreach($countries as $country)
                                            <option value="{{$country->id}}">{{$country->name}}</option>
                                        @endforeach
                                    </select>
                                    <div id="country-error"></div>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="state" class="form-label">STATE<span class="red-mark">*</span></label>
                                    <input type="text" name="state" id="state" class="form-control alpha-only" placeholder="State" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed."/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="city" class="form-label">CITY<span class="red-mark">*</span></label>
                                    <input type="text" name="city" id="city" class="form-control alpha-only" placeholder="City" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed."/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="select-pin-code" class="form-label">PIN CODE<span class="red-mark">*</span></label>
                                    <input type="text" name="pin_code" value="" class="form-control" id="select-pin-code" placeholder="Pin Code" required data-parsley-trigger="change" data-parsley-pin_code/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="passport_no" class="form-label">PASSPORT NO<span class="red-mark">*</span></label>
                                    <input type="text" name="passport_no" value="" class="form-control" id="passport_no" placeholder="Passport No" data-parsley-passport_no/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="passport_expiry_date" class="form-label">PASSPORT EXPIRY DATE<span class="red-mark">*</span></label>
                                    <input type="text" name="passport_expiry_date" class="form-control datepicker" id="passport_expiry_date" placeholder="Passport Expiry Date" />
                                </div>
                                </div>
                            </div>
                            <hr class="hr-footer">
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset> -->
                        <fieldset data-step="3">
                            <div class="mb-5">
                                <div class="card-title mb-md-4 mb-3">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Interview Rounds</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-lg-4 g-3 mb-lg-4 mb-3">
                                    <div class="col-lg-6">
                                        <div class="userApplicants-accordion" id="accordionExample">
                                            <div class="accordion-item active">
                                                <h2 class="accordion-header" id="headingOne">
                                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                        HR Shortlisted
                                                    </button>
                                                </h2>
                                                <div id="collapseOne" class="accordion-collapse collapse show"
                                                    aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <a href="#" id="hrRoundComplete" class="btn btn-themeSkyblue btn-sm">Complete HR Round</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                        Interview
                                                    </button>
                                                </h2>
                                                <div id="collapseTwo" class="accordion-collapse collapse"
                                                    aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <ol>
                                                            <li>HOD Round</li>
                                                            <li>GM Round</li>
                                                        </ol>
                                                        <a href="#" id="hodRoundComplete" class="btn btn-themeSkyblue btn-sm" disabled>Complete HOD Round</a>
                                                        <a href="#" id="gmRoundComplete" class="btn btn-themeSkyblue btn-sm" disabled>Complete GM Round</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingThree">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                        Selected
                                                    </button>
                                                </h2>
                                                <div id="collapseThree" class="accordion-collapse collapse"
                                                    aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <a href="#" id="selectButton" class="btn btn-themeSkyblue btn-sm">Selected</a>
                                                        <a href="#" id="rejectButton" class="btn btn-themeSkyblue btn-sm">Rejected</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6">
                                        <label for="" class="form-label">COMMENTS ABOUT PERFORMANCE</label>
                                        <div class="textarea-icon mt-2">
                                            <textarea rows="8" class="form-control" placeholder="Type Here"></textarea>
                                            <img src="{{ URL::asset('resorts_assets/images/textarea-icon.svg')}}" alt="icon">
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                            <hr class="hr-footer">
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href="#" class="btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset data-step="4">
                            <div class=" mb-5">
                                <div class="card-title">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Selection & offer Process</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-sm-6 ">
                                        <label for="budgeted" class="form-label">IS CANDIDATE IS SELECTED?</label>
                                        <select class="form-select select2t-none" name="candidate_status" id="candidate_status" aria-label="Default select example">
                                            <option value="Selected">Yes </option>
                                            <option value="Rejected">No</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 d-sm-block d-none"></div>
                                    <div class="col-sm-6  ">
                                        <label for="budgeted" class="form-label">SEND AN OFFER LETTER</label>
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn">
                                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                                <input type="file" id="uploadFile">
                                            </div>
                                            <div class="uploadFile-text">PNG, JPEG, PDF, Excel</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr-footer">
                            <a href=" # " class=" btn btn-themeBlue btn-sm float-end  ">Submit</a>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="uploadimageModal" class="modal" >
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
                    <button type="button" class="btn btn-primary crop-picture custom-btn" data-dismiss="modal">Crop</button>
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
                <button type="button" class="btn btn-primary crop_picture_full_img custom-btn" data-dismiss="modal">Crop</button>
            <!--   <button type="button" class="btn btn-default" id="closemodalfull_img" data-dismiss="modal">Close</button> -->

            <button type="button" data-bs-dismiss="modal" class="btn btn-default">Close</button>

            </div>
        </div>
        </div>
    </div>
@endsection

@section('import-css')
<link href="{{ URL::asset('applicant_form_assets/css/default.css')}}" rel=stylesheet>
<link href="{{ URL::asset('applicant_form_assets/css/media.css')}}" rel=stylesheet>
@endsection

@section('import-scripts')
    <script type="text/javascript">
        $('.datepicker').datepicker({});

        $(document).ready(function () {
            // Ensure Parsley is loaded
            if (typeof $.fn.parsley !== 'function') {
                console.error('Parsley.js is not loaded correctly');
                return;
            }

            // Initialize the entire form with Parsley
            var $form = $("#msform");
            $form.parsley({
                excluded: 'input[type=button], input[type=submit], input[type=reset]',
                trigger: 'change',
                successClass: 'is-valid',
                errorClass: 'is-invalid'
            });

            $(".select2t-none").select2();

            // Manually trigger Parsley validation when Select2 changes
            $(".select2t-none").on('change', function () {
                var parsleyField = $(this).parsley();
                parsleyField.validate();

                // Add/remove the error class to the Select2 container based on validation
                if (parsleyField.isValid()) {
                    $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                } else {
                    $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
                }
            });

            // Parsley field validation handler
            window.Parsley.on('field:validated', function (fieldInstance) {
                var $element = fieldInstance.$element;
                if ($element.hasClass('select2t-none')) {
                    // Update the Select2 container's appearance
                    var $select2Container = $element.next('.select2-container').find('.select2-selection');
                    if (fieldInstance.isValid()) {
                        $select2Container.removeClass('is-invalid');
                    } else {
                        $select2Container.addClass('is-invalid');
                    }
                }
            });
            $('#division').on('change', function() {
                let divisionId = $(this).val();
                let departmentDropdown = $('#department');

                // Clear department dropdown
                departmentDropdown.empty().append('<option value="">Select Department</option>');

                if (divisionId) {
                    $.ajax({
                        url: "{{ route('departments.get', ':divid') }}".replace(':divid', divisionId),
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                // Populate departments dropdown
                                response.departments.forEach(function(department) {
                                    departmentDropdown.append(
                                        `<option value="${department.id}">${department.name}</option>`
                                    );
                                });
                            } else {
                                alert('Failed to fetch departments.');
                            }
                        },
                        error: function(xhr) {
                            alert('An error occurred while fetching departments.');
                        }
                    });
                }
            });

            $('#department').on('change', function() {
                let deptId = $(this).val();
                let sectionDropdown = $('#section');
                let positionDropdown = $('#position');
                let employeesDropdown = $('#reporting_to');

                // Clear department dropdown
                sectionDropdown.empty().append('<option value="">Select Section</option>');
                positionDropdown.empty().append('<option value="">Select Position</option>');
                employeesDropdown.empty().append('<option value="">Reporting To</option>');

                if (deptId) {
                    $.ajax({
                        url: "{{ route('sections.get', ':deptid') }}".replace(':deptid', deptId),
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                // Populate departments dropdown
                                response.sections.forEach(function(section) {
                                    sectionDropdown.append(
                                        `<option value="${section.id}">${section.name}</option>`
                                    );
                                });
                            } else {
                                alert('Failed to fetch sections.');
                            }
                        },
                        error: function(xhr) {
                            alert('An error occurred while fetching sections.');
                        }
                    });

                    $.ajax({
                        url: "{{ route('positions.get', ':deptid') }}".replace(':deptid', deptId),
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                // Populate departments dropdown
                                response.positions.forEach(function(position) {
                                    positionDropdown.append(
                                        `<option value="${position.id}">${position.position_title}</option>`
                                    );
                                });
                            } else {
                                alert('Failed to fetch positions.');
                            }
                        },
                        error: function(xhr) {
                            alert('An error occurred while fetching positions.');
                        }
                    });

                    $.ajax({
                        url: "{{ route('reporting.employees.get', ':deptid') }}".replace(':deptid', deptId),
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                // Populate departments dropdown
                                response.reporting_employees.forEach(function(emp) {
                                    employeesDropdown.append(
                                        `<option value="${emp.id}">${emp.first_name} ${emp.last_name}</option>`
                                    );
                                });
                            } else {
                                alert('Failed to fetch reporting to.');
                            }
                        },
                        error: function(xhr) {
                            alert('An error occurred while fetching reporting to.');
                        }
                    });
                }
            });

            $('#service_provider').on('change', function() {
                toggleInput();
            });

            $('#new_service_provider').on('input', function() {
                toggleInput();
            });

            // Function to toggle between select box and textbox
            function toggleInput() {
                const inputField = $('#new_service_provider');
                const selectBox = $('#service_provider');

                // Ensure elements exist
                if (inputField.length === 0 || selectBox.length === 0) {
                    console.error('Input or select element is missing.');
                    return;
                }

                const inputValue = inputField.val()?.trim(); // Safe navigation to prevent undefined
                const selectValue = selectBox.val();

                if (inputValue) {
                    selectBox.val('').prop('disabled', true);
                    inputField.prop('disabled', false);
                } else if (selectValue) {
                    inputField.val('').prop('disabled', true);
                    selectBox.prop('disabled', false);
                } else {
                    inputField.prop('disabled', false);
                    selectBox.prop('disabled', false);
                }
            }

            $('#position').on('change', function() {
                var positionId = $(this).val();
                if (positionId) {
                    $.ajax({
                        url: '{{ route("resort.getRank") }}',
                        type: 'GET',
                        data: { positionId: positionId },
                        success: function(response) {
                            $('#txt-rank').val(response.rank || ''); // Set the rank if available, else empty
                            $('#rank_id').val(response.rank_id)
                        },
                        error: function() {
                            console.error("An error occurred while fetching the rank.");
                        }
                    });
                } else {
                    $('#txt-rank').val(''); // Clear rank field if no position selected
                    $('#rank_id').val('');
                }
            });

            document.querySelectorAll('input[name="employee_type"]').forEach((radio) => {
                radio.addEventListener('change', function() {
                    const employmentType = this.value;

                    // Div elements
                    const permanentDiv = document.getElementById('permanent-div');
                    const tempDiv = document.getElementById('temp-div');
                    const replacementEmployee = document.getElementById('replacement-employee');

                    // Reset visibility
                    permanentDiv.style.display = 'none';
                    tempDiv.style.display = 'none';
                    replacementEmployee.style.display = 'none';

                    // Show/hide based on selection
                    if (employmentType === 'Permanant' || employmentType === 'Replacement') {
                        permanentDiv.style.display = 'block';
                    }
                    if (employmentType === 'Replacement') {
                        replacementEmployee.style.display = 'block';
                    }
                    if (employmentType === 'Casual/Agency' || employmentType === 'Trainee / Intern' || employmentType === 'Temporary / Project') {
                        tempDiv.style.display = 'block';
                        toggleInput();
                    }
                });
            });

            function updateVacancyStatus(positionId, requestedVacancy) {
                $.ajax({
                    url: '{{route("resort.vacancies.getstatus")}}',
                    method: 'POST',
                    data: {
                        position_id: positionId,
                        requested_vacancy: requestedVacancy
                    },
                    success: function(response) {
                        const selectBox = $('#vacancy_status');
                        selectBox.empty(); // Clear existing options

                        if (response.status === 'Budgeted') {
                            selectBox.append('<option value="Budgeted">Budgeted</option>');
                        } else {
                            selectBox.append('<option value="Out of Budget">Out of Budget</option>');
                        }

                        $('#txt-budget-salary').val(response.budgeted_salary);
                        $('#txt-proposed-salary').val(response.proposed_salary);
                        $('#txt-Pension').val(response.pension);
                        $('#txt-allowances').val(response.allowance);
                        $('#txt-Medical').val(response.medical);
                        $('#txt-acommocation2').val(response.accommodation);
                        $('#txt-Insurance').val(response.insurance);
                    },
                    error: function() {
                        alert('Error fetching vacancy status.');
                    }
                });
            }

            // Trigger when the requested vacancy changes
            $('#Total_position_required').on('change', function() {
                const positionId = $('#position').val();
                const requestedVacancy = $(this).val();
                if (positionId && requestedVacancy) {
                    updateVacancyStatus(positionId, requestedVacancy);
                }
            });

            var current_fs, next_fs, previous_fs; //fieldsets
            var opacity;
            var current = 1;
            var steps = $("fieldset").length;
            // setProgressBar(current);
            $(".next").click(function (e) {
                e.preventDefault();

                // Get current fieldset
                var $currentFieldset = $(this).closest("fieldset");
                // current_fs = $(this).parent();

                // Validate the current step
                var isValid = true;
                $currentFieldset.find('input, select, textarea').each(function () {
                    var fieldValidation = $(this).parsley();
                    if (fieldValidation.validate() !== true) {
                        isValid = false;
                    }
                });

                if (isValid) {
                    // Save data via AJAX
                    var stepData = $currentFieldset.find('input, select, textarea').serialize();
                    stepData += '&step=' + $currentFieldset.data('step'); // Add step identifier

                    $.ajax({
                        // url: '/applicant-form/store-step',
                        url : '{{route("save.applicantinfo.draft")}}',
                        method: 'POST',
                        data: stepData,
                        success: function (response) {
                            if (response.success) {
                                // Move to the next fieldset
                                var next_fs = $currentFieldset.next("fieldset");
                                $currentFieldset.hide();
                                
                                $("#progressbar li").eq($("fieldset").index($currentFieldset)).removeClass("current");
                                //Add Class Active
                                $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active current");
                                next_fs.show();
                                $currentFieldset.animate({ opacity: 0 }, {
                                    step: function (now) {
                                        // for making fielset appear animation
                                        opacity = 1 - now;

                                        $currentFieldset.css({
                                            'display': 'none',
                                            'position': 'relative'
                                        });
                                        next_fs.css({ 'opacity': opacity });
                                    },
                                    duration: 500
                                });

                            } else {
                                alert(response.message);
                            }
                        },
                        error: function () {
                            alert('Error saving step data.');
                        }
                    });
                }
            });

            $(".previous").click(function () {
                var current_fs = $(this).closest("fieldset");
                var previous_fs = current_fs.prev("fieldset");
                var step = previous_fs.data('step'); // Step identifier for previous fieldset

                // Fetch draft data for the step via AJAX
                $.ajax({
                    url: '{{ route("get.applicantinfo.draft") }}',
                    method: 'POST',
                    data: { step: step },
                    success: function (response) {
                        if (response.success) {
                            // Populate the previous fieldset with retrieved data
                            for (const [key, value] of Object.entries(response.data)) {
                                const input = previous_fs.find(`[name="${key}"]`);

                                if (input.length) {  // Ensure the input exists in the fieldset
                                    if (input.is(':checkbox')) {
                                        // For checkboxes, handle multiple selected options
                                        if (Array.isArray(value)) {
                                            value.forEach(val => {
                                                previous_fs.find(`[name="${key}"][value="${val}"]`).prop('checked', true);
                                            });
                                        } else {
                                            previous_fs.find(`[name="${key}"][value="${value}"]`).prop('checked', true);
                                        }
                                    } else if (input.is(':radio')) {
                                        // For radio buttons, select the checked radio
                                        previous_fs.find(`[name="${key}"][value="${value}"]`).prop('checked', true);
                                    } else {
                                        // For text, select, and textarea fields
                                        input.val(value);
                                    }
                                }
                            }
                        }

                        // Update progress bar
                        var index = $("fieldset").index(current_fs);
                        $("#progressbar li").eq(index).removeClass("current active");
                        $("#progressbar li").eq(index - 1).addClass("current");

                        // Animate transition
                        previous_fs.show();
                        current_fs.animate({ opacity: 0 }, {
                            step: function (now) {
                                var opacity = 1 - now;
                                current_fs.css({ display: 'none', position: 'relative' });
                                previous_fs.css({ opacity: opacity });
                            },
                            duration: 500
                        });
                    },
                    error: function () {
                        alert('Error retrieving draft data.');
                    }
                });
            });
        });  

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

        // Date Picker Initialization
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

        // CSS for Dropzone and Validation
        const validationStyles = `
            <style>
            .drop-zone {
                border: 2px dashed #ccc;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .drop-zone--over {
                background-color: rgba(0, 0, 0, 0.1);
                border-color: #000;
            }

            .drop-zone__thumb {
                width: 100%;
                height: 200px;
                background-size: cover;
                background-position: center;
                position: relative;
            }

            .drop-zone__thumb::after {
                content: attr(data-label);
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                background: rgba(0,0,0,0.5);
                color: white;
                padding: 5px;
                text-align: center;
            }

            .is-invalid {
                border-color: #dc3545;
            }

            .invalid-feedback {
                color: #dc3545;
                display: block;
                margin-top: 5px;
            }
            </style>
            `;

    
        // Inject styles
        document.head.insertAdjacentHTML('beforeend', validationStyles);

        //for passport size photo
        $image_crop_profile = $('#profile_picture_preview').croppie({
            enableOrientation: true,
            viewport: {
                width:200,
                height:200,
                type:'circle' //square
            },
            boundary:{
                width:300,
                height:300
            }
        });

        //for full length size photo
        $image_crop = $('#profile_picture_preview_full_img').croppie({
            enableOrientation: true,
            viewport: {
                width:200,
                height:200,
                type:'square' //square
            },
            boundary:{
                width:300,
                height:300
            }
        });

        /**** on selecting passport size photo from file input, bind it with croppie preview ***/
        $('#profile_picture').on('change', function(){
            var reader = new FileReader();
            reader.onload = function (event) {
                $image_crop_profile.croppie('bind', {
                    url: event.target.result
                }).then(function(){
                    console.log('jQuery bind complete');
                });
            }
            reader.readAsDataURL(this.files[0]);
            $('#uploadimageModal').modal('show');
        });

        $('.crop-picture').click(function(event){
            $image_crop_profile.croppie('result', {
                type: 'canvas',
                size: 'viewport'
            }).then(function(response){
                $('#profilePicturePreview').attr("src",response);
                $('#uploadimageModal').modal('hide');
            })
        });

        
        /**** on selecting full length photo from file input, bind it with croppie preview ***/
        $('#full_length_photos').on('change', function(){
            var reader = new FileReader();
            reader.onload = function (event) {
                $image_crop.croppie('bind', {
                    url: event.target.result
                }).then(function(){
                    console.log('jQuery bind complete');
                });
            }
            reader.readAsDataURL(this.files[0]);
            $('#uploadimageModal_fullImg').modal('show');
        });

        $('.crop_picture_full_img').click(function(event){
            $image_crop.croppie('result', {
                type: 'canvas',
                size: 'viewport'
            }).then(function(response){
                $('#profilePreviewfullimg').attr("src",response);
                $('#uploadimageModal_fullImg').modal('hide');
            })
        });
        document.addEventListener('DOMContentLoaded', function () {
            const hrRoundComplete = document.getElementById('hrRoundComplete');
            const hodRoundComplete = document.getElementById('hodRoundComplete');
            const gmRoundComplete = document.getElementById('gmRoundComplete');

            hrRoundComplete.addEventListener('click', function (e) {
                e.preventDefault();
                // Enable HOD Round button
                hodRoundComplete.removeAttribute('disabled');
            });

            hodRoundComplete.addEventListener('click', function (e) {
                e.preventDefault();
                // Enable GM Round button
                gmRoundComplete.removeAttribute('disabled');
            });

            gmRoundComplete.addEventListener('click', function (e) {
                e.preventDefault();
                alert('GM Round Completed.');
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
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

            // Initialize Parsley Validation
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
                            return /^[0-9]{10}$/.test(value);
                        },
                        messages: {
                            en: 'Please enter a valid 10-digit mobile number.'
                        }
                    });

                    window.Parsley.addValidator('passport_no', {
                        validateString: function(value) {
                            // Adjust regex as needed for passport number format
                            // This regex ensures:
                            // - Starts with 1 or more uppercase letters (A-Z)
                            // - Followed by 6 to 9 digits (0-9)
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
                            return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
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

                            if (!validTLDs.includes(domainParts[0]) || (domainParts[0] === 'co' && !validTLDs.includes(domainParts[1]))) {
                            return false;
                            }

                            return true;
                        },
                        messages: {
                            en: 'Invalid email address'
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
                            return endDate.isAfter(startDate, 'day'); // Ensure day-level comparison
                        },
                        messages: {
                            en: 'End Date must be greater than Start Date.'
                        }
                    });

                    // Manage the "Currently Working Here" and "End Date" logic
                    $('.currently-working-checkbox').on('change', function () {
                        if ($(this).is(':checked')) {
                            $('#txt-end-date').prop('disabled', true).val('').parsley().reset();
                        } else {
                            $('#txt-end-date').prop('disabled', false);
                        }
                        calculateExperience(); // Recalculate experience
                    });

                }
            }

            // Dropzone Initialization with Validation
            function initDropzoneValidation() {
                document.querySelectorAll(".drop-zone__input").forEach(inputElement => {
                    const dropZoneElement = inputElement.closest(".drop-zone");
                    // console.log(dropZoneElement,"dropZoneElement");

                    // Click to open file selector
                    dropZoneElement.addEventListener("click", (e) => {
                        if (e.target.closest('.drop-zone__input')) return;
                        inputElement.click();
                    });

                    // Handle file selection
                    inputElement.addEventListener("change", (event) => {
                        if (inputElement.files.length) {
                            const file = inputElement.files[0];
                            const fileType = getFileType(inputElement);
                            
                            // Validate file
                            if (validateFile(file, fileType, inputElement)) {
                                updateDropzoneThumbnail(dropZoneElement, file);
                                // console.log(file,"file");
                                
                                // Trigger Parsley validation
                                $(inputElement).parsley().validate();
                            } else {
                                // Clear the input if validation fails
                                inputElement.value = '';
                                $(inputElement).parsley().validate();
                            }
                        }
                    });

                    // Drag and drop event handlers
                    ["dragover", "dragleave", "dragend", "drop"].forEach(eventType => {
                        dropZoneElement.addEventListener(eventType, e => {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (eventType === "dragover") {
                                dropZoneElement.classList.add("drop-zone--over");
                            } else {
                                dropZoneElement.classList.remove("drop-zone--over");
                            }

                            // Handle drop event
                            if (eventType === "drop") {
                                const droppedFiles = e.dataTransfer.files;
                                if (droppedFiles.length) {
                                    inputElement.files = droppedFiles;
                                    const file = droppedFiles[0];
                                    const fileType = getFileType(inputElement);
                                    
                                    if (validateFile(file, fileType,inputElement)) {
                                        updateDropzoneThumbnail(dropZoneElement, file);
                                        $(inputElement).parsley().validate();
                                    } else {
                                        inputElement.value = '';
                                        $(inputElement).parsley().validate();
                                    }
                                }
                            }
                        });
                    });
                });

                // Determine file type
                function getFileType(inputElement) {
                    const name = inputElement.name;
                    if (name === 'curriculum_file' || name === 'passport') return 'PDF';
                    if (name === 'profile_picture') return 'Photo';
                    return 'Unknown';
                }

                // File Validation
                function validateFile(file, fileType, inputElement) {
                    // Find or create an error message container below the specific input
                    let errorMessageContainer = inputElement.nextElementSibling;
                    if (!errorMessageContainer || !errorMessageContainer.classList.contains("error-message-container")) {
                        errorMessageContainer = document.createElement("div");
                        errorMessageContainer.classList.add("error-message-container");
                        inputElement.parentNode.insertBefore(errorMessageContainer, inputElement.nextSibling);
                    }

                    // Clear previous error messages for this input
                    errorMessageContainer.innerHTML = '';

                    // Check if a file is selected
                    if (!file) {
                        showErrorMessage(`Please select a ${fileType} file.`, errorMessageContainer);
                        return false;
                    }

                    // PDF Validation
                    if (fileType === 'PDF' && file.type !== 'application/pdf') {
                        showErrorMessage(`Please upload only PDF files for ${fileType}.`, errorMessageContainer);
                        return false;
                    }

                    // Image Validation
                    if (fileType === 'Photo') {
                        const validImageTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                        if (!validImageTypes.includes(file.type)) {
                            showErrorMessage('Please upload only JPEG, PNG, or JPG images.', errorMessageContainer);
                            return false;
                        }

                        // File size validation (max 5MB)
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        if (file.size > maxSize) {
                            showErrorMessage('File size should not exceed 5MB.', errorMessageContainer);
                            return false;
                        }
                    }

                    // If validation passes, clear any previous error message
                    errorMessageContainer.innerHTML = ''; // Clear any previous error messages
                    return true;
                }

                // Function to show error messages
                function showErrorMessage(message, container) {
                    const errorMessageElement = document.createElement("div");
                    errorMessageElement.classList.add("error-message");
                    errorMessageElement.textContent = message;

                    container.appendChild(errorMessageElement);
                }

                // Update Dropzone Thumbnail
                function updateDropzoneThumbnail(dropZoneElement, file) {
                    // Remove existing prompt
                    const promptElement = dropZoneElement.querySelector(".drop-zone__prompt");
                    if (promptElement) {
                        promptElement.style.display = 'none';
                    }

                    // Create or find thumbnail element
                    let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");
                    if (!thumbnailElement) {
                        thumbnailElement = document.createElement("div");
                        thumbnailElement.classList.add("drop-zone__thumb");
                        dropZoneElement.appendChild(thumbnailElement);
                    }

                    // Set file name
                    thumbnailElement.dataset.label = file.name;

                    // Handle image preview
                    if (file.type.startsWith("image/")) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
                            
                            // Update specific image preview areas
                            if (dropZoneElement.querySelector('#profilePicturePreview')) {
                                dropZoneElement.querySelector('#profilePicturePreview').src = reader.result;
                            }
                            if (dropZoneElement.querySelector('#profilePreviewfullimg')) {
                                dropZoneElement.querySelector('#profilePreviewfullimg').src = reader.result;
                            }
                        };
                        reader.readAsDataURL(file);
                    } else {
                        thumbnailElement.style.backgroundImage = null;
                    }
                }
            }

            // Alpha-only Input Handling
            function initAlphaOnlyInputs() {
                $('.alpha-only').on('keyup blur', function() {
                    $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
                });
            }

            // Phone Number Formatting
            function initPhoneNumberFormatting() {
                $('#txt-mobile-number').on('keyup blur', function() {
                    $(this).val($(this).val().replace(/[^0-9]/g, ''));
                });
            }
            

            // Form Submission Handling
            function initFormSubmission() {
                $('#msform').on('submit', function(e) {
                    // Prevent default submission
                    e.preventDefault();

                    // Validate entire form
                    const form = $(this);
                    if (form.parsley().validate()) {
                        // All validations passed
                        var formData = new FormData(this);

                        // const tempVideoRef = $('#tempVideoReference').val();
                        // if (tempVideoRef) {
                        //     formData.append('temp_video_reference', tempVideoRef);
                        // }
                        console.log(formData);
                        // Disable submit button to prevent multiple submissions
                        $('#submit')
                            .prop('disabled', true)
                            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

                        // Ajax submission
                        $.ajax({
                            url: '{{ route('resort.applicantFormstore') }}', // Your submission route
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                console.log(response);
                                // Handle successful submission
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
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
                                toastr.success(errorMessage, "Error", {
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
                initDropzoneValidation();
                initDatePicker();
                initAlphaOnlyInputs();
                initPhoneNumberFormatting();
                initFormSubmission();
            }

            // Call initialization when document is ready
            $(document).ready(initializeFormValidation);
        });
    </script>
@endsection