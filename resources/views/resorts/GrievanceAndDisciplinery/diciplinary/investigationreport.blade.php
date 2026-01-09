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
                                    <td>{{ $Disciplinary_parent->Disciplinary_id }}</td>
                                </tr>
                                <tr>
                                    <th>Grievance Category:</th>
                                    <td>{{ $Disciplinary_parent->CatName }}</td>
                                </tr>
                                <tr>
                                    <th>Disciplinary Offence:</th>
                                    <td>{{ $Disciplinary_parent->SubCatName }}</td>
                                </tr>
                               
                                <tr>
                                    <th>Priority Level:</th>
                                    <td>
                                        @if($Disciplinary_parent->Priority =="High")
                                            <span class="text-danger fw-600">High</span>
                                        @elseif($Disciplinary_parent->Priority =="Medium")
                                            <span class="text-success fw-600">Medium</span>
                                        @else
                                            <span class="text-primary fw-600">Low</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Attachements:</th>
                                    <td>
                                        @if(isset($Disciplinary_parent->Attachements) && !empty($Disciplinary_parent->Attachements))
                                            @foreach(explode(",", $Disciplinary_parent->Attachements) as $g)
                                                @php 
                                                    $Path = $Path . "/" . $g;
                                                @endphp
                                                <a target="_blank" href="{{ URL::asset($Path) }}">{{ $g }}</a><br>
                                            @endforeach
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th> Signed Document:</th>
                                    <td>
                                        @if(isset($Disciplinary_parent->upload_signed_document) && !empty($Disciplinary_parent->upload_signed_document))
                                                @php 
                                                    $upload_signed_document = $Path."/" . $Disciplinary_parent->upload_signed_document;
                                                @endphp

                                                <a target="_blank" href="{{ URL::asset($upload_signed_document) }}">{{ $g }}</a><br>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <h6>Description:</h6>
                        <p>{!!  $Disciplinary_parent->Incident_description !!}</p>
                    </div>

                    <div class="col-lg-6">
                        <h6 class="mb-md-3 mb-2">Employee Details: </h6>
                        <div class="img-circle me-2">
                            <img src="{{  Common::getResortUserPicture($Disciplinary_parent->GetEmployee->resortAdmin->id)}}" alt="image">
                        </div>
                        <h6 class="mb-0">{{ $Disciplinary_parent->first_name }} {{ $Disciplinary_parent->last_name }}</h6>

                        <div class="table-responsive mt-3">
                            <table class="table-lableSmallLabel">
                                <tr>
                                    <th>Department:</th>
                                    <td>{{ $Disciplinary_parent->DepartmentName }}</td>
                                </tr>
                                <tr>
                                    <th>Position:</th>
                                    <td>{{ $Disciplinary_parent->PositiontName }}</td>
                                </tr>
                                <tr>
                                    <th>Contact Details:</th>
                                    <td>{{ $Disciplinary_parent->personal_phone }}</td>
                                </tr>
                            
                            </table>
                        </div>
                    </div> <!-- /col-lg-6 -->
                </div> <!-- /row -->
            </div> <!-- /bg-themeGrayLight -->
            @if(isset($parent) && $parent->isNotEmpty())
                <div class="mt-3 bg-themeGrayLight mb-md-4 mb-3">
                    <div class="row g-lg-5 g-sm-4 g-3">
                        <h5>History</h5>
                        <hr>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table border table-lableSmallLabel">
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Investigation Start Date - Anticipated Resolution Date</th>
                                            <th>Committee Member</th>
                                            <th>Outcome</th>
                                            <th>Attachments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($parent) && !empty($parent))
                                            @foreach($parent as $key => $value)
                                                <tr>
                                                    <td class="text-center">{{ $loop->iteration }}</td>
                                                    <td>{{ $value->invesigation_date }} - {{ $value->resolution_date }}</td>
                                                    <td>{{ $value->first_name }} {{ $value->last_name }}  ({{$value->rank}})</td>
                                                    <td>{{ $value->outcome_type }}</td>
                                                    <td>
                                                        @php
                                                            $files = '';
                                                            if (!empty($value->investigation_file)) {
                                                                foreach (explode(",", $value->investigation_file) as $file) {
                                                                    $files .= '<a href="' . URL::asset($Path . '/' . $file) . '" target="_blank">' . e($file) . '</a><br>';
                                                                }
                                                            }
                                                        @endphp
                                                        {!! $files !!}
                                                    </td>
                                                </tr>
                                                @php $childData = $child->where('Disciplinary_P_id', $value->id); @endphp
                                                @if($childData->isNotEmpty())
                                                    <tr>
                                                        <td colspan="5">
                                                            <table class="table table-sm table-bordered mt-2">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Sr No.</th>
                                                                        <th>Investigation Find Recommendations</th>
                                                                        <th>Follow Up Action</th>
                                                                        <th>Follow Up Description</th>
                                                                        <th>Investigation Stage</th>
                                                                        <th>Resolution Note</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($childData as $c)
                                                                        <tr>
                                                                            <td  class="text-center">{{ $loop->iteration }}</td>
                                                                            <td>{{ $c->inves_find_recommendations }}</td>
                                                                            <td>{{ $c->follow_up_action }}</td>
                                                                            <td>{{ $c->follow_up_description }}</td>
                                                                            <td>{{ $c->investigation_stage }}</td>
                                                                            <td>{{ $c->resolution_note }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="bg-themeGrayLight mt-3 mb-md-4 mb-3">
                <div class="row g-lg-5 g-sm-4 g-3">
                <form id="investigationReportSubmit" data-parsley-validate>
                    @csrf
               
                
                    <input type="hidden" name="Disciplinary_form_id" value="{{ $Disciplinary_parent->Disciplinary_id}}">
                    <input type="hidden" name="committee_member_id" value="{{$committee_member_id}}">
                    @if($Disciplinary_parent->Assigned !="DeliverToHr" ||  $Disciplinary_parent->status !="resolved")
                        <div class="row g-xl-4 g-3 mb-md-4 mb-3">
                            <div class="col-lg-4 col-sm-6">
                                <label for="inves_date" class="form-label">INVESTIGATION START DATE</label>
                                <input type="text" value="{{ isset($GrivanceInvestigationModel->inves_start_date) ? $GrivanceInvestigationModel->inves_start_date :'' }}"
                                @if($Disciplinary_parent->Assigned=="No" || isset($GrivanceInvestigationModel->inves_start_date )) readonly @else required data-parsley-required-message="Investigation start date is required" @endif class="form-control datepicker" id="inves_date" name="invesigation_date" placeholder="Choose Date">
                            </div>
                            <div class="col-lg-4 col-sm-4">
                                <label for="exp_date" class="form-label">Anticipated Resolution Date</label>
                                <input type="text" value="{{ isset($GrivanceInvestigationModel->resolution_date) ? $GrivanceInvestigationModel->resolution_date :'' }} " @if($Disciplinary_parent->Assigned=="No" || isset($GrivanceInvestigationModel->resolution_date)) readonly @else required data-parsley-required-message="Anticipated resolution date is required" data-parsley-greaterThan="#inves_date" data-parsley-greaterThan-message="Resolution date must be after the investigation start date" @endif class="form-control datepicker" id="exp_date" name="resolution_date" placeholder="Choose Date">
                            </div>
                            <div class="col-lg-4 col-sm-4">
                                <a href="javascript:void(0)" class="btn btn-themeBlue AddMoreGrivance">Add More</a>
                            </div>
                        <div class="row appendHere">
                            <input type="hidden" name="counts" value="1" id="counts">
                            <div class="col-12">
                                <label for="inves_find" class="form-label">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                                <textarea class="form-control" @if($Disciplinary_parent->Assigned=="No") readonly @else required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" @endif id="inves_find" name="inves_find_recommendations[]" placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"></textarea>
                            </div>
                            <div class="col-lg-4 col-sm-6 mt-1">
                                <label for="followup_actions" class="form-label">FOLLOW-UP ACTIONS</label>
                                <select class="form-select select2t-none follow_up_action_id" id="follow_up_action" name="follow_up_action[]" @if($Disciplinary_parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select a follow-up action" data-parsley-errors-container="#follow_up_action_error" @endif aria-label="Default select example">
                                    <option value=""> Select Follow-Up Action</option>
                                    @if($Disciplinary_parent->Request_For_Statement !="Yes")
                                        <option value="GatherWitnessStatements">Gather Witness Statements</option>
                                    @endif
                                    <option value="InspectSite">Inspect Site</option>
                                    <option value="ReviewDocuments">Review Documents</option>
                                    <option value="CCTVFootageReview">CCTV Footage Review</option>
                                    <option value="CheckAccessLogs">Check Access Logs</option>
                                    <option value="GatherPhysicalEvidence">Gather Physical Evidence</option>
                                </select>
                                <div id="follow_up_action_error"></div>
                            </div>
                            <div class="col-lg-6 col-sm-6 mt-4">
                                <input type="text" class="form-control" placeholder="Type Here" name="follow_up_description[]" id="follow_up_description" @if($Disciplinary_parent->Assigned=="No") disabled @else required data-parsley-required-message="Additional follow-up information is required" @endif>
                            </div>
                                <div class="col-lg-2 col-sm-6 mt-4  d-none" id="RequestForStatement">
                                    <a href="javascript:void(0)" class="btn btn-themeBlue RequestForStatement" data-id="{{$Disciplinary_parent->Disciplinary_id}}">Request For Statement</a>
                                </div>
                            <div class="col-lg-4 col-sm-6">
                                <label for="inves_stage" class="form-label">INVESTIGATION STAGE</label>
                                <select class="form-select select2t-none" id="investigation_stage" name="investigation_stage[]" aria-label="Default select example" @if($Disciplinary_parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select an investigation stage" data-parsley-errors-container="#investigation_stage_error" @endif>
                                    <option value="" disabled selected>Select a stage</option>
                                    <option value="InitialReview">Initial Review</option>
                                    <option value="Delegated">Delegated</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Compiled">Report Compiled</option>
                                </select>
                                <div id="investigation_stage_error"></div>
                            </div>

                            <div class="col-12">
                                <label for="resol_notes" class="form-label">RESOLUTION NOTES</label>
                                <textarea class="form-control" id="resol_notes" name="resolution_note[]" @if($Disciplinary_parent->Assigned=="No") readonly @else required data-parsley-required-message="Resolution notes are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of resolution notes" @endif placeholder="Type Here..." rows="4"></textarea>
                            </div>
                        </div>

                                <div class="col-lg-4 col-sm-6">
                                    <label for="outcome_type" class="form-label">OUTCOME TYPE</label>
                                    <select class="form-select select2t-none" id="outcome_type" name="outcome_type"
                                    
                                        aria-label="Default select example">
                                        <option value="">Select Outcome</option>
                                        @if($Disciplinary_parent->SendtoHr != "Yes") 
                                            <option value="DeliverToHr">Resolved  Send To Hr</option>
                                            <option value="Unresolved"  {{ ($Disciplinary_parent->outcome_type == "Unresolved")  ?'selected':'' }}>Unresolved</option>

                                        @else
                                        <option value="Unresolved"  {{ ($Disciplinary_parent->outcome_type == "Unresolved")  ?'selected':'' }}>Unresolved</option>
                                        <option value="Dismissed"  {{ ($Disciplinary_parent->outcome_type == "Dismissed")  ?'selected':'' }}>Dismissed</option>
                                        <option value="OnHold"  {{ ($Disciplinary_parent->outcome_type == "OnHold")  ?'selected':'' }}>On Hold</option>
                                        <option value="WithdrawalbyComplainant"  {{ ($Disciplinary_parent->outcome_type == "WithdrawalbyComplainant")  ?'selected':'' }}>Withdrawal by Complainant</option>
                                        <option value="EscalatedforFurtherInvestigation"  {{ ($Disciplinary_parent->outcome_type == "EscalatedforFurtherInvestigation")  ?'selected':'' }}>Escalated for Further Investigation</option>
                                        <option value="PolicyImprovementSuggested"  {{ ($Disciplinary_parent->outcome_type == "PolicyImprovementSuggested")  ?'selected':'' }}>Policy Improvement Suggested</option>
                                        @endif
                                    
                                    </select>
                                </div>
                        
                            @if($Disciplinary_parent->SendtoHr == "Yes") 
        
                            
                            
                               
                                <div class="col-lg-4 col-sm-6">
                                    <label for="action_taken" class="form-label">STATUS</label>

                                    <select class="form-select select2t-none" id="Status" name="STATUS"
                                    
                                        aria-label="Default select example">
                                        <option value="">Select Status</option>
                                        <option value="pending" {{$Disciplinary_parent->status =="pending"  ?'selected':''}}>Pending</option>
                                        <option value="in_review" {{$Disciplinary_parent->status =="in_review"  ?'selected':''}}>In Review</option>
                                        <option value="resolved" {{$Disciplinary_parent->status =="resolved"  ?'selected':''}}>Resolved</option>
                                        <option value="rejected" {{$Disciplinary_parent->status =="rejected"  ?'selected':''}}>Rejected</option>
                                    </select>
                                </div>
                               
                            @endif
                                <div class="col-lg-4 col-sm-6">
                                 <label class="form-label">FILE UPLOAD</label>
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input 
                                                type="file" 
                                                id="uploadFile" 
                                                multiple 
                                                name="investigation_file[]" 
                                                accept=".pdf, image/*" 
                                                @if($Disciplinary_parent->Assigned=="No") 
                                                    disabled 
                                                @else 
                                                    data-parsley-max-file-size="5" 
                                                    data-parsley-fileextension="pdf,png,jpg,jpeg" 
                                                    data-parsley-fileextension-message="Only PDF and image files are allowed" 
                                                @endif
                                            >
                                        </div>
                                        <div class="uploadFile-text">PNG, JPEG, PDF</div>
                                        <ul id="selectedFileNames" class="mt-2 list-unstyled text-primary small"></ul>
                                    </div>
                                </div>
                       

                        </div>
                    @endif
                
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {

    document.getElementById('uploadFile').addEventListener('change', function () {
    const fileList = this.files;
    const output = document.getElementById('selectedFileNames');
    output.innerHTML = ''; // Clear previous list

    if (fileList.length === 0) {
        output.innerHTML = '<li>No file selected</li>';
        return;
    }

    for (let i = 0; i < fileList.length; i++) {
        const li = document.createElement('li');
        li.textContent = fileList[i].name;
        output.appendChild(li);
    }
});
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
                        url: "{{ route('GrievanceAndDisciplinery.Disciplinary.InvestigationReportStore') }}", 
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
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.RequestForStatement') }}", 
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


    // $(document).on("change","#Employee_id",function() {
    
    //     var emp =  $(this).val();

    //     $.ajax({
    //     url: "{{ route('GrievanceAndDisciplinery.grivance.GetEmployeeDetails') }}", 
    //     type: "get",
    //     data: {"_token":"{{ csrf_token() }}","emp":emp},
    //     success: function(response) {
    //         console.log(response);
    //         if (response.success) {
            
    //          $("#supervisor").val(response.data.Superviser.Main_Name);  
    //           $("#employee_main_id").val(response.data.Employee.Emp_id);
    //           $("#Department").val(response.data.Employee.DepartmentName);
    //           $("#job_title").val(response.data.Employee.PositionName);
    //         }
    //         else
    //         {
    //             toastr.error(response.message, "Error",
    //             {
    //                 positionClass: 'toast-bottom-right'
    //             });
    //         }
    //     },
    //     error: function(response) {
    //         var errors = response.responseJSON;
    //         var errs = '';
    //         $.each(errors.errors, function(key, error) {
    //             errs += error + '<br>';
    //         });
    //         toastr.error(errs, {
    //             positionClass: 'toast-bottom-right'
    //         });
    //     }
    // });
    // });
    $(document).on("click", ".AddMoreGrivance", function () {
            var counts = parseInt($("#counts").val());
            counts += 1;

            var string = `
            <hr     class="mt-3 Remove_c_${counts}">
                <div class="col-12 Remove_c_${counts}">
                    <label class="form-label">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                    <textarea class="form-control" name="inves_find_recommendations[]" required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" rows="4" placeholder="Add detailed notes..."></textarea>
                </div>

                <div class="col-lg-4 col-sm-6 Remove_c_${counts}">
                    <label class="form-label">FOLLOW-UP ACTIONS</label>
                    <select class="form-select select2t-none" name="follow_up_action[]" id="follow_up_action_${counts}" required data-parsley-required-message="Please select a follow-up action" data-parsley-errors-container="#follow_up_action_error_${counts}">
                        <option value="">Select follow-up action</option>
                        <option value="InspectSite">Inspect Site</option>
                        <option value="ReviewDocuments">Review Documents</option>
                        <option value="CCTVFootageReview">CCTV Footage Review</option>
                        <option value="CheckAccessLogs">Check Access Logs</option>
                        <option value="GatherPhysicalEvidence">Gather Physical Evidence</option>
                    </select>
                    <div id="follow_up_action_error_${counts}"></div>
                </div>

                <div class="col-lg-8 col-sm-6 mt-3 Remove_c_${counts}">
                    <input type="text" class="form-control" name="follow_up_description[]" placeholder="Type Here" required data-parsley-required-message="Follow-up description is required">
                </div>

                <div class="col-lg-4 col-sm-6 Remove_c_${counts}">
                    <label class="form-label">INVESTIGATION STAGE</label>
                    <select class="form-select select2t-none" name="investigation_stage[]" id="investigation_stage_${counts}" required data-parsley-required-message="Please select an investigation stage" data-parsley-errors-container="#investigation_stage_error_${counts}">
                        <option value="">Select a stage</option>
                        <option value="InitialReview">Initial Review</option>
                        <option value="Delegated">Delegated</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Compiled">Report Compiled</option>
                    </select>
                    <div id="investigation_stage_error_${counts}"></div>
                </div>

                <div class="col-11 Remove_c_${counts}">
                    <label class="form-label">RESOLUTION NOTES</label>
                    <textarea class="form-control" name="resolution_note[]" required data-parsley-required-message="Resolution notes are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters" rows="4" placeholder="Type Here..."></textarea>
                </div>

                <div class="col-1 mt-3 Remove_c_${counts}">
                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="${counts}">
                        <img src="{{asset("resorts_assets/images/trash-red.svg")}}" alt="Delete" class="img-fluid">
                    </a>
                </div>`;

            $(".appendHere").append(string);
            $("#counts").val(counts);

            // Reinitialize select2
            $('#follow_up_action_' + counts).select2({ placeholder: 'Select follow up action', width: '100%' });
            $('#investigation_stage_' + counts).select2({ placeholder: 'Select investigation stage', width: '100%' });

            // Rebind Parsley
            $('#yourFormId').parsley().destroy();
            $('#yourFormId').parsley();
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
        else
        {
            $(".hideApprovalRequest").show();
        }
    });
    
    // $(document).on("click",".RequestIdentity",function(){
    
    
    //     $.ajax({
    //         url: "{{ route('GrievanceAndDisciplinery.grivance.RequestIdentity') }}", 
    //         type: 'POST',
    //         data: {"_token":"{{ csrf_token()}}","id":$(this).data("id")},
    //         success: function(response) {
    //             console.log(response.success);
    //             if (response.success) {
    //                 toastr.success(response.message,"Success",
    //                 {
    //                     positionClass: 'toast-bottom-right'
    //                 });

    //                 $(".align-self-end").hide();
    //             } 
    //             else 
    //             {
    //                     toastr.error(response.message, "Error",
    //                     {
    //                         positionClass: 'toast-bottom-right'
    //                     });
    //             }
    //         },
    //         error: function(response) 
    //         {
    //             var errors = response.responseJSON;
    //             if (errors.error) 
    //             { 
    //                 toastr.error(errors.error, "Error", 
    //                 {
    //                     positionClass: 'toast-bottom-right'
    //                 });
    //             }
    //             else 
    //             {
    //                 var errs = '';
    //                 $.each(errors.errors, function(key, error) 
    //                 {
    //                     errs += error + '<br>';
    //                 });
    //                 toastr.error(errs, "Validation Error",{
    //                     positionClass: 'toast-bottom-right'
    //                 });
    //             }
    //         }

    //     });
    // });
    
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