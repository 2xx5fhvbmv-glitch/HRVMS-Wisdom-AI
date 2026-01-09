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
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span> Grievance And Disciplinery</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                        <option value="1">bbb</option>
                    </select>
                </div> -->
            </div>
        </div>


        <div class="card grieInv-card">
            <div class="bg-themeGrayLight mb-md-4 mb-3">
                <div class="row g-lg-5 g-sm-4 g-3">
                    <div class="col-lg-6">
                        <div class="table-responsive  mb-2">
                            <table class="table-lableSmallLabel">
                                <tr>
                                    <th>Grievance ID:</th>
                                    <td>{{ $Grivance_Parent->Grivance_id}}</td>
                                </tr>
                                <tr>
                                    <th>Grievance Category:</th>
                                    <td>{{ $Grivance_Parent->CatName }}</td>
                                </tr><tr>
                                    <th>Grievance Subcategory:</th>
                                    <td>{{ $Grivance_Parent->SubCatName }}</td>
                                </tr>
                                <tr>
                                    <th>Confidentiality Status:</th>
                                    <td>
                                        @if($Grivance_Parent->Grivance_Submission_Type =="Yes")
                                        <span class="text-themeGreen text-nowrap">
                                            <img src="assets/images/check-circle-themeGreen.svg" alt="">Confidentiality
                                        </span>
                                        @elseif($Grivance_Parent->Grivance_Submission_Type =="No")
                                            <span class="text-themeprimary text-nowrap" style="color: red;">
                                                 Not a Confidentiality
                                            </span>
                                        @else
                                            <span class="text-themeprimary text-nowrap" style="color: rgb(157, 47, 82);">
                                                Not A Applicable
                                            </span>
                                        @endif
                                            
                                    </td>
                                </tr>
                              
                                <tr>
                                    <th>Priority Level:</th>
                                    <td>
                                        @if($Grivance_Parent->Priority =="High")
                                            <span class="text-danger fw-600">High</span>
                                        @elseif($Grivance_Parent->Priority =="Medium")
                                            <span class="text-success fw-600">Medium</span>
                                        @else
                                            <span class="text-primary fw-600">Low</span>
                                        @endif
                                    
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <h6>Description:</h6>
                        <p>
                            {!!  $Grivance_Parent->Grivance_description !!}
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="mb-md-3 mb-2">Employee Details: @if($Grivance_Parent->Grivance_Submission_Type =="Yes")
                                        <span class="text-themeGreen text-nowrap">
                                            <img src="assets/images/check-circle-themeGreen.svg" alt="">Confidentiality
                                        </span>
                                        @elseif($Grivance_Parent->Grivance_Submission_Type =="No")
                                            <span class="text-themeprimary text-nowrap" style="color: red;">
                                                 Not a Confidentiality
                                            </span>
                                        @else
                                            <span class="text-themeprimary text-nowrap" style="color: rgb(157, 47, 82);">
                                                Not A Applicable
                                            </span>
                                        @endif</h6>
                                  @if(in_array($auth_id, $GrivanceKeys) || 
                                        (!in_array($auth_id, $GrivanceKeys) && $Grivance_Parent->Grivance_Submission_Type != "Yes" && $Grivance_Parent->Grivance_Submission_Type != "No"))                            <div class="d-flex align-items-center mb-md-2 mb-1">
                                <div class="img-circle me-2">

                                    <img src="{{  Common::getResortUserPicture( $Grivance_Parent->Parentid)}}" alt="image">
                                </div>
                                <h6 class="mb-0">{{ $Grivance_Parent->first_name }} {{ $Grivance_Parent->last_name }}</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableSmallLabel">
                                    <tr>
                                        <th>Department:</th>
                                        <td>{{ $Grivance_Parent->DepartmentName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Position:</th>
                                        <td>{{ $Grivance_Parent->PositiontName }}</td>
                                    </tr>
                                    <tr>
                                        <th>Contact Details:</th>
                                        <td>{{ $Grivance_Parent->personal_phone }}</td>
                                    </tr>
                                    <tr>
                                        <th>Attachements:</th>
                                        <td>
                                            @if(isset($Grivance_Parent->Attachements) && !empty($Grivance_Parent->Attachements))
                                                @foreach(explode(",",$Grivance_Parent->Attachements) as  $g)
                                                    
                                                @php 
                                                    $Path = $path."/".$Grivance_Parent->Grivance_id."/".$g;
                                                @endphp
                                                <a target="_blank" href="{{URL::asset($Path)}}" >{{$g}}</a></br>
                                                @endforeach
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
                @if(!empty($GrivanceSubmissionHistory) &&  $GrivanceSubmissionHistory->isNotEmpty())
                    <hr>
                    <div class="row">
                        <h3>History</h3>
                        <hr>
                        <div class="col-md-8">
                            <table class="table  ">
                                <thead>
                                    <tr>
                                        <th>FOLLOW-UP Action</th>
                                        <th>Follow - up Description</th>
                                        <th>Investigation Stage</th>
                                        <th>Grievance Explination Description</th>
                                        <th>Committee Member Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($GrivanceSubmissionHistory as $key => $value)
                                        <tr>
                                            <td>{{ $value->follow_up_action }}</td>
                                            <td>{!! $value->follow_up_description !!}</td>
                                            <td>{{ $value->investigation_stage }}</td>
                                            <td>{!! $value->inves_find_recommendations !!}</td>
                                            <td>{{ $value->first_name }} {{ $value->last_name }} </td>
                                            
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table  ">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Attachments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($GrivanceInvestigationModel)
                                        @foreach (explode(',', $GrivanceInvestigationModel->investigation_files) as $f)
                                            <tr>
                                                <td>{{ $f }}</td>
                                                <td><a target="_blank" href="{{ URL::asset($EveidanceFilePath.'/'. $f) }}">View</a>  </td>
                                            </tr>
                                            
                                        @endforeach
                                    @endif
                                
                                </tbody>
                            </table>
                        </div>
                      
                    </div>
                @endif
            </div>
                @if(isset($Grivance_Parent->Gm_Decision) && !empty($Grivance_Parent->Gm_Decision))
                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                        <div class="row">
                                <h3> Gm Response</h3>
                                <div class="table">
                                    <table class="table ">
                                        <thead>
                                            <tr>
                                                <th>Out-come Type</th>
                                                <th>Action Type </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{ $Grivance_Parent->Gm_Decision }}</td>
                                                <td>{!!  $Grivance_Parent->Gm_Resoan  !!}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                    </div>
                @endif
                <form id="investigationReportSubmit" data-parsley-validate>
                    @csrf
                    <div class="grieInvAssign-main">
                        <div class="grieInvAssign-block">
                            <div class="row align-items-end g-xl-4 g-3 mb-md-4 mb-3">
                                <div class="col-lg-4 col-md-5 col-sm-6">
                                    <label for="assign_to" class="form-label">ASSIGN TO</label>
                                    <select class="form-select select2t-none" id="assign_to" name="assign_to"
                                    @if($Grivance_Parent->Assigned == "Yes" || $Grivance_Parent->Assigned =='DeliverToHr') disabled @else required data-parsley-required-message="Please assign at least one committee member" @endif>
                                        <option value=""></option>
                                        @if($GrievanceCommitteeMemberParent)
                                            @foreach ($GrievanceCommitteeMemberParent as $c)
                                                <option value="{{ $c->id }}">{{ $c->Grivance_CommitteeName }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                
                    <input type="hidden" name="Grievant_form_id" value="{{ $Grivance_Parent->id}}">
                    <input type="hidden" value="{{ ($Grivance_Parent->Assigned =='No') ? 'AssignToComittee':'EditModeForCommittee' }}" name="flag">
                    @if($Grivance_Parent->Assigned !="DeliverToHr" ||  $Grivance_Parent->status !="resolved")
                        <div class="row g-xl-4 g-3 mb-md-4 mb-3">
                            <div class="col-lg-4 col-sm-6">
                                <label for="inves_date" class="form-label">INVESTIGATION START DATE</label>
                                <input type="text" value="{{ isset($GrivanceInvestigationModel->inves_start_date) ? $GrivanceInvestigationModel->inves_start_date :'' }}"
                                @if($Grivance_Parent->Assigned=="No" || isset($GrivanceInvestigationModel->inves_start_date )) readonly @else required data-parsley-required-message="Investigation start date is required" @endif class="form-control datepicker" id="inves_date" name="invesigation_date" placeholder="Choose Date">
                            </div>
                            <div class="col-lg-4 col-sm-4">
                                <label for="exp_date" class="form-label">Anticipated Resolution Date</label>
                                <input type="text" value="{{ isset($GrivanceInvestigationModel->resolution_date) ? $GrivanceInvestigationModel->resolution_date :'' }} " @if($Grivance_Parent->Assigned=="No" || isset($GrivanceInvestigationModel->resolution_date)) readonly @else required data-parsley-required-message="Anticipated resolution date is required" data-parsley-greaterThan="#inves_date" data-parsley-greaterThan-message="Resolution date must be after the investigation start date" @endif class="form-control datepicker" id="exp_date" name="resolution_date" placeholder="Choose Date">
                            </div>
                            <div class="col-lg-4 col-sm-4">
                                <a href="javascript:void(0)" class="btn btn-themeBlue AddMoreGrivance">Add More</a>
                            </div>
                        <div class="row appendHere">
                            <input type="hidden" name="counts" value="1" id="counts">
                            <div class="col-12">
                                <label for="inves_find" class="form-label">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                                <textarea class="form-control" @if($Grivance_Parent->Assigned=="No") readonly @else required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" @endif id="inves_find" name="inves_find_recommendations[]" placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"></textarea>
                            </div>
                            <div class="col-lg-4 col-sm-6 mt-1">
                                <label for="followup_actions" class="form-label">FOLLOW-UP ACTIONS</label>
                                <select class="form-select select2t-none follow_up_action_id" id="follow_up_action" name="follow_up_action[]" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select a follow-up action" @endif aria-label="Default select example">
                                    <option value=""> Select Follow-Up Action</option>
                                    @if($Grivance_Parent->RequestforStatment !="Yes")
                                        <option value="GatherWitnessStatements">Gather Witness Statements</option>
                                    @endif
                                    <option value="InspectSite">Inspect Site</option>
                                    <option value="ReviewDocuments">Review Documents</option>
                                    <option value="CCTVFootageReview">CCTV Footage Review</option>
                                    <option value="CheckAccessLogs">Check Access Logs</option>
                                    <option value="GatherPhysicalEvidence">Gather Physical Evidence</option>
                                </select>
                            </div>
                            <div class="col-lg-6 col-sm-6 mt-4">
                                <input type="text" class="form-control" placeholder="Type Here" name="follow_up_description[]" id="follow_up_description" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Additional follow-up information is required" @endif>
                            </div>
                            @if($Grivance_Parent->RequestforStatment !="Yes")
                                <div class="col-lg-2 col-sm-6 mt-4  d-none" id="RequestForStatement">
                                    <a href="javascript:void(0)" class="btn btn-themeBlue RequestForStatement" data-id="{{$Grivance_Parent->Grivance_id}}">Request For Statement</a>
                                </div>
                            @endif
                            <div class="col-lg-4 col-sm-6">
                                <label for="inves_stage" class="form-label">INVESTIGATION STAGE</label>
                                <select class="form-select select2t-none" id="investigation_stage" name="investigation_stage[]" aria-label="Default select example" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select an investigation stage" @endif>
                                    <option value="">Select a stage</option>
                                    <option value="InitialReview">Initial Review</option>
                                    <option value="Delegated">Delegated</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Compiled">Report Compiled</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="resol_notes" class="form-label">RESOLUTION NOTES</label>
                                <textarea class="form-control" id="resol_notes" name="resolution_note[]" @if($Grivance_Parent->Assigned=="No") readonly @else required data-parsley-required-message="Resolution notes are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of resolution notes" @endif placeholder="Type Here..." rows="4"></textarea>
                            </div>
                        </div>

                            @if($Grivance_Parent->Assigned == "Yes")
                                <div class="col-lg-4 col-sm-6">
                                    <label for="outcome_type" class="form-label">OUTCOME TYPE</label>
                                    <select class="form-select select2t-none" id="outcome_type" name="outcome_type"
                                    
                                        aria-label="Default select example">
                                        <option value="">Select Outcome</option>
                                        @if($Grivance_Parent->Assigned == "Yes") 
                                            <option value="DeliverToHr">Resolved  Send To Hr</option>
                                            <option value="Unresolved"  {{ ($Grivance_Parent->outcome_type == "Unresolved")  ?'selected':'' }}>Unresolved</option>

                                        @else
                                        <option value="Resolved" {{ ($Grivance_Parent->outcome_type == "resolved")  ?'selected':'' }}>Resolved </option>
                                        <option value="Unresolved"  {{ ($Grivance_Parent->outcome_type == "Unresolved")  ?'selected':'' }}>Unresolved</option>
                                        <option value="Dismissed"  {{ ($Grivance_Parent->outcome_type == "Dismissed")  ?'selected':'' }}>Dismissed</option>
                                        <option value="OnHold"  {{ ($Grivance_Parent->outcome_type == "OnHold")  ?'selected':'' }}>On Hold</option>
                                        <option value="WithdrawalbyComplainant"  {{ ($Grivance_Parent->outcome_type == "WithdrawalbyComplainant")  ?'selected':'' }}>Withdrawal by Complainant</option>
                                        <option value="EscalatedforFurtherInvestigation"  {{ ($Grivance_Parent->outcome_type == "EscalatedforFurtherInvestigation")  ?'selected':'' }}>Escalated for Further Investigation</option>
                                        <option value="PolicyImprovementSuggested"  {{ ($Grivance_Parent->outcome_type == "PolicyImprovementSuggested")  ?'selected':'' }}>Policy Improvement Suggested</option>
                                        @endif
                                    
                                    </select>
                                </div>
                            @endif
                        
                            @if($Grivance_Parent->Assigned == "DeliverToHr") 
        
                            
                            
                                <div class="col-lg-4 col-sm-6">
                                    <label for="action_taken" class="form-label">ACTION TAKEN</label>

                                    <select class="form-select select2t-none" id="action_taken" name="action_taken"
                                    
                                        aria-label="Default select example">
                                        <option value="">Select Action</option>
                                        @if($ActionStore->isNotEmpty())
                                            @foreach($ActionStore as $item)
                                                <option value="{{ base64_encode($item->id) }}" {{ ($Grivance_Parent->action_taken == $item->id)  ?'selected':'' }}>{{ $item->ActionName }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="action_taken" class="form-label">STATUS</label>

                                    <select class="form-select select2t-none" id="Status" name="STATUS"
                                    
                                        aria-label="Default select example">
                                        <option value="">Select Status</option>
                                        <option value="pending" {{$Grivance_Parent->status =="pending"  ?'selected':''}}>Pending</option>
                                        <option value="in_review" {{$Grivance_Parent->status =="in_review"  ?'selected':''}}>In Review</option>
                                        <option value="resolved" {{$Grivance_Parent->status =="resolved"  ?'selected':''}}>Resolved</option>
                                        <option value="rejected" {{$Grivance_Parent->status =="rejected"  ?'selected':''}}>Rejected</option>
                                    </select>
                                </div>
                                @if(!isset($Grivance_Parent->SentToGM ) ||$Grivance_Parent->SentToGM != "Yes" && !isset($Grivance_Parent->Gm_Decision)) 
                                <div class="col-lg-4 hideApprovalRequest">
                                    <label for="" class="form-label">APPROVAL</label>
                                    <div class="mt-lg-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="approval_request"
                                                @if($Grivance_Parent->Assigned != "DeliverToHr") 
                                                    disabled 
                                                @endif 
                                                id="inlineCheckbox1" >
                                            <label class="form-check-label" for="inlineCheckbox1">
                                                Forward the investigation report to relevant approvers
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endif
                            @if($Grivance_Parent->Assigned == "Yes")
                                <div class="col-lg-4 col-sm-6">
                                    <label class="form-label">FILE UPLOAD</label>
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input type="file" 
                                            id="uploadFile" 
                                            multiple 
                                            name="investigation_file[]" 
                                            accept=".pdf, image/*" 
                                            @if($Grivance_Parent->Assigned=="No") disabled @else data-parsley-max-file-size="5" data-parsley-fileextension="pdf,png,jpg,jpeg" data-parsley-fileextension-message="Only PDF and image files are allowed" @endif>
                                        </div>
                                        <div class="uploadFile-text">PNG, JPEG, PDF</div>
                                    </div>
                                </div>
                            @endif
                            @if(!isset($Grivance_Parent->Request_Identity_Disclosure)  && !isset($Grivance_Parent->Gm_Decision))
                                <div class="col-lg-4 col-sm-6 align-self-end">
                                    <a href="javascript:void(0)" @if($Grivance_Parent->Assigned=="No") disabled @endif class="btn btn-themeSkyblue RequestIdentity" data-id="{{ $Grivance_Parent->id}}">Request Identity Disclosure</a>
                                </div>
                            @endif

                        </div>
                    @endif
                
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                    </div>
                </form>
          
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,      // Close the picker after selection
        todayHighlight: true  // Highlight today's date
    });
    $('#Employee_id').select2({
        placeholder: 'Select Employee',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#Grivance_offence_id').select2({
        placeholder: 'Select Grievance Offence',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#Grivance_Cat_id').select2({
        placeholder: 'Select Grievance Category',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#witness_id').select2({
        placeholder: 'Select Wintness',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#assign_to').select2({
        placeholder: 'Select Committee ',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#action_taken').select2({
        placeholder: 'Select Action ',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#follow_up_action').select2({
        placeholder: 'Select follow Up Action',
        minimumResultsForSearch: -1,
        width: '100%'
    });
    $('#assign_to').select2({
        placeholder: 'Select Committee ',
        minimumResultsForSearch: -1,
        width: '100%'
    });

    $("#investigationReportSubmit").parsley();

            $('#investigationReportSubmit').on('submit', function(e)
            {
                e.preventDefault();
               
                let form = $(this);
                if (form.parsley().isValid()) 
                {
                    let formData = new FormData(this);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.grivance.InvestigationReportStore') }}", 
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) {
                                // Show success message
                                window.location.href = response.route;
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
                    return false;
                }
            });
            
    
    
});
$(document).on("change",".follow_up_action_id",function() {

    var action = $(this).val();
    if(action =="GatherWitnessStatements")
    {
        $("#RequestForStatement").removeClass('d-none');   
    }
    else
    {
        $("#RequestForStatement").addClass('d-none');
    }
});
$(document).on("click",".RequestForStatement",function() {

    var id = $(this).data('id');
    $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.grivance.RequestForStatement') }}", 
                type: 'POST',
                data: {"id":id,"_token":"{{ csrf_token() }}"},
                success: function(response) {
                    if (response.success) {
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

});


    $(document).on("change","#Employee_id",function() {
    
        var emp =  $(this).val();

        $.ajax({
        url: "{{ route('GrievanceAndDisciplinery.grivance.GetEmployeeDetails') }}", 
        type: "get",
        data: {"_token":"{{ csrf_token() }}","emp":emp},
        success: function(response) {
            console.log(response);
            if (response.success) {
            
             $("#supervisor").val(response.data.Superviser.Main_Name);  
              $("#employee_main_id").val(response.data.Employee.Emp_id);
              $("#Department").val(response.data.Employee.DepartmentName);
              $("#job_title").val(response.data.Employee.PositionName);
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
    $(document).on("click",".AddMoreGrivance",function(){
        var counts = parseInt( $("#counts").val());
        counts = counts+1;
        var string=`<hr class="mt-2 Remove_c_${counts}">
                    <div class="col-12  Remove_c_${counts}">
                        <label for="inves_find" class="form-label">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                        <textarea class="form-control" @if($Grivance_Parent->Assigned=="No") readonly @else required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" @endif id="inves_find" name="inves_find_recommendations[]" placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"></textarea>
                    </div>
                    <div class="col-lg-4 col-sm-6  Remove_c_${counts}">
                        <label for="followup_actions" class="form-label">FOLLOW-UP ACTIONS</label>
                        <select class="form-select select2t-none" id="follow_up_action_${counts}" name="follow_up_action[]" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select a follow-up action" @endif aria-label="Default select example">
                            <option value=""></option>
                            <option value="InspectSite">Inspect Site</option>
                            <option value="ReviewDocuments">Review Documents</option>
                            <option value="CCTVFootageReview">CCTV Footage Review</option>
                            <option value="CheckAccessLogs">Check Access Logs</option>
                            <option value="GatherPhysicalEvidence">Gather Physical Evidence</option>
                        </select>
                    </div>
                    <div class="col-lg-8 col-sm-6 mt-3 Remove_c_${counts}">
                        <input type="text" class="form-control" placeholder="Type Here" name="follow_up_description[]" id="follow_up_description" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Additional follow-up information is required" @endif>
                    </div>
                    <div class="col-lg-4 col-sm-6 Remove_c_${counts}">
                        <label for="inves_stage" class="form-label">INVESTIGATION STAGE</label>
                        <select class="form-select select2t-none" id="investigation_stage_${counts}" name="investigation_stage[]" aria-label="Default select example" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select an investigation stage" @endif>
                            <option value="">Select a stage</option>
                            <option value="InitialReview">Initial Review</option>
                            <option value="Delegated">Delegated</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Compiled">Report Compiled</option>
                        </select>
                    </div>
                    <div class="col-11 Remove_c_${counts}">
                        <label for="resol_notes" class="form-label">RESOLUTION NOTES</label>
                        <textarea class="form-control" id="resol_notes" name="resolution_note[]" @if($Grivance_Parent->Assigned=="No") readonly @else required data-parsley-required-message="Resolution notes are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of resolution notes" @endif placeholder="Type Here..." rows="4"></textarea>
                    </div>
                    <div class="col-1 mt-3 Remove_c_${counts}">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="${counts}" >
                            <img src="{{asset("resorts_assets/images/trash-red.svg")}}" alt="Delete" class="img-fluid">
                        </a>
                    </div>`;
                   
            $(".appendHere").append(string);
            $("#counts").val(counts)
            $('#action_taken_'+counts).select2({
                placeholder: 'Select Action ',
                minimumResultsForSearch: -1,
                width: '100%'
            });
            $('#investigation_stage_'+counts).select2({
                placeholder: 'Select Inestigation Stage',
                minimumResultsForSearch: -1,
                width: '100%'
            })
            $('#follow_up_action_'+counts).select2({
                placeholder: 'Select follow Up Action',
                minimumResultsForSearch: -1,
                width: '100%'
            });
            $('#assign_to_'+counts).select2({
                placeholder: 'Select Committee ',
                minimumResultsForSearch: -1,
                width: '100%'
            });


    });
    $(document).on("click",".delete-row-btn",function(){

        var location = $(this).data("id");
        $(".Remove_c_"+location).remove();
        $("#counts").val(parseInt( $("#counts").val())-1);
    });
    $(document).on("change","#outcome_type",function(){

        if($(this).val() == "Resolved")
        {
            $(".hideApprovalRequest").hide();
        }
        else{
            $(".hideApprovalRequest").show();
        }

      
        
    });
    
    $(document).on("click",".RequestIdentity",function(){
    
    
        $.ajax({
            url: "{{ route('GrievanceAndDisciplinery.grivance.RequestIdentity') }}", 
            type: 'POST',
            data: {"_token":"{{ csrf_token()}}","id":$(this).data("id")},
            success: function(response) {
                console.log(response.success);
                if (response.success) {
                    toastr.success(response.message,"Success",
                    {
                        positionClass: 'toast-bottom-right'
                    });

                    $(".align-self-end").hide();
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
    });
    
         window.Parsley.addValidator('greaterThan', {
        validateString: function(value, requirement) {
            // Get the dates
            var startDateStr = $(requirement).val();
            var endDateStr = value;
            
            if (!startDateStr || !endDateStr) return true;
            
            // Parse dates (assuming format dd-mm-yyyy)
            var startParts = startDateStr.split('-');
            var endParts = endDateStr.split('-');
            
            if (startParts.length !== 3 || endParts.length !== 3) return true;
            
            var startDate = new Date(startParts[2], startParts[1] - 1, startParts[0]);
            var endDate = new Date(endParts[2], endParts[1] - 1, endParts[0]);
            
            // Return true if end date is greater than start date
            return endDate > startDate;
        },
        priority: 33
    });
    
    // Custom validator for file size
    window.Parsley.addValidator('maxFileSize', {
        validateString: function(_value, maxSize, parsleyInstance) {
            var files = parsleyInstance.$element[0].files;
            if (files.length === 0) {
                return true;
            }
            
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > maxSize * 1024 * 1024) {
                    return false;
                }
            }
            return true;
        },
        requirementType: 'integer',
        messages: {
            en: 'File size must not exceed %sMB'
        }
    });
    
</script>
@endsection