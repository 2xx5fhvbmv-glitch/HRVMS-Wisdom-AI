@extends('resorts.applicant_form.layouts.app')
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
            <div class="row justify-content-center g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="card">
                <form id="msform" enctype="multipart/form-data">
                    @csrf
                    <!-- progressbar -->
                    <div class="progressbar-wrapper">
                        <ul id="progressbar"
                            class="progressbar-tab d-flex justify-content-between align-items-center">
                            <li class="active current"> <span>Applicant Information</span></li>
                            <li><span>Work Experience</span></li>
                            <li><span>Education</span></li>
                            <li><span>Job Assessment</span></li>
                            <li><span>Preliminary questions</span></li>
                            <li><span>Consent and Privacy</span></li>
                        </ul>
                    </div>
                    <fieldset data-step="1">
                        <div class=" pb-md-5 pb-4">
                            <div class="card-title">
                                <div class="row justify-content-start align-items-center g-4">
                                    <div class="col">
                                        <h3>My Information</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-4 g-3">
                                <input type="hidden" name="source" id="source" value="{{$source}}"/>
                                <input type="hidden" name="resort_id" id="resort_id" value="{{$resort_id}}"/>
                                <input type="hidden" name="vacancy_id" id="vacancy_id" value="{{$v_id}}"/> 
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
                                        <input type="file" id="fileInput" name="passport" class="drop-zone__input"  data-parsley-required="true" data-parsley-required-message="Please upload your passport" >
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
                                    <div class="input-group">
                                        <select class="form-select select2t-none" name="country_phone_code" id="country-phone-code" style="max-width: 120px;" required>
                                            <option value="">Code</option>
                                            @foreach($countries as $country)
                                                <option value="+{{ $country->phonecode }}">{{ $country->shortname }} +{{ $country->phonecode }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="form-control" name="mobile_number" id="txt-mobile-number" placeholder="Mobile Number" required data-parsley-type="digits" data-parsley-trigger="change">
                                    </div>
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
                                    <label for="city" class="form-label">CITY<span class="red-mark">*</span></label>
                                    <input type="text" name="city" id="city" class="form-control alpha-only" placeholder="City" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed."/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="state" class="form-label">STATE</label>
                                    <input type="text" name="state" id="state" class="form-control alpha-only" placeholder="State"/>
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
                                    <label for="select-pin-code" class="form-label">PIN CODE<span class="red-mark">*</span></label>
                                    <input type="number" name="pin_code" value="" class="form-control" id="select-pin-code" placeholder="Pin Code" required data-parsley-trigger="change"/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="passport_no" class="form-label">PASSPORT NO<span class="red-mark">*</span></label>
                                    <input type="text" name="passport_no" value="" class="form-control" id="passport_no" placeholder="Passport No" required data-parsley-trigger="change" data-parsley-passport_no/>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="passport_expiry_date" class="form-label">PASSPORT EXPIRY DATE<span class="red-mark">*</span></label>
                                    <input type="text" name="passport_expiry_date" class="form-control datepicker" id="passport_expiry_date" placeholder="Passport Expiry Date" required data-parsley-trigger="change"/>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                    </fieldset>
                    <fieldset data-step="2">
                        <div class=" pb-md-5 pb-4">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Work Experience</h3>
                                    </div>
                                    <div class="col text-end">
                                        <button id="rowAdder" type="button" class="btn btn-themeSkyblue btn-sm">Add Work Experience
                                        </button>
                                    </div>
                                </div>
                            </div>
                                <input type="hidden" id="incrementcountry" value="1">
                                <input type="hidden" id="incrementEducation" value="1">
                            <div class="row g-md-4 g-3 work-experience-row">
                                <div class="col-md-6 ">
                                    <label for="txt-job-title" class="form-label">JOB TITLE*<span class="red-mark">*</span></label>
                                    <input type="text" class="form-control alpha-only" id="txt-job-title" placeholder="Job Title" name="job_title[]" value="" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-employer-name" class="form-label">EMPLOYER
                                        NAME<span class="red-mark">*</span></label>
                                    <input type="text" class="form-control alpha-only" id="txt-employer-name"
                                        placeholder="Employer Name" name="employer_name[]" value="" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                        data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="select-location-city" class="form-label">LOCATION (CITY & COUNTRY)
                                        <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="select-location-country"
                                        aria-label="Default select example" name="work_country_name[]" data-parsley-required="true" data-parsley-errors-container="#work-country-error">
                                        <option value="">Country </option>
                                        @foreach($countries as $country)
                                            <option value="{{$country->id}}">{{$country->name}}</option>
                                        @endforeach
                                    </select>
                                    <div id="work-country-error"></div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="select-location-country "
                                        class="form-label d-md-inline-block d-none">CITY</label>
                                    <input type="text" name="work_city[]" id="select-location-city" class="form-control alpha-only" placeholder="City" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed."/>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txt-start-date" class="form-label">START DATE<span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker txt-start-date" id="txt-start-date"
                                        placeholder="DD/MM/YYYY" name="work_start_date[]" data-parsley-required="true"
                                        data-parsley-trigger="change" data-parsley-date-format="dd/mm/yyyy">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txt-end-date" class="form-label">END DATE<span class="red-mark">*</span></label>
                                    <input type="text" class="form-control datepicker txt-end-date Enddate_0" id="txt-end-date"
                                        placeholder="DD/MM/YYYY" name="work_end_date[]" disabled
                                        data-parsley-date-format="dd/mm/yyyy" data-parsley-trigger="change"  data-parsley-endgreaterthanstart="#txt-start-date" >
                                </div>
                                <div class="col-lg-6">
                                    <label for="txt-job-description" class="form-label">JOB DESCRIPTION</label>
                                    <textarea class="form-control" id="txt-job-description"
                                        placeholder="Type Here.." rows="3" name="job_description_work[]" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                        data-parsley-validate-script-message="Script tags are not allowed."></textarea>   
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input currently-working-checkbox" data-id="1" type="checkbox" id="currently-working-checkbox-1"
                                            name="currently_working[]" checked value="1">
                                        <label class="form-check-label" for="currently-working-checkbox">
                                            Currently working here
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="total-experience" class="form-label">Total Experience (Years)</label>
                                    <input type="text" id="total-experience" class="form-control total-experience" name="total_experience[]" >
                                </div>
                            </div>
                        </div>
                        <div id="newinput"></div>
                        <hr class="hr-footer">
                        <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-step="3">
                        <div class=" pb-md-5 pb-4">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Education</h3>
                                    </div>
                                    <div class="col text-end">
                                        <button id="rowAdderEducation" type="button" class="btn btn-themeSkyblue btn-sm">Add Education</button>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-4 g-3">
                                <div class="col-md-6 ">
                                    <label for="txt-institute-name" class="form-label">INSTITUTE
                                        NAME<span class="red-mark">*</span></label>
                                    <input type="text" name="institute_name[]" class="form-control  alpha-only" id="txt-institute-name" placeholder="Institute Name" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-educational-level" class="form-label">EDUCATIONAL
                                        LEVEL<span class="red-mark">*</span></label>
                                    <input type="text" name="educational_level[]" class="form-control alpha-only" id="txt-educational-level" placeholder="Educational Level" required data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="select-country1 "
                                        class="form-label d-md-inline-block d-none">COUNTRY<span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="select-country1"
                                        aria-label="Default select example" name="country_educational[]" data-parsley-required="true" data-parsley-errors-container="#select-country1-error">
                                        <option value="">Country </option>
                                        @foreach($countries as $country)
                                            <option value="{{$country->id}}">{{$country->name}}</option>
                                        @endforeach
                                    </select>
                                    <div id="select-country1-error"></div>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="select-city1" class="form-label">CITY
                                        <span class="red-mark">*</span></label>
                                    <input type="text" name="city_educational[]" id="select-city1" class="form-control alpha-only" placeholder="City" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed."/>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        <div id="newinputEducation"></div>
                        <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-step="4">
                        <div class="pb-md-5 pb-4 job-assessment">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Job Assessment</h3>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="resort_id" id="resort_id" value="{{$resort_id}}"/>
                            <div class="row g-md-4 g-3">
                                @foreach($get_questionnaireChild as $key => $questionnaireChild)
                                    <div class="col-md-6">
                                        <label for="txt-textarea-1" class="form-label"> {{$key + 1}}. {{@$questionnaireChild->Question}}</label>
                                        @if(@$questionnaireChild->questionType == 'single')
                                            <textarea class="form-control" name="question_{{$questionnaireChild->id}}" id="txt-textarea-{{$key}}" placeholder="Type Here.." rows="2" required data-parsley-trigger="change"></textarea>
                                        @elseif(@$questionnaireChild->questionType == 'Radio')
                                            <ul class="nav flex-column">
                                                @php $dataarray = json_decode($questionnaireChild->options, TRUE) ?? []; @endphp
                                                @foreach($dataarray as $keyj => $dataredio)
                                                    <li class="form-radio">
                                                        <input 
                                                            class="form-radio-input" 
                                                            type="radio" 
                                                            value="{{$dataredio}}" 
                                                            id="radio-{{$key}}{{$keyj}}" 
                                                            name="question_{{$questionnaireChild->id}}" 
                                                            data-parsley-required="true" 
                                                            data-parsley-error-message="Please select an option for this question.">
                                                        <label class="form-radio-label" for="radio-{{$key}}{{$keyj}}">{{$dataredio}}</label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @elseif(@$questionnaireChild->questionType == 'multiple')
                                            <ul class="nav flex-column">
                                                @php $dataarray_multi = json_decode($questionnaireChild->options, TRUE) ?? []; @endphp
                                                @foreach($dataarray_multi as $key => $datamulti)
                                                    <li class="form-check">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox" 
                                                            name="question_{{$questionnaireChild->id}}[]" 
                                                            value="{{$datamulti}}" 
                                                            id="check-{{$key}}{{$questionnaireChild->id}}" 
                                                            data-parsley-multiple="question_{{$questionnaireChild->id}}" 
                                                            data-parsley-mincheck="1" 
                                                            data-parsley-error-message="Please select at least one option.">
                                                        <label class="form-check-label" for="check-{{$key}}{{$questionnaireChild->id}}">{{$datamulti}}</label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                                <!-- Video Assessment Section -->
                                <div class="row g-md-4 g-3">
                                    @if($get_questionnaire->video == "Yes")
                                        @foreach($get_questionnaireVideo as $key => $questionnaireVideo)
                                            <div class="col-md-6">
                                                @foreach($get_Languages as $get_Languagesdata)
                                                    @if($get_Languagesdata['id'] == @$questionnaireVideo->lang_id)
                                                        <label for="txt-institute-name" class="form-label">
                                                            {{$get_Languagesdata['name']}} LANGUAGE TEST
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        
                                            <div class="col-md-6">
                                                <a href="#startAssessment-modal-{{$key}}" data-bs-toggle="modal" class="btn btn-themeBlue btn-sm">Start Assessment</a>
                                                <span id="Append_startAssessment-modal-{{$key}}"> </span>

                                            </div>

                                            <input type="hidden" name="assessment_completed_{{$key}}" id="assessment_completed_{{$key}}" data-key="{{$key}}" data-parsley-required="true" class="" data-parsley-error-message="Please complete the assessment.">

                                            <input type="hidden" name="tempVideoReference_{{$key}}" id="tempVideoReference_{{$key}}" data-key="{{$key}}">

                                            <div class="modal fade" id="startAssessment-modal-{{$key}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-xl modal-lanTest">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="staticBackdropLabel">Language Test</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            @php $get_questionnaireList = App\Models\VideoQuestion::where('Q_Parent_id', $get_questionnaire->id)->where('lang_id', $questionnaireVideo->lang_id)->get(); @endphp
                                                            @foreach($get_questionnaireList as $videoQts)
                                                                <div class="mb-3">
                                                                    <p> {{@$videoQts->VideoQuestion}} </p>
                                                                    <input type="hidden" name="video_questionid_{{$key}}" id="video_questionid_{{$key}}" value="{{$videoQts->id}}"/>
                                                                </div>
                                                            @endforeach

                                                            <div class="ratio ratio-16x9 mb-3">
                                                                <div style="background-color: #000;"></div>
                                                                <video id="previewVideo-{{$key}}" autoplay muted></video>
                                                                <video id="recordedVideo-{{$key}}" style="display: none;"></video>

                                                            </div>

                                                            <div class="row align-items-center justify-content-center g-md-3 g-2">
                                                                <div class="col-auto">
                                                                    <button id="startRecord-{{$key}}" onclick="startRecording({{$key}})"  class="btn btn-themeSkyblue btn-sm">Start Recording</button>                        
                                                                </div>

                                                                <div class="col-auto">
                                                                    <button id="stopRecord-{{$key}}" onclick="stopRecording({{$key}})" disabled  class="btn btn-themeSkyblue btn-sm">Stop Recording</button>
                                                                </div>

                                                                <div class="col-auto">OR</div>

                                                                <div class="col-auto">
                                                                    <label for="fileInput-{{$key}}" class="btn btn-themeBlue btn-sm" >Upload File</label>
                                                                    <input type="file" name="video_file[]" id="fileInput-{{$key}}" style="display: none;" onchange="handleFileSelection(event, {{$key}})" >
                                                                </div> 
                                                        
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                                                            <a href="#" id="submitRecord-{{$key}}" class="btn btn-themeBlue submitVideo" data-id="{{$key}}" >Submit</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                        <a href="#" class="btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-step="5">
                        <div class="pb-4">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Preliminary questions</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6 ">
                                    <label for="txt-joining-availability" class="form-label">JOINING
                                        AVAILABILITY<span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" name="Joining_availability" id="txt-joining-availability" placeholder="Joining Availability" data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed." required>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="txt-reference" class="form-label">REFERENCE<span class="red-mark">*</span></label>
                                    <input type="text" name="reference" class="form-control" id="txt-reference" placeholder="Reference" required data-parsley-validate-script
                                    data-parsley-validate-script-message="Script tags are not allowed.">
                                </div>
                                <div class="col-md-6 ">
                                    <label for="notice_period" class="form-label">Notice Periods (In Days)<span class="red-mark">*</span></label>
                                    <input type="number" min="0" name="notice_period" class="form-control" id="notice_period" placeholder="Notice Periods (In Days)" required>
                                </div>
                                <div class="col-md-6 ">
                                    <label for="expected_salary" class="form-label">Salary Expectation<span class="red-mark">*</span></label>
                                    <input type="text" min="0" name="expected_salary" class="form-control" id="expected_salary" placeholder="Salary Expectation" required>
                                </div>
                            </div>
                        </div>
                        <div class="pb-4">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Language</h3>
                                    </div>
                                    <div class="col text-end">
                                        <button id="rowAdderlanguage" type="button" class="btn btn-themeSkyblue btn-sm">Add Language</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="incrementlanguage" value="1">
                            
                            <div class="row g-md-4 g-3">
                                <div class="col-md-3 ">
                                    <label for="select-level" class="form-label"> LANGUAGE <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" name="preliminary_language[]" id="select-language" aria-label="Default select example" required >
                                        <option value="">Select Language</option>
                                        @foreach($get_Languages as $get_Languagesdata)
                                            <option value="{{$get_Languagesdata->name}}" >{{@$get_Languagesdata->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 ">
                                    <label for="select-level" class="form-label">SELECT THE LEVEL <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" name="select_level[]" id="select-level"
                                        aria-label="Default select example">
                                        @foreach($levelList as $levelListdata)
                                            <option value="{{$levelListdata}}">{{$levelListdata}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="newinputlanguage"></div>
                        <hr class="hr-footer">

                        <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                        <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                    <fieldset data-step="6">
                        <div class="pb-4 terms-conditions-box">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Terms and Conditions</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4">
                                {!! $termsAndCondition->terms_and_condition !!}
                                <div class="col-md-12">
                                    <div class="form-check ">
                                        <input class="form-check-input" type="checkbox" name="terms_conditions" required=""
                                            value="1" id="flexCheckservicechares-yes">
                                        <label class="form-check-label"  for="flexCheckservicechares-yes">
                                            I have accept the terms & Conditions
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h5>Data Retention</h5>
                                <div class="row g-md-4 g-3">
                                    <div class="col-lg-3 col-md-6 ">
                                        <select class="form-select select2t-none" id="select-month" name="select_months"
                                            aria-label="Default select example">
                                            <option value="">Select in months </option>
                                            @for($i=1;$i<=12;$i++)
                                                <option value="{{$i}}">{{$i}}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 ">
                                        <select class="form-select select2t-none" name="select_years" id="select-year"
                                            aria-label="Default select example">
                                            <option value="">Select in years </option>
                                            @for($i=1;$i<=5;$i++)
                                                <option value="{{$i}}">{{$i}}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr-footer">
                        <button type="submit" class="btn btn-themeBlue btn-sm float-end " style="margin-right: 10px;" id="submit">Submit</button>
                        <a href=" # " class="btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style type="text/css">
    .drop-zone {
        height: 200px;
        padding: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-family: "Quicksand", sans-serif;
        font-weight: 500;
        font-size: 20px;
        cursor: pointer;
        color: #cccccc;
    }

    .drop-zone--over {
        border-style: solid;
    }

    .drop-zone__input {
        display: none;
    }

    .drop-zone__thumb {
        width: 30%;
        height: 100%;
        border-radius: 10px;
        overflow: hidden;
        background-color: #cccccc;
        background-size: cover;
        position: relative;
    }

    .drop-zone__thumb::after {
        content: attr(data-label);
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 5px 0;
        color: #ffffff;
        background: rgba(0, 0, 0, 0.75);
        font-size: 14px;
        text-align: center;
    }

    .delete_button {
        width: 150px;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
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

        $('.datepicker').datepicker({format: 'dd/mm/yyyy', autoclose: true}).on('changeDate', function () {
            // Trigger Parsley validation when the date changes
            $(this).parsley().validate();
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
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error('Error saving step data.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
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
                    toastr.error('Error retrieving draft data.', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
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
            }).on('changeDate', function () {
                $(this).parsley().validate(); // Trigger validation on date change
            });
        }
    }

    function calculateExperience() {
        $('.work-experience-row').each(function () {
            // Get the start date and end date values
            var startDate = $(this).find('.txt-start-date').val();
            var endDate = $(this).find('.txt-end-date').val();
            var isCurrentlyWorking = $(this).find('.currently-working-checkbox').is(':checked');

            // If end date is empty and the checkbox is checked (currently working)
            if (isCurrentlyWorking && startDate) {
                endDate = new Date();  // Set end date to current date if still working
            }

            if (startDate && endDate) {
                // Parse the start and end dates if they are in string format
                var start;
                if (typeof startDate === 'string') {
                    start = new Date(startDate.split('/').reverse().join('-')); // Convert dd/mm/yyyy to yyyy-mm-dd
                } else {
                    start = new Date(startDate); // If it's already a date object
                }

                var end;
                if (typeof endDate === 'string') {
                    end = new Date(endDate.split('/').reverse().join('-')); // Convert dd/mm/yyyy to yyyy-mm-dd
                } else {
                    end = new Date(endDate); // If it's already a date object
                }

                // Calculate the difference in years, months, and days
                var years = end.getFullYear() - start.getFullYear();
                var months = end.getMonth() - start.getMonth();
                var days = end.getDate() - start.getDate();

                // Adjust for negative months or days (when the end date hasn't reached the same day or month)
                if (months < 0) {
                    years--;
                    months += 12;
                }
                if (days < 0) {
                    months--;
                    days += new Date(end.getFullYear(), end.getMonth(), 0).getDate(); // Get the last day of the previous month
                }

                // Display the total experience in the respective field
                var experienceText = years + " years, " + months + " months, " + days + " days";
                $(this).find('.total-experience').val(experienceText);
            }
        });
    }

    $(document).on('change', '.txt-start-date, .txt-end-date, .currently-working-checkbox', function () {
        calculateExperience(); 
    });


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

    
    document.head.insertAdjacentHTML('beforeend', validationStyles);

    // Passport-size photo preview
    $('#profile_picture').on('change', function(){
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (event) {
                $('#profilePicturePreview').attr("src", event.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Full-length photo preview
    $('#full_length_photos').on('change', function(){
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (event) {
                $('#profilePreviewfullimg').attr("src", event.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    //step 2: work experiance add 
    document.getElementById('rowAdder').addEventListener('click', function () {
        // Get the container for adding new inputs
        const container = document.getElementById('newinput');

        // Create a unique identifier for new entries (using Date.now() and a random number to reduce the collision risk)
        const uniqueId = Date.now() + '-' + Math.floor(Math.random() * 1000);

        // HTML template for a new work experience section
        const newRow = `
            <div class="work-experience-row row g-md-4 g-3 mb-4" id="workExperience-${uniqueId}">
                <div class="col-md-6">
                    <label for="txt-job-title-${uniqueId}" class="form-label">JOB TITLE<span class="red-mark">*</span></label>
                    <input type="text" class="form-control alpha-only" id="txt-job-title-${uniqueId}" placeholder="Job Title" name="job_title[]" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed.">
                </div>
                <div class="col-md-6">
                    <label for="txt-employer-name-${uniqueId}" class="form-label">EMPLOYER NAME<span class="red-mark">*</span></label>
                    <input type="text" class="form-control alpha-only" id="txt-employer-name-${uniqueId}" placeholder="Employer Name" name="employer_name[]" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed.">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="select-location-country-${uniqueId}" class="form-label">LOCATION (CITY & COUNTRY)<span class="red-mark">*</span></label>
                    <select class="form-select select2t-none" id="select-location-country-${uniqueId}" name="work_country_name[]" data-parsley-required="true" data-parsley-errors-container="#work-country-error-${uniqueId}">
                        <option value="">Country</option>
                        @foreach($countries as $country)
                            <option value="{{$country->id}}">{{$country->name}}</option>
                        @endforeach
                    </select>
                    <div id="work-country-error-${uniqueId}"></div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="select-location-city-${uniqueId}" class="form-label">CITY</label>
                    <input type="text" name="work_city[]" id="select-location-city-${uniqueId}" class="form-control alpha-only" placeholder="City" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed.">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="txt-start-date-${uniqueId}" class="form-label">START DATE<span class="red-mark">*</span></label>
                    <input type="text" class="form-control datepicker txt-start-date" id="txt-start-date-${uniqueId}" placeholder="DD/MM/YYYY" name="work_start_date[]" data-parsley-required="true" data-parsley-trigger="change">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="txt-end-date-${uniqueId}" class="form-label">END DATE</label>
                    <input type="text" class="form-control datepicker txt-end-date" id="txt-end-date-${uniqueId}" placeholder="DD/MM/YYYY" name="work_end_date[]" data-parsley-endgreaterthanstart="#txt-start-date-${uniqueId}">
                </div>
                <div class="col-lg-6">
                    <label for="txt-job-description-${uniqueId}" class="form-label">JOB DESCRIPTION</label>
                    <textarea class="form-control" id="txt-job-description-${uniqueId}" placeholder="Type Here.." rows="3" name="job_description_work[]" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."></textarea>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input currently-working-checkbox" type="checkbox" name="currently_working[]" id="flexCheckcurrently-working-checkbox-${uniqueId}"data-id="${uniqueId}" checked>
                        <label class="form-check-label" for="flexCheckcurrently-working-checkbox-${uniqueId}">
                            Currently working here
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="total-experience-${uniqueId}" class="form-label">Total Experience (Years)</label>
                    <input type="text" id="total-experience-${uniqueId}" class="form-control total-experience" name="total_experience[]" >
                </div>
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm mt-2 removeRow" data-row-id="${uniqueId}">Remove</button>
                </div>
            </div>
        `;

        // Append the new row
        container.insertAdjacentHTML('beforeend', newRow);

        initSelect2AndValidation(); 
        initDatePicker();
        $('#msform').parsley().destroy(); 
        $('#msform').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<div></div>',
            trigger: 'change'
        });

        $('.alpha-only').on('input', function () {
            this.value = this.value.replace(/[^a-zA-Z ]/g, ''); // Allow only alphabetic characters and spaces
        });

        // Handle currently-working-checkbox initial state
        const checkBox = document.getElementById(`flexCheckcurrently-working-checkbox-${uniqueId}`);
        const endDateField = document.getElementById(`txt-end-date-${uniqueId}`);
        if (checkBox.checked) {
            endDateField.disabled = true;
        }

        $('.currently-working-checkbox').on('change', function () {
            let location = $(this).data('id');
            if ($(this).is(':checked')) {
                $('#txt-end-date-'+location).prop('disabled', true).val('').parsley().reset();
            } else {
                $('#txt-end-date-'+location).prop('disabled', false);
            }
            calculateExperience(); // Recalculate experience
        });

        // Remove functionality
        document.querySelectorAll('.removeRow').forEach(button => {
            button.addEventListener('click', function () {
                const rowId = this.getAttribute('data-row-id');
                document.getElementById(`workExperience-${rowId}`).remove();

                // Reinitialize Parsley validation for remaining fields after removal
                setTimeout(() => {
                    $('#msform').parsley(); // Reinitialize Parsley on the entire form
                }, 100);
            });
        });
        
    });
  
    // // Step 3 : Function to dynamically add new education fields
    document.getElementById('rowAdderEducation').addEventListener('click', function () {
        const container = document.getElementById('newinputEducation'); // Container where new inputs will be added

        // Generate a unique ID for the new set of inputs
        const uniqueId = Date.now() + '-' + Math.floor(Math.random() * 1000);

        // HTML for new education fields
        const newEducationRow = `
            <div class="row g-md-4 g-3 mb-4" id="education-row-${uniqueId}">
                <div class="col-md-6">
                    <label for="txt-institute-name-${uniqueId}" class="form-label">INSTITUTE NAME<span class="red-mark">*</span></label>
                    <input type="text" name="institute_name[]" class="form-control  alpha-only" id="txt-institute-name-${uniqueId}" placeholder="Institute Name" required data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed.">
                </div>
                <div class="col-md-6">
                    <label for="txt-educational-level-${uniqueId}" class="form-label">EDUCATIONAL LEVEL<span class="red-mark">*</span></label>
                    <input type="text" name="educational_level[]" class="form-control  alpha-only" id="txt-educational-level-${uniqueId}" placeholder="Educational Level" required data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed.">
                </div>
                <div class="col-md-6">
                    <label for="select-country-${uniqueId}" class="form-label">COUNTRY<span class="red-mark">*</span></label>
                    <select class="form-select select2t-none" id="select-country-${uniqueId}" name="country_educational[]" data-parsley-required="true" data-parsley-errors-container="#select-country-error-${uniqueId}">
                        <option value="">Country</option>
                        @foreach($countries as $country)
                            <option value="{{$country->id}}">{{$country->name}}</option>
                        @endforeach
                    </select>
                    <div id="select-country-error-${uniqueId}"></div>
                </div>
                <div class="col-md-6">
                    <label for="select-city-${uniqueId}" class="form-label">CITY<span class="red-mark">*</span></label>
                    <input type="text" name="city_educational[]" id="select-city-${uniqueId}" class="form-control alpha-only" placeholder="City" data-parsley-required="true" data-parsley-trigger="change" data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."/>
                </div>
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm mt-2 removeRowEducation" data-row-id="${uniqueId}">Remove</button>
                </div>
            </div>
        `;

        // Append the new education row to the container
        container.insertAdjacentHTML('beforeend', newEducationRow);
        initSelect2AndValidation(); 

        // Reinitialize Parsley validation for the new fields
        $('#msform').parsley().destroy(); 
        $('#msform').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<div></div>',
            trigger: 'change'
        });

        $('.alpha-only').on('input', function () {
            this.value = this.value.replace(/[^a-zA-Z ]/g, ''); // Allow only alphabetic characters and spaces
        });

        // Remove functionality for dynamically added rows
        document.querySelectorAll('.removeRowEducation').forEach(button => {
            button.addEventListener('click', function () {
                const rowId = this.getAttribute('data-row-id');
                document.getElementById(`education-row-${rowId}`).remove();

                // Reinitialize Parsley validation after row removal
                setTimeout(() => {
                    $('#msform').parsley(); // Reinitialize Parsley on the entire form
                }, 100);
            });
        });
    });

    let mediaRecorder;
    let recordedChunks = [];
    let videoBlob = null;
    let currentAssessmentKey = null;

    async function startRecording(key) 
    {
        currentAssessmentKey = key;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });

            // Display the live camera feed in a video element
            const previewVideoElement = document.getElementById(`previewVideo-${key}`);
            if (previewVideoElement) {
                previewVideoElement.srcObject = stream;
                previewVideoElement.play(); // Start playing the live feed
            }

            mediaRecorder = new MediaRecorder(stream);
            recordedChunks = []; // Reset recorded chunks

            // Collect chunks of data
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };

            // When recording stops
            mediaRecorder.onstop = () => {
                videoBlob = new Blob(recordedChunks, { type: 'video/webm' });

                // Display the recorded video
                const recordedVideoElement = document.getElementById(`recordedVideo-${key}`);
                if (recordedVideoElement) {
                    recordedVideoElement.src = URL.createObjectURL(videoBlob);
                    recordedVideoElement.controls = true;
                    recordedVideoElement.style.display = 'block'; // Show the recorded video
                }

                // Enable submit button
                const submitButton = document.getElementById(`submitRecord-${key}`);
                if (submitButton) {
                    submitButton.disabled = false;
                }

                // Stop the preview feed
                stream.getTracks().forEach(track => track.stop());
            };

            mediaRecorder.start();

            // Update button states
            const startButton = document.getElementById(`startRecord-${key}`);
            const stopButton = document.getElementById(`stopRecord-${key}`);
            if (startButton) startButton.disabled = true;
            if (stopButton) stopButton.disabled = false;

        } catch (error) {
            console.error('Error starting recording:', error);
            toastr.error('Could not access your camera and microphone. Please allow permissions.', 'Error', {
                positionClass: 'toast-bottom-right'
            });
        }
    }

    function stopRecording(key) {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();

            // Update button states
            const startButton = document.getElementById(`startRecord-${key}`);
            const stopButton = document.getElementById(`stopRecord-${key}`);
            if (stopButton) stopButton.disabled = true;
            if (startButton) startButton.disabled = false;
        }
    }

        function handleFileSelection(event, key) {
            currentAssessmentKey = key;
            const fileInput = event.target;
            const file = fileInput.files[0];

            if (file && file.type.startsWith('video/')) {
                videoBlob = file;

                // Show video preview
                const videoElement = document.getElementById(`recordedVideo-${key}`);
                if (videoElement) {
                    videoElement.src = URL.createObjectURL(file);
                    videoElement.controls = true;
                    videoElement.style.display = 'block';
                }

                // Display file name in modal label
                const displayName = file.name || "Recorded Video.mp4";
                $("#Append_startAssessment-modal-" + key).text(displayName);

                // Enable submit button
                const submitButton = document.getElementById(`submitRecord-${key}`);
                if (submitButton) {
                    submitButton.disabled = false;
                }
            } else {
                toastr.error('Please upload a valid video file.', 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                fileInput.value = ''; // Clear invalid selection
            }
        }

    async function submitRecording(key) {
        if (!videoBlob) {
            toastr.error('No video to submit. Please record or upload a video.', 'Error', {
                positionClass: 'toast-bottom-right'
            });
            return;
        }
        const originalFileName = videoBlob.name; // e.g., "example.mp4"
        const formData = new FormData();
        if(originalFileName){      
            formData.append('video', videoBlob, `language-assessment-${key}-${originalFileName}`);
            formData.append('key', key);  
        }
        else{
            formData.append('video', videoBlob, `language-assessment-${key}.webm`);
            formData.append('key', key);
        }
    
        for (let pair of formData.entries()) {
            console.log(`${pair[0]}: ${pair[1]}`);
        }
        const resortId = $('#resort_id').val();
        if (resortId) {
            formData.append('resort_id', resortId);
        } else {
            toastr.error('Resort ID is missing!', 'Error', {
                positionClass: 'toast-bottom-right'
            });
            return;
        }
        const vacancy_id = $('#vacancy_id').val();
        if (vacancy_id) {
            formData.append('vacancy_id', vacancy_id);
        }

        try {
            const response = await fetch('{{ route('resort.applicant_tempVideoStore') }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            // console.log('Response:', response);
            if (response.ok) {
                const data = await response.json();
                // Mark assessment as completed
                const completedInput = document.getElementById(`assessment_completed_${key}`);
                if (completedInput) {
                    completedInput.value = "1";
                }

                const videoref = document.getElementById(`tempVideoReference_${key}`);
                if (videoref) {
                    videoref.value = data.video_id;
                }

                // Update UI to show video was submitted
                const submitButton = document.getElementById(`submitRecord-${key}`);
                if (submitButton) {
                    submitButton.textContent = 'Submitted';
                    submitButton.disabled = true;
                }

                // Validate overall assessment completion
                validateAssessmentCompletion();
                
                // Close the modal
                const modal = document.getElementById(`startAssessment-modal-${key}`);
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                
              
                toastr.success('Video uploaded successfully!', 'Success', {
                    positionClass: 'toast-bottom-right'
                });
            } else {
                toastr.error('Failed to upload video. Please try again.', 'Error', {
                    positionClass: 'toast-bottom-right'
                });
            }
        } catch (error) {
            toastr.error('Error uploading video. Please try again.', 'Error', {
                positionClass: 'toast-bottom-right'
            });
        }
    }

    function validateAssessmentCompletion() {
        const assessments = document.querySelectorAll('input[name^="assessment_completed_"]');
        let allCompleted = true;

        assessments.forEach(input => {
            if (input.value !== "1") {
                allCompleted = false;
            }
        });

        const nextButton = document.querySelector('button.next');
        if (nextButton) {
            nextButton.disabled = !allCompleted;
        }
    }

    // Event Listeners Setup Function
    function setupVideoAssessmentListeners() {
        // Find all assessment modals
        const assessmentModals = document.querySelectorAll('[id^="startAssessment-modal-"]');
        
        assessmentModals.forEach(modal => {
            const key = modal.id.split('-').pop();
            
            // Start Recording Button
            const startRecordBtn = document.getElementById(`startRecord-${key}`);
            if (startRecordBtn) {
                startRecordBtn.onclick = () => startRecording(key);
            }

            // Stop Recording Button
            const stopRecordBtn = document.getElementById(`stopRecord-${key}`);
            if (stopRecordBtn) {
                stopRecordBtn.onclick = () => stopRecording(key);
            }

            // File Input
            const fileInput = document.getElementById(`fileInput-${key}`);
            if (fileInput) {
                fileInput.onchange = (event) => handleFileSelection(event, key);
            }

            // Submit Button
            const submitButton = document.getElementById(`submitRecord-${key}`);
            if (submitButton) {
                submitButton.onclick = () => submitRecording(key);
            }
        });
    }

    // Cleanup on page unload
    window.onbeforeunload = function () {
        $.ajax({
            url: '{{ route('resort.applicant_tempVideoremove') }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            async: false,
            success: () => console.log('Temporary video data cleared.')
        });
    };

    $(".submitVideo").on("click",function(){
        let id = $(this).data('id');
        $(`#submitRecord-${id}`).modal('hide');
    })

    // Initialize listeners when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', setupVideoAssessmentListeners);

    //step 5: Function to dynamically add new language and it'e level
    let languageIncrement = 1;

    // Add new language input fields dynamically
    $('#rowAdderlanguage').on('click', function () {
        languageIncrement++;

        // Generate new language and level fields
        let newLanguageField = `
        <div class="row g-md-4 g-3 new-language-field" id="languageField-${languageIncrement}">
            <div class="col-md-3">
                <label for="select-language-${languageIncrement}" class="form-label">LANGUAGE<span class="red-mark">*</span></label>
                <select class="form-select select2t-none" name="preliminary_language[]" id="select-language-${languageIncrement}"
                    aria-label="Default select example" required data-parsley-trigger="change">
                    <option value="">Select Language</option>
                    @foreach($get_Languages as $get_Languagesdata)
                    <option value="{{$get_Languagesdata->name}}">{{@$get_Languagesdata->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="select-level-${languageIncrement}" class="form-label">SELECT THE LEVEL<span class="red-mark">*</span></label>
                <select class="form-select select2t-none" name="select_level[]" id="select-level-${languageIncrement}"
                    aria-label="Default select example" required data-parsley-trigger="change">
                    @foreach($levelList as $levelListdata)
                    <option value="{{$levelListdata}}">{{$levelListdata}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <button type="button" class="btn btn-danger btn-sm remove-language-btn" data-id="${languageIncrement}">Remove</button>
            </div>
        </div>`;

        // Append the new fields to the language section
        $('.newinputlanguage').append(newLanguageField);

        // Reinitialize Parsley validation for the new elements
        initSelect2AndValidation(); 
        initDatePicker();
        $('#msform').parsley().destroy(); 
        $('#msform').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<div></div>',
            trigger: 'change'
        });
    });

    // Remove language input fields
    $(document).on('click', '.remove-language-btn', function () {
        let fieldId = $(this).data('id');
        $('#languageField-' + fieldId).remove();
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


                window.Parsley.addValidator('passport_no', {
                    validateString: function(value) {
                        // Alphanumeric, 5-20 characters, supports all country formats
                        return /^[A-Za-z0-9]{5,20}$/.test(value);
                    },
                    messages: {
                        en: 'Please enter a valid passport number (5-20 alphanumeric characters).'
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
                console.log(dropZoneElement,"dropZoneElement");

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
                const form = $(e.target);
                if (form.parsley().validate()) {
                    // All validations passed
                    var formData = new FormData(e.target);
                    
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