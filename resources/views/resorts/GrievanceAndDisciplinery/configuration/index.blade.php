
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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>People Relation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6 ">
                <div class="card card-grievance">
                    <div class="head">Grievance</div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Grievance Categories </h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.IndexGrievanceCategory')}}" class="a-link">View Existing</a></div>
                            </div>
                        </div>
                        <form id="GrievanceCategoryForm">
                            @csrf
                            <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                                <div class="col-sm-6">
                                    <label for="gri_cate" class="form-label">GRIEVANCE CATEGORY <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="Category_Name" name="Category_Name"
                                        placeholder="Grievance Category" required
                                        data-parsley-required-message="Grievance Category is required"
                                        data-parsley-nohtml
                                        data-parsley-nohtml-message="HTML tags are not allowed.">
                                </div>
                                <div class="col-sm-6">
                                    <label for="subcategories" class="form-label">DESCRIPTION <span class="red-mark">*</span></label>

                                            <textarea class="form-control" id="Category_Description" name="Category_Description"
                                                placeholder="Grievance Category Description" required
                                                data-parsley-required-message="Description is required"
                                                data-parsley-nohtml
                                                data-parsley-nohtml-message="HTML tags are not allowed."></textarea>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Grievance Sub-Categories </h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.IndexGrievanceSubCategory')}}" class="a-link">View Existing</a></div>
                            </div>
                        </div>
                        <form id="GrievanceSubCategoryForm" parsley-validate>
                            @csrf
                            <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                                <div class="col-sm-6">
                                    <label for="gri_cate" class="form-label">SELECT GRIEVANCE CATEGORY <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" 
                                        name="Grievance_Cat_id" id="Grievance_Cat_id" aria-label="Default select example"
                                        data-parsley-required="true"
                                        data-parsley-error-message="Please select a grievance category"
                                        data-parsley-errors-container="#error-Grievance_Cat_id"
                                        data-parsley-trigger="change">
                                        <option value="" ></option>
                                        @if($GrievanceCategory->isNotEmpty())
                                            @foreach($GrievanceCategory as $item)
                                                <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div id="error-Grievance_Cat_id" class="text-danger small"></div>

                                </div>
                                <div class="col-sm-6">
                                    <label for="subcategories" class="form-label">SUBCATEGORIES <span class="red-mark">*</span></label>
                                    <input type="text" class="form-control" id="Sub_Category_Name" name="Sub_Category_Name"
                                                placeholder="Grievance Sub Category name" required
                                                data-parsley-required-message="Description is required"
                                                data-parsley-nohtml
                                                data-parsley-nohtml-message="HTML tags are not allowed.">
                            
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Grievance Categories and Subcategories</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.IndexGrievanceCatAndSubCategory') }}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                    <form id = "AddAppendGrievanceCatForm">
                        @csrf
                        <input type="hidden" value="1"  id="AppendGrievanceCatAndSubCatCount">
                        <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3 AppendGrievanceCatandSubCat">
                            <div class="col-sm-6">
                                <label for="gri_cate" class="form-label">dsff  GRIEVANCE CATEGORY <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none  Grievance_Cat_idMain"
                                    data-parsley-required="true"
                                    data-flag="Grievance_cat_subcat_part_one"
                                    data-parsley-error-message="Please select a grievance category"
                                    name="Grievance_Cat_id[]" id="Grievance_Cat_idMain_1" data-id="1" aria-label="Default select example">
                                    <option value="" ></option>
                                    @if($GrievanceCategory->isNotEmpty())
                                        @foreach($GrievanceCategory as $item)
                                            <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="subcategories" class="form-label">SUBCATEGORIES <span class="red-mark">*</span></label>

                                <select class="form-select select2t-none"  aria-label="Select Subcategory" 
                                        required
                                        data-parsley-required="true"
                                        data-parsley-error-message="Please select a subcategory"
                                        name="Gri_Sub_cat_id[]" id="GrievanceSubcategories_1"  data-id="1" aria-label="Default select example">
                                    <option value="" ></option>
                                </select>
                            </div>
                     
                            <div class="priorityLevel-main">
                                <div class="priorityLevel-block">
                                    <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="priority_level" class="form-label">PRIORITY LEVEL <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" name="priority_level[]" id="priority_level_1" data-id="1"     aria-label="Select Priority Level"
                                            required
                                            data-parsley-required="true"
                                            data-parsley-error-message="Please select a priority level" aria-label="Default select example">
                                                <option value="High">High </option>
                                                <option value="Medium" selected>Medium </option>
                                                <option value="Low">Low</option>
                                            </select>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm AddAppendGrievanceCat blockAdd-btn">Add More</a>
                        </div>
                        <div class="card-footer text-end mt-3">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                    </div>
                    {{-- <div class="body">
                        <div class="card-title">
                
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Grievance Delegation Rules</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.IndexGrievanceDelegationRule') }}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                        <div class="grieDeleRules-main">
                         <form id="GrieDelegationRuleForm" data-parsley-validate>
                            @csrf
                                <div class="grieDeleRules-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3 AppendGrievanceDelegationRule">
                                        <div class="col-sm-6">
                                            <label for="grievance_category" class="form-label">GRIEVANCE   CATEGORY <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none Grievance_Cat_idMain"
                                                        data-parsley-required="true"
                                                        data-parsley-error-message="Please select a grievance category"
                                                        name="Grievance_Cat_id[]" id="Grievance_Cat_idMain_1" data-id="1" aria-label="Default select example">
                                                    <option value="" ></option>
                                                    @if($GrievanceCategory->isNotEmpty())
                                                        @foreach($GrievanceCategory as $item)
                                                            <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                                        @endforeach
                                                    @endif
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="delegation_rule" class="form-label">DELEGATION RULE <span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="delegation_rule"  name="delegation_rule[]"
                                            required
                                            data-parsley-required="true"
                                            data-parsley-error-message="Please Enter a Delegation Rule" aria-label="Default select example"
                                            placeholder="Safety Officer">
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="hidden" id="GrievanceDelegationRuleCount" value="1">
                                    <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm blockAdd-btn AddGrievanceDelegationRule">Add More</a>
                                </div>
                            </div>
                            <div class="mt-3 card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> --}}
                    {{-- <div class="body card-AccomGrieHearing">
                        <div class="card-title">
                            <h3>Accompanied at Any Grievance Hearing </h3>
                        </div>
                        <form id="AccompaniedGrievanceHearingForm">
                            @csrf
                            <div class="row align-items-center g-1 mb-md-4 mb-3">
                                <div class="col-auto">
                                    <h6>Right To Be Accompanied</h6>
                                </div>
                                <input type="text" name="AccompaniedGrievanceHearingCount" id="AccompaniedGrievanceHearingCount" value="{{ isset($GrievanceRightToBeAccompanied->id) ? $GrievanceRightToBeAccompanied->id : '' }}" hidden>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch"  
                                        name="grievanceRightToBeAccompanied" 
                                        id="flexSwitchCheckDefault" 
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#checkbox-error"
                                        data-parsley-error-message="Please check this box before proceeding."
                                        {{ isset($GrievanceRightToBeAccompanied->Right_to_be_accompanied) && $GrievanceRightToBeAccompanied->Right_to_be_accompanied == "yes" ? 'checked' : '' }}
                                        >
                                    

                                    <!-- Error message container -->
                                                                            <label class="form-check-label" for="flexSwitchCheckDefault"> <span class="red-mark">*</span></label>
                                    
                                    </div>
                                    <div id="checkbox-error"></div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> --}}
                    <!-- <div class="body card-nonRetaliation">
                        <form id="NonRetaliationForm" data-parsley-validate>
                            @csrf
                            <div class="card-title">
                                <h3>Non-Retaliation</h3>
                            </div>
                            <div class="row gx-md-4 g-3 mb-md-4 mb-3">
                                <div class="col-12">
                                    <label for="timeframe_submission" class="form-label">
                                        A TIMEFRAME FOR FEEDBACK SUBMISSION
                                     <span class="red-mark">*</span></label>
                                    <input type="text" value="{{ isset($GrievanceNonRetaliation) ? $GrievanceNonRetaliation->id : '0' }}" name="NonRetaliationId" hidden>
                                    <input type="text" min="1" class="form-control" id="timeframe_submission" 
                                        name="timeframe_submission" placeholder="7 Days" 
                                        value="{{ isset($GrievanceNonRetaliation) ? (int)$GrievanceNonRetaliation->timeframe_submission : '' }}"
                                          required data-parsley-required="true" data-parsley-error-message="Please provide a timeframe for feedback submission.">
                                </div>
                                <div class="col-sm-6">
                                    <label for="Reminder_Frequency" class="form-label">
                                        SELECT REMINDER FREQUENCY
                                     <span class="red-mark">*</span></label>
                                    <input type="number" min="1" class="form-control" id="Reminder_Frequency" 
                                        name="Reminder_Frequency" placeholder="1 Day Before Deadline" 
                                        required data-parsley-required="true"
                                                         value="{{ isset($GrievanceNonRetaliation) ? $GrievanceNonRetaliation->reminder_frequency : '' }}"
                                        data-parsley-error-message="Please provide a reminder frequency.">
                                </div>
                                <div class="col-sm-6">
                                    <label for="default" class="form-label">DEFAULT <span class="red-mark">*</span></label>
                                    <input type="number" min="1" class="form-control" name="reminder_default_time" id="default" 
                                        name="default min="1"" placeholder="Enabled, Reminders Every 2 Days"
                                        required data-parsley-required="true" 
                                        value="{{ isset($GrievanceNonRetaliation) ? $GrievanceNonRetaliation->reminder_default_time : '2' }}"

                                        data-parsley-error-message="Please provide a default setting.">
                                </div>
                            </div>
                            <div class="row align-items-center g-1 mb-md-4 mb-3">
                                <div class="col-auto">
                                    <h6>Non-Retaliation Feedback</h6>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                        {{ isset($GrievanceNonRetaliation) ? ($GrievanceNonRetaliation->NonRetaliationFeedback == 'yes' ? 'checked' : '') : '' }}
                                        name="NonRetaliationFeedback" id="NonRetaliationFeedback" >
                                        <label class="form-check-label" for="NonRetaliationFeedback"> <span class="red-mark">*</span></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center g-1 mb-md-4 mb-3">
                                <div class="col-auto">
                                    <h6>Reminders for Employees Who Haven’t Completed The Feedback.</h6>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                        {{ isset($GrievanceNonRetaliation) ? ($GrievanceNonRetaliation->ReminderCompleteFeedback ==  'yes' ? 'checked' : '') : '' }}
                                        name="ReminderCompleteFeedback" id="ReminderCompleteFeedback" >
                                        <label class="form-check-label" for="ReminderCompleteFeedback"> <span class="red-mark">*</span></label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                        
                        
                        <div id="checkbox-error"></div>
                        
                    </div> -->
                    <!-- <div class="body card-appeal">
                        <div class="card-title">
                            <h3>Appeal </h3>
                        </div>
                        <form id="GrievanceAppealForm" >
                            <div class="row align-items-center  g-1 mb-md-4 mb-3">
                                <div class="col-auto">
                                    <h6>Enable Appeals</h6>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        
                                        <input class="form-check-input" type="checkbox" role="switch" name="Grievance_Enable_Appeal"  id="ReminderCompleteFeedbackGrievanceAppealType"  {{ (isset($GrievanceAppealDeadlineModel)) ?  "checked" : "" }} >
                                        <label class="form-check-label" for="flexSwitchCheckDefaultnew"> <span class="red-mark">*</span></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row gx-md-4 g-3 mb-md-4 mb-3 checkGrievanceAppealType">
                                <div class="col-xl-4 col-lg-4 col-sm-4">
                                    <label for="appealSub_deadline" class="form-label">APPEAL SUBMISSION  DEADLINE <span class="red-mark">*</span></label>
                                    <input type="number" class="form-control"
                                    value="{{ isset($GrievanceAppealDeadlineModel) ? $GrievanceAppealDeadlineModel->AppealDeadLine: '' }}"
                                    required data-parsley-required="true" data-parsley-error-message="Please  add Appeal submission dedline."
                                     name="AppealDeadLine" id="appealSub_deadline" name="appealSub_deadline" placeholder="Within 7 Days Of Resolution">

                                </div>

                                <div class="col-xl-4 col-lg-4 col-sm-4">
                                    <label for="appealSub_deadline" class="form-label">SELECT TYPE <span class="red-mark">*</span></label>
                                    <select class="form-control Appeal_type" id="Appeal_type_1" data-flag="Grievance" name="Appeal_Type" aria-label="Default select example" required  data-parsley-required-message="Please Select Type">
                                        <option value=""></option>
                                        <option value="Committee" {{ isset($GrievanceAppealDeadlineModel) && $GrievanceAppealDeadlineModel->Appeal_Type =="Committee" ? 'selected' : '' }}>Committee</option>
                                        <option value="Individual" {{ isset($GrievanceAppealDeadlineModel) && $GrievanceAppealDeadlineModel->Appeal_Type =="Individual" ? 'selected' : '' }}>Individual</option>
                                    </select>
                                    <div class="error-container"></div>

                                </div>
                                <div class="col-xl-4 col-lg-4 col-sm-4 AppendAppelTypeWiseSelection">

                   
                                    <label for="default_reviewersApproversNew" class="form-label">DEFAULT REVIEWERS/APPROVERS <span class="red-mark">*</span></label>
                                        <select class="form-control MemberId_or_CommitteeId_1" name="MemberId_or_CommitteeId" id="MemberId_or_CommitteeId_1" required data-parsley-required-message="Please select a committee.">
                                        @if(isset($GrievanceAppealDeadlineModel->Appeal_Type) && $GrievanceAppealDeadlineModel->Appeal_Type =="Individual")
                                            <option value=""></option>
                                            @if(isset($OtherMembers))
                                                @foreach ($OtherMembers as $c)
                                                    <option value="{{$c->id}}" {{ isset($GrievanceAppealDeadlineModel) &&  $GrievanceAppealDeadlineModel->MemberId_or_CommitteeId == $c->id ? 'selected' :'' }}>{{ $c->first_name }} {{ $c->last_name }}</option>
                                                @endforeach
                                            @endif
                                        @endif
                                        @if(isset($GrievanceAppealDeadlineModel->Appeal_Type) && $GrievanceAppealDeadlineModel->Appeal_Type =="Committee")
                                            @if(isset($Committee))
                                           
                                                @foreach ($Committee as $c)
                                                    <option value="{{$c->id}}" {{ isset($GrievanceAppealDeadlineModel) &&  $GrievanceAppealDeadlineModel->MemberId_or_CommitteeId == $c->id ? 'selected' :'' }}>{{ $c->CommitteeName }}</option>
                                                @endforeach
                                            @endif
                                        @endif

                                        </select>
                                        <div class="error-container"></div>

                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> -->
                    {{-- <div class="body">
                        <div class="card-title">
                            <h3>Investigation Templates</h3>
                        </div>
                          
                            <div class="investigationTemp-main">
                                <div class="investigationTemp-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="grievance_typeInvest" class="form-label">GRIEVANCE TYPE <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none Grievance_Cat_idMain"
                                                            data-parsley-required="true"
                                                            data-parsley-error-message="Please select a grievance category"
                                                            name="Grievance_Cat_id[]" id="Grievance_Invest_Cat_idMain_1" data-id="1" aria-label="Default select example">
                                                        <option value="" ></option>
                                                        @if($GrievanceCategory->isNotEmpty())
                                                            @foreach($GrievanceCategory as $item)
                                                                <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                                            @endforeach
                                                        @endif
                                                </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <a href="javascript:void(0)" class="btn btn-themeSkyblue GrievanceCreateTemplete_Templete btn-sm">Create Template</a>
                                        </div>
                                     
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table">
                                            <table class="table GrievanceTempleteIndex w-100" id="GrievanceTempleteIndex">
                                                <thead>
                                                    <tr>
                                                        <th>Category Name</th>
                                                        <th>Templete Name</th>
                                                        <th>Action</th>
                                                </th>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                 
                    </div> --}}
                    <div class="body">
                        <div class="card-title">
                            <h3>Resolution Timelines</h3>
                        </div>
                        <form id="ResolutionTimelinesForm" data-parsley-validate>
                            @csrf
                             <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                <div class="col-sm-12">
                                    <label for="high_priority" class="form-label">HIGH PRIORITY <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none" id="high_priority"  
                                    required
                                    data-parsley-required="true"
                                    data-parsley-error-message="Please select a grievance category"
                                     name ="Grivance_high_priority"    aria-label="Default select example">
                                        <option ></option>
                                        @for($i=1; $i< 11 ; $i++)
                                            <option value="{{ $i }}" {{isset($GrivanceResoultionTimeLineModel) && $GrivanceResoultionTimeLineModel->HighPriority == $i ? 'selected':''}}> {{ $i }} business days</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-sm-12">
                                    <label for="medium_priority" class="form-label">MEDIUM PRIORITY <span class="red-mark">*</span></label>
                                    <select    
                                    required
                                    data-parsley-required="true"
                                    data-parsley-error-message="Please select a grievance category"
                                    class="form-select select2t-none" id="medium_priority"    name ="Grivance_medium_priority" aria-label="Default select example">
                                        <option ></option>
                                        @for($i=1; $i< 11 ; $i++)
                                            <option value="{{ $i }}" {{isset($GrivanceResoultionTimeLineModel) && $GrivanceResoultionTimeLineModel->MediumPriority == $i ? 'selected':''}}>{{ $i }} business days</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-sm-12">
                                    <label for="low_priority" class="form-label">LOW PRIORITY  <span class="red-mark">*</span></label>
                                    <select 
                                    required
                                    data-parsley-required="true"
                                    data-parsley-error-message="Please select a grievance category"
                                    class="form-select select2t-none" id="low_priority"  name ="Grivance_low_priority"  aria-label="Default select example">
                                        <option ></option>
                                        @for($i=1; $i< 11 ; $i++)
                                            <option value="{{ $i }}" {{ isset($GrivanceResoultionTimeLineModel) && $GrivanceResoultionTimeLineModel->LowPriority == $i ? 'selected':''}}>{{ $i }} business days</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Escalation</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{route('GrievanceAndDisciplinery.config.IndexGrievanceEscaltion')}}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                        <form id="escaltionForm" data-parsley-validate>
                            @csrf
                            <div class="escalation-main">
                                <div class="escalation-block">
                                    <input type="hidden" name="grivance_escaltion_count"  value="1" id="grivance_escaltion_count">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3 AppendGrievanecEscalation">
                                        <div class="col-sm-6">
                                            <label for="grievance_type" class="form-label">GRIEVANCE TYPE <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none Grievance_Cat_idMain"
                                                                data-parsley-required="true"
                                                                required data-parsley-errors-container="#error_Grievance_Invest_Cat_idMain_1"
                                                                data-parsley-error-message="Please select a grievance category"
                                                                name="Grievance_Cat_id[]" id="Grievance_Invest_Cat_idMain_1" data-id="1" aria-label="Default select example">
                                                            <option value="" ></option>
                                                        @if($GrievanceCategory->isNotEmpty())
                                                            @foreach($GrievanceCategory as $item)
                                                                <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                                            @endforeach
                                                        @endif
                                                </select>
                                               <div id="error_Grievance_Invest_Cat_idMain_1" class="text-danger"></div>

                                        </div>
                                        <div class="col-sm-6">
                                            <label for="resolved_duration" class="form-label">RESOLVED DURATION <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" id="resolved_duration"
                                                name="resolved_duration[]"
                                                aria-label="Default select example"
                                                required data-parsley-errors-container="#resolved_duration_error_1"
                                                data-parsley-error-message="Please select a resolved duration">
                                                <option selected disabled>2 Business days</option>
                                                @for($i=1; $i< 11 ; $i++)
                                                    <option value="{{ $i }}" >{{ $i }} business days</option>
                                                @endfor
                                
                                            </select>
                                            <div id="resolved_duration_error_1" class="text-danger"></div>

                                        </div>
                                    
                                    </div>
                                    <div class="col-sm-12">
                        
                                        <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm  AddMoreEscaltion blockAdd-btn">Add
                                            More</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end mt-3">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="body">
                        <div class="card-title">
                            <h3>Confidentiality Settings</h3>
                        </div>
                        <form id="GrivanceKeyPerson" data-parsley-validate>
                            @csrf


                            <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                                <div class="col-sm-12">
                                    <label for="personnel_disclosure" class="form-label">KEY PERSONNEL FOR IDENTITY
                                        DISCLOSURE <span class="red-mark">*</span></label>
                                        <select class="form-select" id="KeyPersonnel" name="KeyPersonnel[]" multiple aria-label="Default select example">
                                        @if($KeyPerson->isNotEmpty())
                                            @foreach($KeyPerson as $k)
                                                <option value="{{ $k->Admin_id }}" {{in_array($k->id,$GrivanceKeys) ? 'selected' : '' }}>
                                                    {{ $k->first_name }} {{ $k->last_name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>

                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type='submit' class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                              <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Grievance Committees</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{route('GrievanceAndDisciplinery.config.GrivanceCommitteeIndex')}}" class="a-link">View Existing</a></div>
                            </div>
                   
                        </div>
                        <div class="grievanceCommittees-main">
                        <form id="GrievanceCommitteeForm" data-parsley-validate>
                            @csrf
                            <div class="grievanceCommittees-block">
                                
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="createSpe_committees" class="form-label">CREATE SPECIFIC
                                                COMMITTEES <span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="Grivance_CommitteeName"name="Grivance_CommitteeName"
                                                placeholder="Sexual Harassment Committee"
                                                required="" data-parsley-required-message="Please Enter Appeal Deadline">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="key_members" class="form-label">KEY MEMBERS <span class="red-mark">*</span></label>
                                            <select class="form-control GrieanceCommitteeMembers" multiple name="GrieanceCommitteeMembers[]" id="GrieanceCommitteeMembers" required data-parsley-required-message="Please select a committee.">
                                                    <option value=""></option>
                                                    @if(isset($OtherMembers))
                                                        @foreach ($OtherMembers as $c)
                                                            <option value="{{$c->id}}" {{ isset($DisciplinaryAppeal) &&  $DisciplinaryAppeal->MemberId_or_CommitteeId == $c->id ? 'selected' :'' }}>{{ $c->first_name }} {{ $c->last_name }}</option>
                                                        @endforeach
                                                    @endif
                                            </select>
                                        </div>
                                    
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 ">
                <div class="card card-disciplinary">
                    <div class="head">DISCIPLINARY</div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>DISCIPLINARY CATEGORIES</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.Disciplineryindex') }}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                        <form id="DisciplinaryCategory" data-parsley-validate>
                            @csrf
                            <div class="disciplinaryCategories-main">
                                <div class="disciplinaryCategories-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="disciplinary_cate" class="form-label"> CATEGORY NAME <span class="red-mark">*</span></label>
                                            <input type="text" name="DisciplinaryCategoryName" placeholder="Category Name" 
                                             data-parsley-nohtml
                                             data-parsley-nohtml-message="HTML tags are not allowed."
                                              required
                                             data-parsley-required-message="Please enter category description"
                                            class="form-control"></input>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="disciplinary_cate" class="form-label"> CATEGORY DESCRIPTION <span class="red-mark">*</span></label>
                                            <textarea  name="description" placeholder="Category Description" 
                                            data-parsley-nohtml
                                            data-parsley-nohtml-message="HTML tags are not allowed."
                                             required
                                                        data-parsley-required-message="Please enter category description"
                                            class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>OFFENSES </h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.Offensesindex') }}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                        <form id="OffensesForm" data-p arsley-validate>
                            @csrf
                            <div class="OFFENSES-main">
                                <div class="disciplinaryOFFENSES-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                        
                                        <div class="col-md-4">
                                            <label for="disciplinary_cate" class="form-label">DISCIPLINARY CATEGORY <span class="red-mark">*</span></label>
                                                <select class="form-select select2t-none" 
                                                    name="disciplinary_cat_id" 
                                                    id="disciplinary_cate"
                                                    aria-label="Default select example"
                                                    required
                                                    data-parsley-required="true"
                                                        data-parsley-error-message="Please select a disciplinary category"
                                                        data-parsley-errors-container="#error-disciplinary_cate_error_one"
                                                        data-parsley-trigger="change">
                                                    <option value="">Select Disciplinary Category</option>
                                                    @if($DisciplinaryCategories->isNotEmpty())
                                                    @foreach($DisciplinaryCategories as $item)
                                                        <option value="{{$item->id}}">{{$item->DisciplinaryCategoryName}}</option>
                                                    @endforeach
                                                    @endif
                                                </select>
                                            <div id="error-disciplinary_cate_error_one" class="text-danger small"></div>
                                            </div>
                                        <div class="col-sm-4">
                                            <label for="disciplinary_cate" class="form-label"> Offenses name <span class="red-mark">*</span></label>
                                            <input type="text"
                                                        name="OffensesName"
                                                        class="form-control"
                                                        placeholder="Offenses Name"
                                                        required
                                                        data-parsley-required-message="Please enter offenses name"
                                                        data-parsley-nohtml
                                                        data-parsley-nohtml-message="HTML tags are not allowed.">
                                        </div>
                                        <div class="col-sm-4">
                                            <label for="disciplinary_cate" class="form-label"> Offenses Description <span class="red-mark">*</span></label>
                                            <textarea  name="offensesdescription"  data-parsley-nohtml
                                                  
                                                    data-parsley-nohtml-message="HTML tags are not allowed." placeholder="offenses description" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>ACTION </h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.IndexAction') }}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                        <form id="ACTION" data-parsley-validate>
                            @csrf
                            <div class="ACTION-main">
                                <div class="ACTION-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="ActionName" class="form-label"> ACTION  NAME <span class="red-mark">*</span></label>
                                            <input type="text" name="ActionName"
                                                
                                                data-parsley-required="true"
                                              required
                                                data-parsley-nohtml
                                                data-parsley-required-message="Please enter action name"
                                                data-parsley-nohtml-message="HTML tags are not allowed."
                                                placeholder="Action Name" class="form-control"></input>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="description" class="form-label">  DESCRIPTION <span class="red-mark">*</span></label>
                                            <textarea  name="description"
                                                        data-parsley-nohtml
                                                        data-parsley-nohtml-message="HTML tags are not allowed."
                                                     placeholder="Action Description" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                  
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>SEVERITY </h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{ route('GrievanceAndDisciplinery.config.IndexSeverity') }}" class="a-link">View Existing</a></div>
                            </div>
                        </div>
                        <form id="SEVERITY">
                            @csrf
                            <div class="SEVERITY-main">
                                <div class="SEVERITY-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="ActionName" class="form-label">SEVERITY  NAME <span class="red-mark">*</span></label>
                                            <input type="text" name="SeverityName" 
                                            data-parsley-nohtml
                                            data-parsley-nohtml-message="HTML tags are not allowed."
                                            required
                                            data-parsley-required-message="Please enter severity name"
                                            placeholder="Severity Name" class="form-control"></input>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="description" class="form-label"> DESCRIPTION  <span class="red-mark">*</span></label>
                                            <textarea  name="description"
                                            data-parsley-nohtml
                                            data-parsley-nohtml-message="HTML tags are not allowed." placeholder="Severity Description" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>  
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Code of Conduct</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="{{route('GrievanceAndDisciplinery.config.IndexCodeOfCounduct')}}" class="a-link">View Existing</a></div>
                            </div>

                        </div>
                        <form id="CodeofConduct" data-parsley-validate>
                            @csrf
                            <div class="codeConduct-main">
                                <div class="codeConduct-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3 CodeOfConductappend">
                                        <input type="hidden" name="codeOfConductCount" id="codeOfConductCount" value="1">

                                        {{-- Disciplinary Category --}}
                                        <div class="col-sm-6">
                                            <label class="form-label">DISCIPLINARY CATEGORIES <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none Code_of_disciplinary_cate" 
                                                    id="Code_of_disciplinary_cate_1" 
                                                    name="Deciplinery_cat_id[]" 
                                                    required
                                                    data-id="1"
                                                    data-parsley-errors-container="#disciplinary_error_1"
                                                    data-parsley-required-message="Please select a disciplinary category">
                                                <option value="">Select Disciplinary Category</option>
                                                @foreach($DisciplinaryCategories as $item)
                                                    <option value="{{ base64_encode($item->id) }}">{{ $item->DisciplinaryCategoryName }}</option>
                                                @endforeach
                                            </select>
                                            <div id="disciplinary_error_1" class="text-danger"></div>
                                        </div>

                                        {{-- Offenses --}}
                                        <div class="col-sm-6">
                                            <label class="form-label">OFFENSES <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" 
                                                    id="offenses_1" 
                                                    name="Offenses_id[]" 
                                                    required
                                                    data-parsley-errors-container="#offenses_error_1"
                                                    data-parsley-required-message="Please select an offense">
                                                <option value="">Select Offense</option>
                                            </select>
                                            <div id="offenses_error_1" class="text-danger"></div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="col-sm-6">
                                            <label class="form-label">ACTIONS <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" 
                                                    id="actions_1" 
                                                    name="Action_id[]" 
                                                    required
                                                    data-parsley-errors-container="#actions_error_1"
                                                    data-parsley-required-message="Please select an action">
                                                <option value="">Select Action</option>
                                                @foreach($ActionStore as $item)
                                                    <option value="{{ base64_encode($item->id) }}">{{ $item->ActionName }}</option>
                                                @endforeach
                                            </select>
                                            <div id="actions_error_1" class="text-danger"></div>
                                        </div>

                                        {{-- Severity --}}
                                        <div class="col-sm-6">
                                            <label class="form-label">SEVERITY <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" 
                                                    id="severity_1" 
                                                    name="Severity_id[]" 
                                                    required
                                                    data-parsley-errors-container="#severity_error_1"
                                                    data-parsley-required-message="Please select a severity level">
                                                <option value="">Select Severity</option>
                                                @foreach($SeverityStore as $item)
                                                    <option value="{{ base64_encode($item->id) }}">{{ $item->SeverityName }}</option>
                                                @endforeach
                                            </select>
                                            <div id="severity_error_1" class="text-danger"></div>
                                        </div>

                                        {{-- Buttons --}}
                                        <div class="col-12">
                                            <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm CodeofConductAdd_More me-md-2 me-1">Add More</a>
                                            <a href="{{ route('GrievanceAndDisciplinery.config.CodeOfConduct')}}" class="btn btn-themeSkyblue btn-sm">Download Code Of Conduct</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            <h3>Letter Templates</h3>
                        </div>
                        <div class="mb-md-4 mb-3">
                            <div class="row">
                                
                                <div class="col-md-12">
                                    <label class="form-label"> </label>
                                    <a href="javascript:void(0)"  class="btn btn-themeSkyblue btn-sm EmailFreshModal">Create Latter Template</a>
                                
                                </div>
                                <div class="col-md-12">
                                        <div class="table">
                                            <table class="table DisciplinaryEmailTempleteIndex w-100" id="DisciplinaryEmailTempleteIndex">
                                                <thead>
                                                    <tr>
                                                        <th>Action Name</th>
                                                        <th>Action</th>
                                                </th>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        {{-- <div class="card-footer text-end">
                            <a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div> --}}
                    </div>
                    

                    <!-- <div class="body">
                        <div class="card-title">
                            <h3>Approval Roles</h3>
                        </div>
                        <form id="ApprovalRole">
                            @csrf
                            <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3">
                                <div class="col-sm-12">
                                    <label for="define_approversFor" class="form-label">DEFINE APPROVERS FOR <span class="red-mark">*</span></label>
                                @php $DisciplinaryApprovalRole = config('settings.DisciplinaryApprovalRole'); @endphp
                                    <select class="form-select select2t-none" id="Approval_role_id" name="Approval_role_id" aria-label="Default select example">
                                        <option value=""> </option>
                                        @foreach($DisciplinaryApprovalRole as $k => $r)
                                            <option value="{{ $k }}" {{ isset($ApprovalRoles) &&  $ApprovalRoles->Approval_role_id == $k ? 'selected' :'' }}>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                                <div class="card-footer text-end"><button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> -->
                    <div class="body">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Disciplinary Committees and Assigned Members</h3>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="{{route('GrievanceAndDisciplinery.config.IndexDisciplinaryCommittees')}}" class="a-link">View Existing</a></div>
                    
                                </div>
                            </div>
                          

                       
                        <form id="DisciplinaryCommittees">
                            <div class="disciplinaryComm-main">
                                <input type="hidden" name="discplineryCommitteeCount" id="discplineryCommitteeCount" value="1">
                                <div class="disciplinaryComm-block ">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3 AppendCommittesAssignMember" id="AppendCommittesAssignMember">
                                        <div class="col-sm-6">
                                            <label for="committee" class="form-label">COMMITTEE <span class="red-mark">*</span></label>
                                            <input type="text" 
                                            data-parsley-nohtml
                                            data-parsley-nohtml-message="HTML tags are not allowed."
                                            required
                                            data-parsley-required-message="Please enter committee name"
                                            class="form-control" name="CommitteeName[1][]" id="committee_1" data-id="1" placeholder="Committee"   required data-parsley-required-message="Committee name is required.">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="assign_members" class="form-label">ASSIGN KEY
                                                MEMBERS <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none"
                                                data-parsley-multiple="MemberId[1][]" 
                                                data-parsley-errors-container="#asign_error_1" 
                                                data-parsley-required-message="Please assign at least one member."
                                            multiple id="assign_members_1" name="MemberId[1][]" data-id="1" aria-label="Default select example" required data-parsley-required-message="Please assign at least one member.">
                                                <option value="">Select Members</option>
                                                @if($CommitteeMembers->isNotEmpty())
                                                    @foreach ($CommitteeMembers as $c)
                                                        <option value="{{$c->id}}">{{ $c->first_name }} {{ $c->last_name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="asign_error_1" class="text-danger"></div>

                                        </div>
                                    </div>
                                     <div class="row mt-2">
                                        <div class="col-sm-12">
                                            <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm blockAdd-btn AddAssignCommittees">Add More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end mt-3">
                                <button type="submit" class="btn btn-themeBlue btn-sm CommitteeSubmit" id="CommitteeSubmit">Submit</button>
                            </div>
                        </form>
                    </div>
                    {{-- <div class="body">
                        
                        <div class="mb-md-4 mb-3">
                            <label class="form-label">HEARING AND INVESTIGATION TEMPLATES <span class="red-mark">*</span></label>
                            <div><a href="javascript:void(0)" class="btn btn-themeBlue btn-sm HearingIvestigation" data-type="HearingPackage">Create Template</a></div>
                        </div>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div>
                    <div class="body">
                        <div class="card-title">
                            
                            <div class="col-auto ms-auto">
                                <h3>Disciplinary Delegation Rules</h3>
                            </div>
                            <div class="col-auto ms-auto">
                                <a href="{{route('GrievanceAndDisciplinery.config.IndexDisciplinaryDelegationRule')}}" class="a-link">View Existing</a>
                            </div>

                        </div>
                        <form id="DisciplinaryDeleationRule">
                            <div class="disciplinaryDelegRules-main">
                                <div class="disciplinaryDelegRules-block">
                                    <div class="row align-items-end gx-md-4 g-3 mb-md-4 mb-3 AppendDelegationRule" id="AppendDelegationRule">
                                        <div class="col-sm-6">
                                            <label for="select_category" class="form-label">Select Category <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none DelegationRuleCategory" 
                                                            id="DelegationRuleCategory_1" 
                                                            data-id="1" 
                                                            name="Del_cat_id[]" 
                                                            aria-label="Default select example"
                                                            required
                                                            data-parsley-required-message="Please select a disciplinary category">
                                                        <option value=""></option>
                                                        @if($DisciplinaryCategories->isNotEmpty())
                                                            @foreach($DisciplinaryCategories as $item)
                                                                <option value="{{base64_encode($item->id)}}">{{$item->DisciplinaryCategoryName}}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="set_rule" class="form-label">Set Rule <span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="DelegationRuleName_1"  required data-parsley-required-message="Please Enter Rule"  name="Del_Rule[]" placeholder="Set Rule">
                                        </div>
                                    
                                    </div>
                                  
                                </div>
                               
                            </div>
                            <div class="col-sm-12">
                                <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm DelegationRuleAddmore blockAdd-btn">Add More</a>
                            </div>
                            <input type="hidden" name="DelegationRuleCount" id='DelegationRuleCount' value="1">
                            <div class="card-footer text-end mt-3">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> 
                    <div class="body card-appeal">
                        <div class="card-title">
                            <h3>Appeal</h3>
                        </div>
                        <div class="row align-items-center g-1 mb-md-4 mb-3">
                            <div class="col-auto">
                                <h6>Enable Appeals</h6>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="form-check form-switch form-switchTheme switch-blue">
                                    <input class="form-check-input" type="checkbox" role="switch"   id="CheckAppealType"  @if(isset($DisciplinaryAppeal->id))  checked  @endif name="ReminderCompleteFeedback" id="ReminderCompleteFeedback" data-parsley-multiple="ReminderCompleteFeedback">
                                    <label class="form-check-label" for="flexSwitchCheckDefaultnew"> <span class="red-mark">*</span></label>
                                </div>
                            </div>
                        </div>
                        <form id="DisciplineryAppealform" data-parsley-validate>
                            @csrf
                            <div class="row gx-md-4 g-3 mb-md-4 mb-3 CheckAppealDiv" @if(!isset($DisciplinaryAppeal->id)) style="display: none;"  @endif>
                                <div class="col-xl-4 col-lg-4 col-sm-4">
                                    <input type="hidden" name="DisciplineryAppealId" value="{{ isset($DisciplinaryAppeal) && $DisciplinaryAppeal->id  ? $DisciplinaryAppeal->id  : 0 }}" >
                                    <label for="appealSub_deadline" class="form-label">APPEAL SUBMISSION
                                        DEADLINE <span class="red-mark">*</span></label>
                                        <input type="number" name="AppealDeadLine" class="form-control" id="appealSub_deadline"
                                        placeholder="7 Days" 
                                        value="{{ isset($DisciplinaryAppeal) ? (int) $DisciplinaryAppeal->AppealDeadLine : '' }}"
                                        required
                                        data-parsley-required-message="Please Enter Appeal Deadline"
                                        data-parsley-type="digits"
                                        data-parsley-min="1">
                                 
                                </div>
                                <div class="col-xl-4 col-lg-4 col-sm-4">
                                    <label for="appealSub_deadline" class="form-label">SELECT TYPE <span class="red-mark">*</span></label>
                                    <select class="form-control Appeal_type" id="Appeal_type_2" data-flag="Disciplinary" name="Appeal_Type" aria-label="Default select example" required  data-parsley-required-message="Please Select Type">
                                        <option value=""></option>
                                        <option value="Committee" {{ isset($DisciplinaryAppeal) && $DisciplinaryAppeal->Appeal_Type =="Committee" ? 'selected' : '' }}>Committee</option>
                                        <option value="Individual" {{ isset($DisciplinaryAppeal) && $DisciplinaryAppeal->Appeal_Type =="Individual" ? 'selected' : '' }}>Individual</option>
                                    </select>
                                </div>
                                <div class="col-xl-4 col-lg-4 col-sm-4 AppendDisciplineryTypeWiseSelection">
                                    <label for="default_reviewersApproversNew" class="form-label">DEFAULT REVIEWERS/APPROVERS <span class="red-mark">*</span></label>
                                        <select class="form-control MemberId_or_CommitteeId" name="MemberId_or_CommitteeId_2" id="MemberId_or_CommitteeId_2" required data-parsley-required-message="Please select a committee.">
                                        @if(isset($DisciplinaryAppeal->Appeal_Type) && $DisciplinaryAppeal->Appeal_Type =="OtherMembers")
                                            <option value=""></option>
                                            @if(isset($OtherMembers))
                                                @foreach ($OtherMembers as $c)
                                                    <option value="{{$c->id}}" {{ isset($DisciplinaryAppeal) &&  $DisciplinaryAppeal->MemberId_or_CommitteeId == $c->id ? 'selected' :'' }}>{{ $c->first_name }} {{ $c->last_name }}</option>
                                                @endforeach
                                            @endif
                                        @endif
                                        @if(isset($DisciplinaryAppeal->Appeal_Type) && $DisciplinaryAppeal->Appeal_Type =="Committee")
                                            @if(isset($Committee))
                                                @foreach ($Committee as $c)
                                                    <option value="{{$c->id}}" 
                                                        {{ isset($DisciplinaryAppeal) &&  $DisciplinaryAppeal->MemberId_or_CommitteeId == $c->id ? 'selected' :'' }}
                                                        
                                                        >{{ $c->CommitteeName }}</option>
                                                @endforeach
                                            @endif
                                        @endif

                                        </select>
                                </div>
                                
                       
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                     
                        </form>
                    </div>
                    {{-- <div class="body card-rightAccompanied">
                        <div class="card-title">
                            <h3>Right To Be Accompanied</h3>
                        </div>
                        <form id="RightToBeAccompaniedForm" data-parsley-validate>
                            <div class="row align-items-center  g-1 mb-md-4 mb-3">
           
                                    @csrf 
                                <div class="col-auto">
                                    <h6>Disciplinary hearing</h6>
                                </div>
                            
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" name="RightToBeAccompanied" 
                                        id="RightToBeAccompanied"
                                        role="switch" id="flexSwitchCheckDefaultnew" 
                                        required data-parsley-required="true"
                                        data-parsley-errors-container="#checkbox-error"
                                        data-parsley-required-message="You must confirm this before proceeding."
                                        @if(isset($RightToBeAccompanied->id)) checked @endif
                                        >
                                        <input type="hidden" name="rightTobeAccompised_id" value="{{ $RightToBeAccompanied->id ?? 0 }}" hidden>
                                        <label class="form-check-label" for="flexSwitchCheckDefaultnew"> <span class="red-mark">*</span></label>
                                    </div>
                                    <div id="checkbox-error" class="text-danger"></div>

                                </div>
                            </div>
                            <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> --}}
                </div>
            </div>

        </div>

    </div>
</div>

<div class="modal fade" id="LatterTemplete-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Letter Templete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="LatterTempleteSaveForm">
                <div class="modal-body">
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="col-sm-6">
                                <label for="set_rule" class="form-label">Latter Templete Name <span class="red-mark">*</span></label>
                                <input type="text" class="form-control" id="Latter_Temp_name" placeholder="Latter Templete Name" name="Latter_Temp_name">
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <label for="set_rule" class="form-label">Templete Format <span class="red-mark">*</span></label>
                            <div id="Latter_Templete-builder"></div>
                        </div>
                        <input type="hidden" name="templeteFlag" id='templeteFlag'>
                        <input type="hidden" name="Templete_id" id='Templete_id'>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary save-Template LatterTempleteSave">Save changes</a>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="InvestingHearing-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Hearing and Investingation Templete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="HearingTempleteSaveForm">
                <div class="modal-body">
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="col-sm-6">
                                <label for="set_rule" class="form-label"> Templete Name <span class="red-mark">*</span></label>
                                <input type="text" class="form-control" id="Hearing_Temp_name" placeholder="Hearing Templete Name" name="Latter_Temp_name">
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <label for="set_rule" class="form-label">Templete Format <span class="red-mark">*</span></label>
                            <div id="Hearing_Templete-builder"></div>
                        </div>
                        <input type="hidden" name="templeteFlag" id='templeteFlag'>
                        <input type="hidden" name="InvestingationTemplete" id='InvestingationTemplete' value='{{ isset($InvestingHearingTempleteModel) ? $InvestingHearingTempleteModel->id :'' }}'>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary save-Template InvestingHearingTempleteSave">Save changes</a>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="GrievanceHearing-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Grievance  Templete Templete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="GrievanceTempleteForm" data-parsley-validate>
                @csrf
                <div class="modal-body">
                    <div class="row mt-2">
                             <div class="col-sm-6 Grievance_Cat_idTemplete" style="display: none;">
                                <label for="set_rule" class="form-label"> Grievance Category Name <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" name="Grievance_Cat_id" id="Grievance_Cat_idTemplete" aria-label="Default select example">
                                    <option value="" ></option>
                                    @if($GrievanceCategory->isNotEmpty())
                                        @foreach($GrievanceCategory as $item)
                                            <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                        @endforeach
                                    @endif
                                </select>                            </div>
                            <div class="col-sm-6">
                                <label for="set_rule" class="form-label"> Templete Name <span class="red-mark">*</span></label>
                                <input type="text" class="form-control" id="Grievance_Temp_name" placeholder="Hearing Templete Name" name="Latter_Temp_name">
                            </div>
                        <div class="col-12 mt-3">
                            <label for="set_rule" class="form-label">Templete Format <span class="red-mark">*</span></label>
                            <div id="Grievance_Hearing_Templete-builder"></div>
                        </div>
                        <input type="hidden" name="GrievanectempleteFlag" id='GrievanectempleteFlag'>
                        <input type="hidden" name="GrievanecInvestingationTemplete_id" id='GrievanecInvestingationTemplete_id' value='{{ isset($InvestingHearingTempleteModel) ? $InvestingHearingTempleteModel->id :'' }}'>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary ">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel"> Email Template </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Email Template Name -->
                        <div class="col-md-12">
                        <label for="set_rule" class="form-label">Select Action <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" aria-label="Default select example" required name="Action_id" id="Action_id">
                                    <option value=""selected disabled>Select Action</option>
                                        @if($ActionStore->isNotEmpty())
                                            @foreach($ActionStore as $item)
                                                <option value="{{base64_encode($item->id)}}">{{$item->ActionName}}</option>
                                            @endforeach
                                        @endif
                                </select>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <!-- Subject -->
                        <div class="col-md-12">
                            <input 
                                type="text" 
                                class="form-control MailSubject" 
                                name="MailSubject"  
                                id="MailSubject" 
                                placeholder="Enter Email Subject (e.g., Welcome to Our Service)">
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <!-- Email Content -->
                        <div class="col-md-12">
                            <textarea 
                                class="form-control MailTemplete" 
                                name="MailTemplete" 
                                rows="15"  
                                id="editor" 
                                placeholder="Enter the email content here..."></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="Mode" id="Mode" value="">
                    <input type="hidden" name="TemplateId" id="TemplateId">

                    <div class="placeholders">
                        <strong>Available Placeholders:</strong>
                        <ul>
                            <li><code class="insert-placeholder" data-placeholder="@{{Case_ID}}">@{{Case_ID}}</code> - The Case Id</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Candidate_name}}">@{{Candidate_name}}</code> - The Department Name</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Department_title}}">@{{Department_title}}</code> - The Department Name</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{position_title}}">@{{position_title}}</code> - The Position Name</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{resort_name}}">@{{resort_name}}</code> - The name of the resort</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Category_name}}">@{{Category_name}}</code> - Category Name</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Offense }}">@{{Offense}}</code> - Offense Name</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Priority_Level}}">@{{Priority_Level}}</code> - Priority Level</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Date_Submitted}}">@{{Date_Submitted}}</code> - Date Submitted</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{Case_Description}}">@{{Case_Description}}</code> - Case Description</li>
                        </ul>
                    </div>
                </div>

                <div class="modal-footer">
                    <!-- <a href="#" class="btn btn-themeBlue">Preview</a> -->
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button class="btn btn-themeBlue">Submit</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>

