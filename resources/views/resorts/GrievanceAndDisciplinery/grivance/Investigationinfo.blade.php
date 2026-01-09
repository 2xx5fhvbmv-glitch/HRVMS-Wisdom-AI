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
                                </tr>
                                <tr>
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
                                    <th>Attachements:</th>
                                    <td>@if(isset($Grivance_Parent->Attachements) && !empty($Grivance_Parent->Attachements))
                                            @foreach(explode(",",$Grivance_Parent->Attachements) as  $g)
                                                
                                            @php 
                                                $Path = $path."/".$Grivance_Parent->Grivance_id."/".$g;
                                            @endphp
                                            <a target="_blank" href="{{URL::asset($Path)}}" >{{$g}}</a></br>
                                            @endforeach
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
                        
                       

                        <h6 class="mb-md-3 mb-2">Employee Details:  @if($Grivance_Parent->Grivance_Submission_Type =="Yes")
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
                                    </h6>

                                    @if(in_array($auth_id, $GrivanceKeys) || 
                                        (!in_array($auth_id, $GrivanceKeys) && $Grivance_Parent->Grivance_Submission_Type != "Yes" && $Grivance_Parent->Grivance_Submission_Type != "No"))

                                            <div class="d-flex align-items-center mb-md-2 mb-1">
                                                <div class="img-circle me-2">
                                                    <img src="{{ Common::getResortUserPicture($Grivance_Parent->Parentid) }}" alt="image">
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
                                                </table>
                                            </div>

                                        @endif

                        
                    </div>
                </div>
                @if(!empty($GrivanceSubmissionHistory))
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
                                            <td>{{ $value->follow_up_description }}</td>
                                            <td>{{ $value->investigation_stage }}</td>
                                            <td>{{ $value->inves_find_recommendations }}</td>
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
                                    @if($GrivanceInvestigationModel && !empty($GrivanceInvestigationModel))
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
            <div class=" mb-md-4 mb-3">
                <div class="row">
  
                        <div class="table">
                            <table class="table ">
                                <thead>
                                    <tr>
                                        <th>Out-come Type</th>
                                        <th>{{ ucfirst($Grivance_Parent->outcome_type) }}</th>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        @php 
                                        $status = preg_replace('/[^A-Za-z0-9 ]/', '', $Grivance_Parent->status);

                                        @endphp
                                        <th>   {{ ucfirst($status) }}</th>
                                    </tr>
                                    
                                </thead>
                            </table>
                        </div>
                </div>
            </div>

            @if($rankKey == 'GM')
                <form id="investigationReportSubmit" data-parsley-validate>
                    @csrf
                    <input type="hidden" name="Grievant_form_id" value="{{ $Grivance_Parent->id}}">
                    <input type="hidden" value="GmApproval" name="flag">

                    <div class="row">
                        <div class="col-md-6">
                            <label for="Gm_Decision" class="form-label mt-3">Reason</label>
                            <textarea class="form-control" name="Gm_Resoan"> </textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="Gm_Decision" class="form-label mt-3">GM Decision</label>
                            <select class="form-select select2t-none Gm_Decision" name="Gm_Decision" required data-parsley-required-message="Please select a decision">
                                <option value="">Select Decision</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3 rejection-container Rejection_reason" style="display: none;">
                        <div class="col-md-12">
                            <label for="Rejection_reason" class="form-label">Rejection Reason</label>
                            <textarea class="form-control rejection-field" id="Rejection_reason" name="Rejection_reason"
                                placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"
                                data-parsley-required="false"
                                data-parsley-required-message="Investigation findings are required"
                                data-parsley-minlength="20"
                                data-parsley-minlength-message="Please provide at least 20 characters of detailed findings"></textarea>
                        </div>
                    </div>
                    
                    <div class="card-footer mt-3 text-end">
                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {
    $("#investigationReportSubmit").parsley();

    $('.approvedrejacted').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,      // Close the picker after selection
        todayHighlight: true  // Highlight today's date
    });
    

    
});


    $(document).on("change",".Gm_Decision",function()
    {
        if($(this).val() != "Rejected")
        {
            $(".Rejection_reason").hide();
        }
        else{
            $(".Rejection_reason").show();
        }

    });
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
  
</script>
@endsection