<script type="text/javascript">

    window.Parsley.addValidator('nohtml', {
        requirementType: 'string',
        validateString: function(value) {
            // Basic regex to detect HTML tags
            return !/<[^>]*>/g.test(value);
        },
        messages: {
            en: 'HTML tags are not allowed.'
        }
    });
    $(document).ready(function () 
    {
        // Initialize parsley for form validation
                $('#GrievanceCategoryForm').parsley();
                    $('#GrievanceSubCategoryForm').parsley();
                    $('#DisciplinaryCategory').parsley();
                    $('#OffensesForm').parsley();
                    $('#ACTION').parsley();
                    $("#SEVERITY").parsley();
                    $("#CodeofConduct").parsley();


                
        $(".EmailFreshModal").on("click", function() {
                // Set modal title for add
                $(".modal-title").text("Add Email Template");

                $("#edit-modal").modal('show');
                $("#TemplateId").val("");
                $("#Mode").val("Fresh");

                // Clear form fields
                $("#TempleteName").val("");
                $("#MailSubject").val("");
                if (CKEDITOR.instances["editor"]) {
                    CKEDITOR.instances["editor"].setData(""); // Clear CKEditor content
                } else {
                    CKEDITOR.replace("editor"); // Initialize CKEditor
                }
            });


        
            // var templateData = @json($InvestingHearingTempleteModel);
            // const BASE_URL = "{{ asset('resorts_assets/lang/en-US.lang') }}";
            // const options = {
            //         disableFields: ['autocomplete', 'button'],
            //         i18n: {
            //             locale: 'en-US',
            //             override: {},
            //             url: BASE_URL
            //         }
            //     };

            // const Latter_Templete            = $('#Latter_Templete-builder').formBuilder(options);
            // const Hearing_Templete           = $('#Hearing_Templete-builder').formBuilder(options);
            // const Grievance_Hearing_Templete = $('#Grievance_Hearing_Templete-builder').formBuilder(options);

            $('.LatterTempleteSave').click(function (e) {
                var Latter_Temp_name = $('#Latter_Temp_name').val();
                if($.trim(Latter_Temp_name) === "") 
                {
                    toastr.error('Please enter Letter Template Name.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return false;
                }
                var templetemode = $("#templeteFlag").val();
                var Templete_id = $("#Templete_id").val() || 0;

                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.LatterTemletestore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            Latter_Temp_name: Latter_Temp_name,
                            Latter_Structure: Latter_Templete.actions.getData('json'),
                            Templete_id: Templete_id
                        },
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#LatterTemplete-modal").modal('hide');
                            $("#Templete_id").val(0);
                        
                            Latter_Templete.actions.setData([]);
                            Latter_Templete.actions.clearFields();

                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
           
            });
           
            $('#CodeofConduct').parsley();
            $('#DisciplinaryCommittees').parsley();
            $('#DisciplinaryDeleationRule').parsley();
            $('#DisciplineryAppealform').parsley();
            $('#AddAppendGrievanceCatForm').parsley();
            $('#GrieDelegationRuleForm').parsley();
            $('#GrievanceCommitteeForm').parsley();
            $('#NonRetaliationForm').parsley();
            $("#ResolutionTimelinesForm").parsley();
            $("#escaltionForm").parsley();
            $("#GrivanceKeyPerson").parsley();
            DisciplinaryEmailTempleteIndex();
            $(document).on('click', '.letterTemplates-edit', function (e)
            {
                $("#Latter_Temp_name").val(' ');
                $("#templeteFlag").val($(this).data('flag'));

                e.preventDefault(); 
                var id=$(this).data('id');
                $("#LatterTemplete-modal").modal('show');
                $.ajax({
                    url: "{{ route('GrievanceAndDisciplinery.config.LatterTempleteEdit', ['id' => '__ID__']) }}".replace('__ID__', id),
                    type: "get",
                    data: {
                        id: id,
                    },
                    success: function (response) {
                        var d = response.data;
                        $("#Latter_Temp_name").val(d[1]);
                        $("#Templete_id").val(d[2]);
                        let existingFormStructure = d[0];
                    
                        Latter_Templete.actions.setData(existingFormStructure);
            
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    error: function(response)
                    {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error)
                        {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
        
            });
            $(document).on('click','.letterTemplates-add',function (e) 
            {
                $("#LatterTemplete-modal").modal('show');
                $("#templeteFlag").val($(this).data('flag'));
                Latter_Templete.actions.clearFields();
                $("#Latter_Temp_name").val(' ');


            });
            $(document).on('click','#DisciplinaryEmailTempleteIndex .delete-row-btn',function (e) 
            {
            
                var id = $(this).data('self_id');
                $.ajax({
                    url: "{{ route('GrievanceAndDisciplinery.config.LatterTempleteDestory', ['id' => '__ID__']) }}".replace('__ID__', id),
                    type: "delete",
                    data: {
                        id: id,
                    },
                    success: function (response) 
                    {
                        $('.table-IndexLatterTemplete').DataTable().ajax.reload();                        
                            toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    error: function(response)
                    {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error)
                        {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
            $(document).on('click','.HearingIvestigation',function (e) 
            {
                $("#InvestingHearing-modal").modal('show');
                    if (templateData.Hearing_Temp_Structure) {
                        Hearing_Templete.actions.setData(templateData.Hearing_Temp_Structure); // Use the correct data field
                        $("#Hearing_Temp_name").val(templateData.Hearing_Temp_name);
                    }
                });
            
                //  developer functionality  
                $("#disciplinary_cate").select2({
                    placeholder: "Select Category",
                    allowClear: true,
                    width: '100%'        
                });
            $("#KeyPersonnel").select2({
                placeholder: "Select Key Personnel",
                allowClear: true,
                width: '100%'        
            });
            
            $("#Code_of_disciplinary_cate_1").select2({
                placeholder: "Select Category",
                allowClear: true,
                width: '100%'        
            });
            $("#offenses_1").select2({
                placeholder: "Select Offens",
                allowClear: true,
                width: '100%'        
            });
            $("#actions_1").select2({
                placeholder: "Select Action",
                allowClear: true,
                width: '100%'        
            });
            $("#actions").select2({
                placeholder: "Select Action",
                allowClear: true,
                width: '100%'        
            });
            
            $("#severity_1").select2({
                placeholder: "Select Severity",
                allowClear: true,
                width: '100%'        
            });
            $("#Approval_role_id").select2({
                placeholder: "Select Approval Role",
                allowClear: true,
                width: '100%'        
            });
            
            $("#assign_members_1").select2({
                placeholder: "Select Assign Members",
                allowClear: true,
                width: '100%'        
            });
            $("#DelegationRuleCategory_1").select2({
                placeholder: "Select category",
                allowClear: true,
                width: '100%'        
            });
            $("#Appeal_type_1").select2({
                placeholder: "Select Appeal Type",
                allowClear: true,
                width: '100%'        
            });   
            $("#Appeal_type_2").select2({
                placeholder: "Select Appeal Type",
                allowClear: true,
                width: '100%'        
            });   
            $(".Grievance_Cat_idMain_main").select2({
                placeholder: "Select Grievance Category",
                allowClear: true,
                width: '100%'        
            });   
            $("#MemberId_or_CommitteeId_1").select2({
                placeholder: "Select Member or Committee",
                allowClear: true,
                width: '100%'        
            });   
            $("#Grievance_Cat_idTemplete").select2({
                placeholder: "Select Grievance Category Name",
                allowClear: true,
                width: '100%'        
            });
            $("#high_priority").select2({
                placeholder: "Select Heigh Priority",
                allowClear: true,
                width: '100%'        
            });
            $("#medium_priority").select2({
                placeholder: "Select Medium Priority",
                allowClear: true,
                width: '100%'        
            });
            $("#low_priority").select2({
                placeholder: "Select Low Priority",
                allowClear: true,
                width: '100%'        
            });
            $("#GrieanceCommitteeMembers").select2({
                placeholder: "Select Committee Members",
                allowClear: true,
                width: '100%'        
            });
            
            $('#DisciplinaryCategory').validate({
                rules: {
                    DisciplinaryCategoryName:
                    {
                        required: true,
                    },
                    description:
                    {
                        required: true,
                    },
                    
                },
                messages: {
                    DisciplinaryCategoryName:
                    {
                        required: "Please Enter Disciplinary Category Name.",
                    },
                    description:
                    {
                        required: "Please Enter Description.",
                    },
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.Disciplinerystore') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
            $('#OffensesForm').validate({
                rules: {
                    OffensesName:
                    {
                        required: true,
                    },
                    offensesdescription:
                    {
                        required: true,
                    },
                    
                },
                messages: {
                    OffensesName:
                    {
                        required: "Please Enter Offenses Name.",
                    },
                    offensesdescription:
                    {
                        required: "Please Enter Description.",
                    },
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.StoreOffenses') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
            $('#ACTION').validate({
                rules: {
                    ActionName:
                    {
                        required: true,
                    },
                    description:
                    {
                        required: true,
                    },
                    
                },
                messages: {
                    ActionName:
                    {
                        required: "Please Enter Action Name.",
                    },
                    description:
                    {
                        required: "Please Enter Description.",
                    },
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.ActionStore') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });    
            $('#SEVERITY').validate({
                rules: {
                    SeverityName:
                    {
                        required: true,
                    },
                    description:
                    {
                        required: true,
                    },
                    
                },
                messages: {
                    SeverityName:
                    {
                        required: "Please Enter severity Name.",
                    },
                    description:
                    {
                        required: "Please Enter Description.",
                    },
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.SeverityStore') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
            $('#GrivanceKeyPerson').validate({
                rules: {
                    "KeyPersonnel[]":
                    {
                        required: true,
                    },
                },
                messages: {
                    "KeyPersonnel[]":
                    {
                        required: "Please select Key Personnel   .",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.KeyPersonnel') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });

            
            $('#CodeofConduct').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                
                if (form.parsley().isValid()) {
                    let formData = new FormData(this);
                    
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.CodeOfCounduct') }}", 
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) {
                                // Show success message
                                toastr.success(response.message,"Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                // Optional: Reset form
                                form[0].reset();
                                $('.select2t-none').val('').trigger('change');
                            } else {
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
                }
            });     
            // $('#RightToBeAccompaniedForm').on('submit', function(e) {
            //     e.preventDefault();

            //     let form = $(this);
                
            //     if (form.parsley().isValid()) {
            //         let formData = new FormData(this);
            //         $.ajax({
            //             url: "{{ route('GrievanceAndDisciplinery.config.RightToBeAccompanied') }}", 
            //             type: 'POST',
            //             data: formData,
            //             processData: false,
            //             contentType: false,
            //             success: function(response) {
            //                 console.log(response.success);
            //                 if (response.success) {
            //                     toastr.success(response.message,"Success",
            //                     {
            //                         positionClass: 'toast-bottom-right'
            //                     });
            //                     $('.select2t-none').val('').trigger('change');
            //                 } 
            //                 else
            //                 {
            //                     toastr.error(response.message, "Error",
            //                     {
            //                         positionClass: 'toast-bottom-right'
            //                     });
            //                 }
            //             },
            //             error: function(response) 
            //             {
            //                 var errors = response.responseJSON;

            //                 if (errors.error) { 
            //                     // If it's a duplicate entry error
            //                     toastr.error(errors.error, "Error", {
            //                         positionClass: 'toast-bottom-right'
            //                     });
            //                 } else {
            //                     // If it's a validation error
            //                     var errs = '';
            //                     $.each(errors.errors, function(key, error) {
            //                         errs += error + '<br>';
            //                     });

            //                     toastr.error(errs, "Validation Error", {
            //                         positionClass: 'toast-bottom-right'
            //                     });
            //                 }
            //             }

            //         });
            //     }
            // });     
            
            $('#ApprovalRole').validate({
                rules: {
                    Approval_role_id:
                    {
                        required: true,
                    }
                },
                messages: {
                    Approval_role_id:
                    {
                        required: "Please Select Approval Role.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.DiscriplineryApprovalRole') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });   
            $('#DisciplineryAppealform').on('submit', function(e) {
                    e.preventDefault();
                    let form = $(this);
                
                if (form.parsley().isValid()) 
                {
                    let formData = new FormData(this);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.DisciplinaryAppealStore') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            if(errors !=="undefined")
                            {
                                $.each(errors.errors, function(key, error) {
                                    errs += error + '<br>';
                                });
                                toastr.error(errs, {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        
                        }
                    });
                }
            });
            
            $('#DisciplinaryDeleationRule').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                
                if (form.parsley().isValid()) {
                    let formData = new FormData(this);
                    
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.DelegationRulesStore') }}", 
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) 
                            {
                                // Show success message
                                toastr.success(response.message,"Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form[0].reset();
                            } else {
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
                }
            });
            $('#ApprovalRole').validate({
                rules: {
                    Approval_role_id:
                    {
                        required: true,
                    }
                },
                messages: {
                    Approval_role_id:
                    {
                        required: "Please Select Approval Role.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.DiscriplineryApprovalRole') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
            $('.InvestingHearingTempleteSave').click(function (e) {
                    var Hearing_Temp_name = $('#Hearing_Temp_name').val();
                    if($.trim(Hearing_Temp_name) === "") 
                    {
                        toastr.error('Please enter  Template Name.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        return false;
                    }
                    var HearingIdtemplete = $("#InvestingationTemplete").val() || 0;
                        $.ajax({
                            url: "{{ route('GrievanceAndDisciplinery.config.InvestingHearingTempleteStore') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                Hearing_Temp_name: Hearing_Temp_name,
                                Hearing_Temp_Structure: Hearing_Templete.actions.getData('json'),
                                HearingIdtemplete: HearingIdtemplete
                            },
                            success: function (response) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#InvestingHearing-modal").modal('hide');
                            },
                            error: function(response)
                            {
                                var errors = response.responseJSON;
                                var errs = '';
                                $.each(errors.errors, function(key, error)
                                {
                                    errs += error + '<br>';
                                });
                                toastr.error(errs, {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        });
                });
            $('#DisciplinaryCommittees').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                
                if (form.parsley().isValid()) {
                    let formData = new FormData(this);
                    
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.DisciplinaryCommittees') }}", 
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) {
                                // Show success message
                                toastr.success(response.message,"Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form[0].reset();
                            } else {
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
                }
            });
            LatterTemplete();

            $(document).on("change","#CheckAppealType",function(){

                if($(this).is(':checked'))
                {
    
                    $(".CheckAppealDiv").show();
                }
                else
                {
                    $(".CheckAppealDiv").hide();
                }
            });
            $(document).on("change","#ReminderCompleteFeedbackGrievanceAppealType",function(){

                if($(this).is(':checked'))
                {

                    $(".checkGrievanceAppealType").show();
                }
                else
                {
                    $(".checkGrievanceAppealType").hide();
                }
            });
            
             
            //  CheckAppealType CheckAppealDive
            $(document).on("click",".CodeofConductAdd_More",function(){
                let count  = $("#codeOfConductCount").val();
                count =  parseInt(count)+1;
                var string='';
                    string=` <div class="col-sm-6 codeOfConduct_remove_${count}">
                                <label for="disciplinary_cate" class="form-label">DISCIPLINARY CATEGORIES <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none Code_of_disciplinary_cate" 
                                        id="Code_of_disciplinary_cate_${count}" 
                                        data-id="${count}" 
                                        name="Deciplinery_cat_id[]" 
                                        aria-label="Default select example"
                                        required
                                        data-parsley-required-message="Please select a disciplinary category">
                                    <option value="">Select Disciplinary Category</option>

                                    @if($DisciplinaryCategories->isNotEmpty())
                                        @foreach($DisciplinaryCategories as $item)
                                            <option value="{{base64_encode($item->id)}}">{{$item->DisciplinaryCategoryName}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
            
                            <div class="col-sm-6 codeOfConduct_remove_${count}">
                                <label for="offenses" class="form-label">OFFENSES <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" 
                                        name="Offenses_id[]" 
                                        data-id="${count}" 
                                        id="offenses_${count}" 
                                        aria-label="Default select example"
                                        required
                                        data-parsley-required-message="Please select an offense">
                                    <option value="">Select Offense</option>
                                </select>
                            </div>
            
                            <div class="col-sm-6 codeOfConduct_remove_${count}">
                                <label for="actions" class="form-label">ACTIONS <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" 
                                        id="actions_${count}"  
                                        data-id="${count}" 
                                        name="Action_id[]" 
                                        aria-label="Default select example"
                                        required
                                        data-parsley-required-message="Please select an action">
                                    <option value="">Select Action</option>
                                    @if($ActionStore->isNotEmpty())
                                        @foreach($ActionStore as $item)
                                            <option value="{{base64_encode($item->id)}}">{{$item->ActionName}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
            
                            <div class="col-sm-4 codeOfConduct_remove_${count}">
                                <label for="severity" class="form-label">SEVERITY <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" 
                                        name="Severity_id[]" 
                                        data-id="${count}" 
                                        id="severity_${count}" 
                                        aria-label="Default select example"
                                        required
                                        data-parsley-required-message="Please select a severity level">
                                    <option value="">Select Severity</option>
                                    @if($SeverityStore->isNotEmpty())
                                        @foreach($SeverityStore as $item)
                                            <option value="{{base64_encode($item->id)}}">{{$item->SeverityName}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-sm-2 codeOfConduct_remove_${count}">
                                <label for="committee" class="form-label"> </label>
                                <button type="button" class="btn btn-sm btn-danger CodeOfCounductRemove" data-id="${count}"><i class="fa fa-trash"></i></button>
                            </div>`;
                        $(".CodeOfConductappend").append(string);
                
                        $("#Code_of_disciplinary_cate_"+count).select2({
                            placeholder: "Select Category",
                            allowClear: true,
                            width: '100%'        
                        });
                        $("#offenses_"+count).select2({
                            placeholder: "Select Offens",
                            allowClear: true,
                            width: '100%'        
                        });
                        $("#actions_"+count).select2({
                            placeholder: "Select Action",
                            allowClear: true,
                            width: '100%'        
                        });
                        $("#severity_"+count).select2({
                            placeholder: "Select Severity",
                            allowClear: true,
                            width: '100%'        
                        });
                        $("#codeOfConductCount").val(count);
            });
            $(document).on('change', '.Code_of_disciplinary_cate', function() {
                var id= $(this).val();
                var Location = $(this).data('id');
                alert("test"+Location+"=="+id);
                $.ajax({
                    url: "{{ route('GrievanceAndDisciplinery.config.GetCategoryOffenses') }}", 
                    type: "POST",
                    data: {"_token":"{{ csrf_token() }}","id":id},
                    success: function(response) {
                        console.log(response);
                        if (response.success) {
                        
                            var option = '<option></option>'; 
                            $.each(response.data, function(i, v) {
                                option += '<option value="' + v.id + '">' + v.OffensesName + '</option>';
                            });
                            $("#offenses_"+Location).html(option);
                        }
                        else
                        {
                            toastr.error(response.message, "Error",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });

            $(document).on('click','.AddAssignCommittees',function()
            {
                var assignCommitteeCount = parseInt($("#discplineryCommitteeCount").val());
                var string='';
                assignCommitteeCount = assignCommitteeCount+1;
                string=`<div class="col-sm-4 commite_id_${assignCommitteeCount}" >
                            <label for="committee" class="form-label">COMMITTEE <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" name="CommitteeName[${assignCommitteeCount}][]" id="committee_${assignCommitteeCount}" data-id="${assignCommitteeCount}" placeholder="Committee"   required data-parsley-required-message="Committee name is required.">
                        </div>
                        <div class="col-sm-4 commite_id_${assignCommitteeCount}">
                            <label for="assign_members" class="form-label">ASSIGN KEY
                                MEMBERS <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" multiple id="assign_members_${assignCommitteeCount}" name="MemberId[${assignCommitteeCount}][]" 
                            data-id="${assignCommitteeCount}"
                                                data-parsley-multiple="MemberId[${assignCommitteeCount}][]" 
                                                data-parsley-errors-container="#asign_error_${assignCommitteeCount}" 
                                                data-parsley-required-message="Please assign at least one member."
                                            multiple  id="assign_members_${assignCommitteeCount} name="MemberId[${assignCommitteeCount}][]" data-id="${assignCommitteeCount}"  data-id="1" aria-label="Default select example" required data-parsley-required-message="Please assign at least one member.">
                                                <option value="">Select Members</option>
                                                @if($CommitteeMembers->isNotEmpty())
                                                    @foreach ($CommitteeMembers as $c)
                                                        <option value="{{$c->id}}">{{ $c->first_name }} {{ $c->last_name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="asign_error_${assignCommitteeCount}" class="text-danger"></div>
                        </div>
                        <div class="col-sm-2 commite_id_${assignCommitteeCount}" >
                            <label for="committee" class="form-label"> </label>
                            <button type="button" class="btn btn-sm btn-danger remove_committe" data-id="${assignCommitteeCount}"><i class="fa fa-trash"></i></button>
                            </div>`;
                        $("#AppendCommittesAssignMember").append(string);
                        $("#assign_members_"+assignCommitteeCount).select2({
                            placeholder: "Select Assign Members",
                            allowClear: true,
                            width: '100%'        
                        });
                        $("#discplineryCommitteeCount").val(assignCommitteeCount);        
            });

            $(document).on('click','.DelegationRuleAddmore',function()
            {
                var DelegationRuleCount = parseInt($("#DelegationRuleCount").val());
                var string='';
                DelegationRuleCount = DelegationRuleCount+1;
                string=`<div class="col-sm-4 delegation_id_${DelegationRuleCount}">
                                <label for="select_category" class="form-label">Select Category <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none DelegationRuleCategory" 
                                                id="DelegationRuleCategory_${DelegationRuleCount}" 
                                                data-id="${DelegationRuleCount}" 
                                                name="Del_cat_id[]" 
                                                aria-label="Default select example"
                                                required
                                                data-parsley-required-message="Please select a disciplinary category">
                                            <option value="">Select Disciplinary Category</option>
                                            @if($DisciplinaryCategories->isNotEmpty())
                                                @foreach($DisciplinaryCategories as $item)
                                                    <option value="{{base64_encode($item->id)}}">{{$item->DisciplinaryCategoryName}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                            </div>
                            <div class="col-sm-6 delegation_id_${DelegationRuleCount}">
                                <label for="set_rule" class="form-label">Set Rule <span class="red-mark">*</span></label>
                                <input type="text" class="form-control" id="DelegationRuleName_${DelegationRuleCount}"  required
                                                data-parsley-required-message="Please Enter Rule" name="Del_Rule[]" placeholder="Set Rule">
                            </div>
                            <div class="col-sm-2 delegation_id_${DelegationRuleCount}" >
                            <label for="committee" class="form-label"> </label>
                                <button type="button" class="btn btn-sm btn-danger remove_DelegationRule" data-id="${DelegationRuleCount}"><i class="fa fa-trash"></i></button>
                            </div>`;
                        $("#AppendDelegationRule").append(string);
                        $("#DelegationRuleCategory_"+DelegationRuleCount).select2({
                            placeholder: "Select Category",
                            allowClear: true,
                            width: '100%'        
                        });
                        $("#DelegationRuleCount").val(DelegationRuleCount);        
            });

            $(document).on('click','.CodeOfCounductRemove',function()
            {
                var count= $(this).data('id');
                $(".codeOfConduct_remove_"+count).remove();
                count   = count-1; 

                $("#codeOfConductCount").val(count);

            });
            $(document).on('click','.remove_committe',function()
            {
                var committee_remove= $(this).data('id');
                $(".commite_id_"+committee_remove).remove();
                committee_remove   = committee_remove-1; 
            $("#discplineryCommitteeCount").val(committee_remove);

            });
            $(document).on('click','.remove_DelegationRule',function()
            {
                var DelegationRule_remove= $(this).data('id');
                $(".delegation_id_"+DelegationRule_remove).remove();
             
                var count = parseInt($("#DelegationRuleCount").val())-1;
                $("#DelegationRuleCount").val(count);

            });
            $(document).on('change','.Appeal_type',function()
            {
                var Appeal_type = $(this).val();
                var flag  =  $(this).data('flag');
       
                $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.DisciplineryAppealTypeWiseData') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            Appeal_type: Appeal_type,
                        },
                        success: function (response)
                        {
                            var string='';
                            var id_type="";
                            if(flag =="Disciplinary")
                            {
                                id_type="MemberId_or_CommitteeId_2";
                            }
                            else
                            {
                                id_type="MemberId_or_CommitteeId_1";
                            }
                            if(response.data.Type=="Committee")
                            {
                                var option = '<option></option>';
                                $.each(response.data.Committee,function(i,v)
                                {
                                    option += '<option value="'+v.id+'">'+v.CommitteeName+'</option>';
                                });
                                string=`<label for="default_reviewersApproversNew" class="form-label">DEFAULT REVIEWERS/APPROVERS <span class="red-mark">*</span></label>
                                        <select class="form-control MemberId_or_CommitteeId" name="MemberId_or_CommitteeId" id="${id_type}" required data-parsley-required-message="Please select a committee.">
                                        ${option}
                                        </select>`;
                            }
                            else
                            {
                                var option = '<option></option>';
                                $.each(response.data.OtherMembers,function(i,v){
                                    option += '<option value="'+v.id+'">'+v.first_name+' '+v.last_name+'</option>';
                                });
                                string=`<label for="default_reviewersApproversNew" class="form-label">DEFAULT REVIEWERS/APPROVERS <span class="red-mark">*</span></label>
                                        <select class="form-control MemberId_or_CommitteeId" name="MemberId_or_CommitteeId" id="${id_type}" required data-parsley-required-message="Please select a committee.">
                                        ${option}
                                        </select>`;
                            }
          
                                if(flag =="Disciplinary")
                                {
                                    $(".AppendDisciplineryTypeWiseSelection").html(string);
                                    $("#MemberId_or_CommitteeId_2").select2({
                                        placeholder: `Select ${Appeal_type} `,
                                        allowClear: true,
                                        width: '100%'        
                                    });
                                }
                                else
                                {
                                    $(".AppendAppelTypeWiseSelection").html(string);
                                    $("#MemberId_or_CommitteeId_1").select2({
                                        placeholder: `Select ${Appeal_type} `,
                                        allowClear: true,
                                        width: '100%'        
                                    });
                                }
                          
                           
                        },
                        error: function(response)
                        {
                            // var errors = response.responseJSON;
                            // var errs = '';
                            // $.each(errors.errors, function(key, error)
                            //     errs += error + '<br>';
                            // });
                            // toastr.error(errs, {
                            //     positionClass: 'toast-bottom-right'
                            // });
                        }
                    });
                
            });
       

           //End of Disciplinery Part  Developer Functionality
            $("#Grievance_Cat_id").select2({
                placeholder: "Select Greivance Category",
                allowClear: true,
                width: '100%'        
            });
            $(".Grievance_Cat_idMain").select2({
                placeholder: "Select Greivance Category",
                allowClear: true,
                width: '100%'        
            });
            $("#GrievanceSubcategories_1").select2({
                placeholder: `Select Grieveance Sub Category `,
                allowClear: true,
                width: '100%'        
            });
            $("#priority_level_1").select2({
                placeholder: `Select Priority Level `,
                allowClear: true,
                width: '100%'        
            });

            $('#editForm').validate({
                rules: {
                    TempleteName: {
                        required: true,
                    },
                    Action_id: {
                        required: true,
                    },
                    
                    MailSubject: {
                        required: true,
                    },
                    MailTemplete: {
                        required: true,
                    }
                },
                messages: {
                    TempleteName: {
                        required: "Please enter the email template name.",
                    },
                    Action_id: {
                        required: "Please Select Action .",
                    },

                    
                    MailSubject: {
                        required: "Please enter the email subject.",
                    },
                    MailTemplete: {
                        required: "Please provide the email content.",
                    }
                },
                submitHandler: function(form) {
                    const content = CKEDITOR.instances['editor'].getData(); // Get content from CKEditor
                    let Action_id = $("#Action_id").val();

                    let TempleteName = $("#TempleteName").val();
                    let MailSubject = $("#MailSubject").val();
                    let id = $("#TemplateId").val();
                    let Mode = $("#Mode").val();
                    let TemplateId = $("#TemplateId").val();
                    
                    $.ajax({
                        url:  "{{ route('GrievanceAndDisciplinery.Disciplinary.DisciplineryEmailTamplate')}}",
                        type: "post",
                        data: {
                            "id": id,
                            "Action_id":Action_id,
                            "TempleteName": TempleteName,
                            "subject": MailSubject,
                            "content": content,
                            "TemplateId":TemplateId,
                            "Mode":Mode,
                            
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $("#edit-modal").modal('hide');
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#template_id_" + id).text(TempleteName);
                                DisciplinaryEmailTempleteIndex();                        

                            }
                        },
                        error: function(xhr, status, error) {
                            let errs = '';
                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }
                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }
                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $("#editForm")[0].reset();
                            if (CKEDITOR.instances['editor']) {
                                CKEDITOR.instances['editor'].setData('');
                            }
                        }
                    });
                }
            });
            
            $(document).on('change','.Grievance_Cat_idMain',function()
            {
                var Cat_id = $(this).val();
                var flag = $(this).data('flag');
                var location = $(this).data('id');
                GetGrievancesubcat(Cat_id,location,flag);

            });
            $('#GrievanceCategoryForm').validate({
                rules: {
                  
                    Category_Name:
                    {
                        required: true,
                    },
                    Category_Description:
                    {
                        required: true,
                    },
                },
                messages: {
                    Category_Name:
                    {
                        required: "Please Enter Category Name.",
                    },
                    Category_Description:
                    {
                        required: "Please Enter Description.",
                    },
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCategoryStore') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();

                                window.location.reload();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
            $('#GrievanceSubCategoryForm').validate({
                rules: {
                  
                    Grievance_Cat_id:
                    {
                        required: true,
                    },
                    Sub_Category_Name:
                    {
                        required: true,
                    },
                },
                messages: {
                    Category_Name:
                    {
                        required: "Please Select Category Name.",
                    },
                    Sub_Category_Name:
                    {
                        required: "Please Enter Description.",
                    },
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceSubCategoryStore') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form.reset();
                                window.location.reload();
                            }
                            else
                            {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
            $('#GrievanceAppealForm').validate({
                rules: {
                    AppealDeadLine: { required: true },
                    Appeal_Type: { required: true },
                    MemberId_or_CommitteeId: { required: true }
                },
                messages: {
                    AppealDeadLine: { required: "Please enter appeal deadline." },
                    Appeal_Type: { required: "Please select appeal type." },
                    MemberId_or_CommitteeId: { required: "Please select committee member." }
                },
                errorPlacement: function(error, element)
                {
                    if (element.hasClass('select2-hidden-accessible')) 
                    {
                        error.insertAfter(element.next('.select2-container'));
                    }
                    else
                    {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceAppealStore') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", { positionClass: 'toast-bottom-right' });
                            } else {
                                toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422 || xhr.status === 400) {
                                var response = xhr.responseJSON;
                                var errorMessages = '';
                                if (response.errors && Array.isArray(response.errors)) {
                                    response.errors.forEach(function(errorObj) {
                                        errorMessages += `Row ${errorObj.row}: ${errorObj.error} <br>`;
                                    });
                                } else {
                                    errorMessages = response.msg || "An unexpected error occurred.";
                                }
                                toastr.error(errorMessages, "Import Error", { positionClass: 'toast-bottom-right' });
                            } else {
                                toastr.error("An unexpected error occurred. Please try again.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });

            $("<style>")
                .prop("type", "text/css")
                .html(`
                    .error {
                        display: block !important;
                        margin-top: 5px !important;
                        color: #dc3545 !important;
                    }
                    label.error {
                        margin-top: 5px !important;
                        position: relative !important;
                        top: auto !important;
                    }
                    .select2-container + .error {
                        margin-top: 5px !important;
                    }
                `)
                .appendTo("head");
            $(document).on('click','.AddAppendGrievanceCat',function()
            {
                var Grievance_CatCount = $("#AppendGrievanceCatAndSubCatCount").val();
                Grievance_CatCount = parseInt(Grievance_CatCount)+1;
                var row=`    <div class="row g-md-4 g-3 mb-md-4 mb-3 CateandSubCat_${Grievance_CatCount}">
                            <!-- Grievance Category -->
                            <div class="col-md-6">
                                <label for="gri_cate" class="form-label fw-bold">GRIEVANCE CATEGORY <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none Grievance_Cat_idMain" 
                                        name="Grievance_Cat_id[]" 
                                        data-flag="Grievance_cat_subcat_part_one"
                                        id="Grievance_Cat_idMain_${Grievance_CatCount}" 
                                        data-id="${Grievance_CatCount}" 
                                        aria-label="Select Grievance Category"
                                        required
                                        data-parsley-required="true"
                                        data-parsley-error-message="Please select a grievance category">
                                    <option value="" selected disabled>-- Select Category --</option>
                                    @if($GrievanceCategory->isNotEmpty())
                                        @foreach($GrievanceCategory as $item)
                                            <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Subcategories -->
                            <div class="col-md-6">
                                <label for="subcategories" class="form-label fw-bold">SUBCATEGORIES <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" 
                                        name="Gri_Sub_cat_id[]" 
                                        id="GrievanceSubcategories_${Grievance_CatCount}" 
                                        data-id="${Grievance_CatCount}" 
                                        aria-label="Select Subcategory"
                                         required
                                        data-parsley-required="true"
                                        data-parsley-error-message="Please select a subcategory">
                                    <option value="" selected disabled>-- Select Subcategory --</option>
                                </select>
                            </div>

                            <!-- Priority Level -->
                            <div class="col-md-6">
                                <label for="priority_level" class="form-label fw-bold">PRIORITY LEVEL <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none"  required
                                            data-parsley-required="true"
                                            name="priority_level[]"
                                            data-parsley-error-message="Please select a priority level"
                                            id="priority_level_${Grievance_CatCount}" aria-label="Select Priority Level">
                                    <option value="High">High</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>

                            <!-- Remove Button -->
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm CatAndSubCatRemove" 
                                        data-id="${Grievance_CatCount}">
                                    <i class="fa fa-trash"></i> 
                                </button>
                            </div>
                        </div>
                        `;
                $(".AppendGrievanceCatandSubCat").append(row);
                $("#AppendGrievanceCatAndSubCatCount").val(Grievance_CatCount);
                $("#Grievance_Cat_idMain_"+Grievance_CatCount).select2({
                    placeholder: "Select Greivance Category",
                    allowClear: true,
                    width: '100%'        
                });
                $("#GrievanceSubcategories_"+Grievance_CatCount).select2({
                    placeholder: `Select Grieveance Sub Category `,
                    allowClear: true,
                    width: '100%'        
                });
                $("#priority_level_"+Grievance_CatCount).select2({
                    placeholder: `Select Priority Level `,
                    allowClear: true,
                    width: '100%'        
                });
            });      
            $(document).on('click','.CatAndSubCatRemove',function()
            {
                var loction =$(this).data('id');
                $(".CateandSubCat_"+loction).remove();
                var count = parseInt($("#AppendGrievanceCatAndSubCatCount").val())-1;
                $("#AppendGrievanceCatAndSubCatCount").val(count);
         

            });
            $('#AddAppendGrievanceCatForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                
                if (form.parsley().isValid()) {
                    let formData = new FormData(this);
                    
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryStore') }}", 
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) {
                                // Show success message
                                toastr.success(response.message,"Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                form[0].reset();
                            } else {
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
                }
            });
    
            // $('#AccompaniedGrievanceHearingForm').on('submit', function(e) {
            //     e.preventDefault();

            //     let form = $(this);
                
            //     if (form.parsley().isValid()) {
            //         let formData = new FormData(this);
                    
            //         $.ajax({
            //             url: "{{ route('GrievanceAndDisciplinery.config.GrievanceRightToBeAccompanied') }}", 
            //             type: 'POST',
            //             data: formData,
            //             processData: false,
            //             contentType: false,
            //             success: function(response) {
            //                 console.log(response.success);
            //                 if (response.success) {
            //                     // Show success message
            //                     toastr.success(response.message,"Success",
            //                     {
            //                         positionClass: 'toast-bottom-right'
            //                     });
                
            //                 } else {
            //                         toastr.error(response.message, "Error",
            //                         {
            //                             positionClass: 'toast-bottom-right'
            //                         });
            //                 }
            //             },
            //             error: function(response) 
            //             {
            //                 var errors = response.responseJSON;

            //                 if (errors.error) { 
            //                     // If it's a duplicate entry error
            //                     toastr.error(errors.error, "Error", {
            //                         positionClass: 'toast-bottom-right'
            //                     });
            //                 } else {
            //                     // If it's a validation error
            //                     var errs = '';
            //                     $.each(errors.errors, function(key, error) {
            //                         errs += error + '<br>';
            //                     });

            //                     toastr.error(errs, "Validation Error", {
            //                         positionClass: 'toast-bottom-right'
            //                     });
            //                 }
            //             }

            //         });
            //     }
            // });
            
                $('#NonRetaliationForm').on('submit', function(e) {
                    e.preventDefault();

                    let form = $(this);
                    
                    if (form.parsley().isValid()) 
                    {
                        let formData = new FormData(this);
                        $.ajax({
                            url: "{{ route('GrievanceAndDisciplinery.config.GrievanceNonRetaliation') }}", 
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log(response.success);
                                if (response.success) {
                                    // Show success message
                                    toastr.success(response.message,"Success",
                                    {
                                        positionClass: 'toast-bottom-right'
                                    });
                                } else {
                                        toastr.error(response.message, "Error",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                }
                            },
                            error: function(response) 
                            {
                                var errors = response.responseJSON;
                                if (errors.error) 
                                { 
                                    toastr.error(errors.error, "Error", 
                                    {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                                else 
                                {
                                    var errs = '';
                                    $.each(errors.errors, function(key, error) 
                                    {
                                        errs += error + '<br>';
                                    });


                                    toastr.error(errs, "Validation Error",{
                                    positionClass: 'toast-bottom-right'
                                    });
                                }
                            }

                        });
                    }
                });
            
                $(document).on('click','.AddGrievanceDelegationRule',function()
                {
                    var count = parseInt($("#GrievanceDelegationRuleCount").val())+1;
                    var row=`<div class="col-sm-6 GrievanceDelegationRul_${count}">
                                    <label for="grievance_category" class="form-label">GRIEVANCE   CATEGORY <span class="red-mark">*</span></label>
                                    <select class="form-select select2t-none Grievance_Cat_idMain"
                                                data-parsley-required="true"
                                                data-parsley-error-message="Please select a grievance category"
                                                name="Grievance_Cat_id[]" id="Grievance_Cat_idMain_${count}" data-id="${count}" aria-label="Default select example">
                                            <option value="" ></option>
                                            @if($GrievanceCategory->isNotEmpty())
                                                @foreach($GrievanceCategory as $item)
                                                    <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                                @endforeach
                                            @endif
                                    </select>
                            </div>
                            <div class="col-sm-4 GrievanceDelegationRul_${count}">
                                <label for="delegation_rule" class="form-label">DELEGATION RULE <span class="red-mark">*</span></label>
                                <input type="text" class="form-control" id="delegation_rule"  name="delegation_rule[]" placeholder="Safety Officer" required
                                                data-parsley-required="true"
                                                data-parsley-error-message="Please Enter a Delegation Rule" aria-label="Default select example">
                            </div>
                            <div class="col-sm-2 GrievanceDelegationRul_${count}">
                                    <label for="committee" class="form-label"> </label>
                                    <button type="button" class="btn btn-sm btn-danger GrievanceDelegationRuleRemove" data-id="${count}" fdprocessedid="ehjvsv"><i class="fa fa-trash"></i></button>
                            </div>`;
                    
                    $(".AppendGrievanceDelegationRule").append(row);
                    $("#GrievanceDelegationRuleCount").val(count);
                    $("#Grievance_Cat_idMain_"+count).select2({
                        placeholder: "Select Greivance Category",
                        allowClear: true,
                        width: '100%'        
                    });
                });
                $(document).on("click",".GrievanceDelegationRuleRemove",function(){
                    var location = $(this).data('id');
                    $(".GrievanceDelegationRul_"+location).remove();
                    var count = parseInt($("#GrievanceDelegationRuleCount").val())-1;
                    $("#GrievanceDelegationRuleCount").val(count);
                });

                $('#GrieDelegationRuleForm').on('submit', function(e) {
                    e.preventDefault();
                    let form = $(this);
                    if (form.parsley().isValid()) 
                    {
                        let formData = new FormData(this);
                        $.ajax({
                            url: "{{route('GrievanceAndDisciplinery.config.GrieDelegationRuleStore') }}", 
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log(response.success);
                                if (response.success) {
                                    // Show success message
                                    toastr.success(response.message,"Success",
                                    {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    form[0].reset();
                                } else {
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
                    }
                });
               
                
              
            

                
                $(document).on('click','.GrievanceCreateTemplete_Templete',function()
                {

   
                    var Grievance_Invest_Cat_idMain_1 = $('#Grievance_Invest_Cat_idMain_1').val();
                    if($.trim(Grievance_Invest_Cat_idMain_1) === "") 
                    {
                        toastr.error('Please Select Grievance Category Name.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        return false;
                    }
                    $("#GrievanceHearing-modal").modal('show');
                    Grievance_Hearing_Templete.actions.clearFields();


                });
                $('#GrievanceTempleteForm').on('submit', function(e) {
                        e.preventDefault();

                    
                        var Grievance_Invest_Cat_idMain_1 = $('#Grievance_Invest_Cat_idMain_1').val();
                        var Grievance_Temp_name = $('#Grievance_Temp_name').val();
                        var GrievanectempleteFlag = $("#GrievanectempleteFlag").val();
                        if(GrievanectempleteFlag  !="EditMode")
                        {
                            if($.trim(Grievance_Invest_Cat_idMain_1) === "") 
                            {
                                toastr.error('Please Select Grievance Category Name.', "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                                return false;
                            } 
                        }
                        var GrievanecInvestingationTemplete_id = $("#GrievanecInvestingationTemplete_id").val();
                        $.ajax({
                            url: "{{ route('GrievanceAndDisciplinery.config.GrievanceTempleteStore') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                Grievance_Cat_id: Grievance_Invest_Cat_idMain_1,
                                Grievance_Temp_name:Grievance_Temp_name,
                                Grievance_Temp_Structure: Grievance_Hearing_Templete.actions.getData('json'),
                                GrievanectempleteFlag:GrievanectempleteFlag,
                                GrievanecInvestingationTemplete_id:GrievanecInvestingationTemplete_id,
                            },
                            success: function (response) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#GrievanceHearing-modal").modal('hide');
                            },
                            error: function(response)
                            {
                                var errors = response.responseJSON;
                                var errs = '';
                                $.each(errors.errors, function(key, error)
                                {
                                    errs += error + '<br>';
                                });
                                toastr.error(errs, {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        });
                });
                

                $('#ResolutionTimelinesForm').on('submit', function(e) {
                    e.preventDefault();

                        let form = $(this);

                        if (form.parsley().isValid()) {
                            let formData = new FormData(this);
                            
                            $.ajax({
                                url: "{{ route('GrievanceAndDisciplinery.config.GrivanceResoultionTimeLineStore') }}", 
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    console.log(response.success);
                                    if (response.success) {
                                        // Show success message
                                        toastr.success(response.message,"Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                        form[0].reset();
                                    } else {
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
                        }
                });
                
                $(document).on('click','.edit-grivanceTemplate',function()
                {
                    var id = $(this).data('id');
             
    
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceTempleteEdit') }}", 
                        type: 'POST',
                        data: {"_token":"{{ csrf_token() }}","id":id},
        
                        success: function(response) {
                           
                            if (response.success) 
                            {
                           
                           
                                $("#GrievanceHearing-modal").modal('show'); 
                                Grievance_Hearing_Templete.actions.setData(response.data[0]);
                                $("#Grievance_Temp_name").val(response.data[1]);
                                $(".Grievance_Cat_idTemplete").css('display', 'block'); // To show the element
                                $("#Grievance_Cat_idTemplete").val(response.data[2]).trigger('change');
                                $("#GrievanecInvestingationTemplete_id").val(response.data[3]);
                                $("#GrievanectempleteFlag").val("EditMode");
                                $('#Grievance_Invest_Cat_idMain_1').val(response.data[2]).trigger('change');
                                GrievanceTemplete();
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


                $(document).on('click','.delete-grivanceTemplete',function(e)
                {
                    var id = $(this).data('id');
                    e.preventDefault();
                    var $button = $(this);
                    var $row = $button.closest("tr");
                    var main_id = $(this).data('id');

                    Swal.fire({
                        title: 'Sure want to delete?',
                        text: 'This cannot be undone',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No',
                        confirmButtonColor: "#DD6B55"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "DELETE",
                                url: "{{ route('GrievanceAndDisciplinery.config.grivanceTempleteDestory', ':id') }}".replace(':id', main_id),
                                dataType: "json",
                            }).done(function(result) {
                                if (result.success) {
                                    
                                    toastr.success(result.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    GrievanceTemplete();

                                } else {
                                    toastr.error(result.message, "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            }).fail(function(jqXHR) {
                                toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                        }
                    });
                });
                 GrievanceTemplete();
                $(document).on('click','.AddMoreEscaltion',function(e)
                {
                    var count = parseInt($("#grivance_escaltion_count").val());
                    count = count+1;
                    var row=` <div class="col-sm-6 Grivance_Escaltion_r_${count}">
                                        <label for="grievance_type" class="form-label">GRIEVANCE TYPE <span class="red-mark">*</span></label>
                                        <select class="form-select select2t-none Grievance_Cat_idMain"
                                                            data-parsley-required="true"
                                                            data-parsley-error-message="Please select a grievance category"
                                                             required data-parsley-errors-container="#griveance_error_type_${count}"
                                                            name="Grievance_Cat_id[]" id="Grievance_Invest_Cat_idMain_${count}" data-id="1" aria-label="Default select example">
                                                        <option value="" ></option>
                                                @if($GrievanceCategory->isNotEmpty())
                                                    @foreach($GrievanceCategory as $item)
                                                        <option value="{{$item->id}}">{{$item->Category_Name}}</option>
                                                    @endforeach
                                                @endif
                                        </select>
                                        <div id="griveance_error_type_${count}" class="text-danger"></div>

                                    </div>
                                      <div class="col-sm-4  Grivance_Escaltion_r_${count}">
                                            <label for="resolved_duration" class="form-label">RESOLVED DURATION <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" id="resolved_duration"
                                                name="resolved_duration[]"
                                                aria-label="Default select example"
                                                required data-parsley-errors-container="#resolved_duration_error_${count}"
                                                data-parsley-error-message="Please select a resolved duration">
                                                <option selected disabled>2 Business days</option>
                                                @for($i=1; $i< 11 ; $i++)
                                                    <option value="{{ $i }}" >{{ $i }} business days</option>
                                                @endfor
                                
                                            </select>
                                            <div id="resolved_duration_error_${count}" class="text-danger"></div>

                                        </div>
                                    <div class="col-sm-2  Grivance_Escaltion_r_${count}">
                                    <label for="committee" class="form-label"> </label>
                                        <button type="button" class="btn btn-sm btn-danger remove_grivance_escaltion" data-id="${count}" fdprocessedid="po9pcm"><i class="fa fa-trash"></i></button>
                                    </div>`;

                    $(".AppendGrievanecEscalation").append(row);
                    $("#grivance_escaltion_count").val(count);
                    $("#Grievance_Invest_Cat_idMain_"+count).select2({
                        placeholder: "Select Greivance Category",
                        allowClear: true,
                        width: '100%'        
                    })
                });
                $(document).on('click','.remove_grivance_escaltion',function(e)
                {
                    var id=$(this).data('id');
                    $(".Grivance_Escaltion_r_"+id).remove();
                    var count = parseInt($("#grivance_escaltion_count").val())-1;
                    $("#grivance_escaltion_count").val(count);

                });
                $(document).on('click','.edit-row-btn',function(e)
                {
                    var id=$(this).data('cat-id');
                    $("#edit-modal").modal('show');
                         $.ajax({
                                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.GetEmailTamplate') }}", 
                                type: 'POST',
                                data: {"_token":"{{csrf_token()}}","id":id},
                                success: function(response) {
                                    if (response.success) 
                                    {
                     
                                        var data= response.data;
                                        console.log(data,data.Action_id);
                                        $("#Action_id").val(data.Action_id).trigger('change');
                                        $("#MailSubject").val(data.subject);
                                        $("#Mode").val("edit");
                                        $("#TemplateId").val(data.id);
                                       

                                        if (CKEDITOR.instances["editor"]) {
                                            CKEDITOR.instances["editor"].destroy(true);
                                        }
                                        CKEDITOR.replace("editor");

                                        CKEDITOR.instances["editor"].on('instanceReady', function () {
                                            CKEDITOR.instances["editor"].setData(data.content);
                                        });
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
                 
    
                $('#escaltionForm').on('submit', function(e) {
                    e.preventDefault();

                        let form = $(this);

                        if (form.parsley().isValid()) {
                            let formData = new FormData(this);
                            
                            $.ajax({
                                url: "{{ route('GrievanceAndDisciplinery.config.GrivanceEscaltionStore') }}", 
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    console.log(response.success);
                                    if (response.success) {
                                        // Show success message
                                        toastr.success(response.message,"Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                        form[0].reset();
                                    } else {
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
                        }
                });
            $('#GrievanceCommitteeForm').on('submit', function(e) {
                e.preventDefault();

                    let form = $(this);
                    if (form.parsley().isValid()) {
                        let formData = new FormData(this);
                        
                        $.ajax({
                            url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCommitteeStore') }}", 
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log(response.success);
                                if (response.success) {
                                    // Show success message
                                    toastr.success(response.message,"Success",
                                    {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    form[0].reset();
                                } else {
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
                    }
            });

        });

        function GetGrievancesubcat(Cat_id,location,flag)
        {
            if(flag==undefined)
            {
                return false;
            }
         $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCategoryWiseSubCategoryData') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    Grievance_Cat_idMain: Cat_id,
                },
                success: function (response)
                {
                    var string='';
                    var option = '<option></option>';
                    $.each(response.data.GrievanceSubcategory,function(i,v)
                    {
                        option += '<option value="'+v.id+'">'+v.Sub_Category_Name+'</option>';
                    });

                    if(flag =="Grievance_cat_subcat_part_one")
                    {
                        $("#GrievanceSubcategories_" + location).html(option);
                    }

                    
                },
                error: function(response)
                {
                    // var errors = response.responseJSON;
                    // var errs = '';
                    // $.each(errors.errors, function(key, error)
                    //     errs += error + '<br>';
                    // });
                    // toastr.error(errs, {
                    //     positionClass: 'toast-bottom-right'
                    // });
                }
            });
           
            
        }
        
        function DisciplinaryEmailTempleteIndex()
        {
            if ($.fn.DataTable.isDataTable('.DisciplinaryEmailTempleteIndex'))
            {
                $('.DisciplinaryEmailTempleteIndex').DataTable().destroy();
            }
            var divisionTable = $('.DisciplinaryEmailTempleteIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 120,
            processing: true,
            serverSide: true,
            order:[[2, 'asc']],
            ajax: {
                url: "{{ route('GrievanceAndDisciplinery.Disciplinery.EmailTamplateIndex') }}",
                type: 'GET',
                data: function(d) {
                    d.Year = $(".Year").val();
                    d.searchTerm = $('.search').val();
                }
            },
            columns: [
                { data: 'ActionName', name: 'ActionName',},
                { data: 'action', name: 'action' },
                {data:'created_at',visible:false,searchable:false},
            ]
            });
        }
        function GrievanceTemplete()
        {
            if ($.fn.DataTable.isDataTable('.GrievanceTempleteIndex'))
            {
                $('.GrievanceTempleteIndex').DataTable().destroy();
            }
            var divisionTable = $('.GrievanceTempleteIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 120,
            processing: true,
            serverSide: true,
            order:[[3, 'asc']],
            ajax: {
                url: "{{ route('GrievanceAndDisciplinery.config.IndexGrievanceTemplete') }}",
                type: 'GET',
                data: function(d) {
                    d.Year = $(".Year").val();
                    d.searchTerm = $('.search').val();
                }
            },
            columns: [
                { data: 'CategoryName', name: 'CategoryName',},
                { data: 'GrievaneTempleteName', name: 'GrievaneTempleteName'},
                { data: 'Action', name: 'Action' },
                {data:'created_at',visible:false,searchable:false},
            ]
            });
        }

        function LatterTemplete()
        {
            
            if ($.fn.DataTable.isDataTable('.table-performance-kpilist'))
            {
                $('.table-IndexLatterTemplete').DataTable().destroy();
            }
            var divisionTable = $('.table-IndexLatterTemplete').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            order:[[4, 'desc']],
            ajax: {
                url: "{{ route('GrievanceAndDisciplinery.config.IndexLatterTemplete') }}",
                type: 'GET',
                data: function(d) {
                    d.Year = $(".Year").val();
                    d.searchTerm = $('.search').val();
                }
            },
            columns: [
                { data: 'LatterName', name: 'LatterName',},
                { data: 'EditTemplete', name: 'EditTemplete'},
                { data: 'CreateNewTemplate', name: 'CreateNewTemplate' },
                { data: 'Action', name: 'Action' },
                {data:'created_at',visible:false,searchable:false},
            ]
            });
        }

    // End of Disciplinery Part #

    // Start of Grievance



    // End of Grievance

</script>

@endsection
