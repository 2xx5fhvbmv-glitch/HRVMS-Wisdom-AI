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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Performance</span>
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

            <div>
                <div class="card">
                <form id="MonthlyCheckinForm" data-parsley-validate>
                     @csrf
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-4 col-md-5">
                                <div class="bg-themeGrayLight sumDisEmp-block">
                                    <div class="card-title mb-md-3">
                                        <h3>Select Employee</h3>
                                    </div>
                                    <input type="search" class="form-control mb-3 search" placeholder="Search">
                                    <div class="overflow-auto pe-1 AppendEmp_id">
                                        @if($Employee->isNotEmpty())
                                        @foreach($Employee as $e)
                                        <div class="d-flex  Employee" data-id="{{$e->emp_id}}" 
                                            data-profile="{{$e->profileImg}}"
                                            data-position="{{$e->PositionName}}"
                                            data-position="{{$e->PositionName}}"
                                            data-first_name="{{$e->first_name}}"
                                            data-last_name="{{$e->last_name}}"
                                            data-Emp_id="{{$e->Emp_id}}"
                                            >
                                            <div class="img-circle userImg-block "><img src="{{$e->profileImg}}"
                                                    alt="user">
                                            </div>
                                            <div>
                                                <h6>{{$e->first_name}}  {{$e->last_name}}</h6>
                                                <p>{{$e->PositionName}}</p>
                                            </div>
                                        </div>
                                        @endforeach
                                        @endif
                                    
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-8 col-md-7 sumDisEmpDetail-block">
                                <div class="d-flex align-items-center mb-md-4 pb-md-1 mb-3" id="Append_id">
                                
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mt-3">
                                        <label for="date_discussion" class="form-label">DATE OF DISCUSSION <span class="text-danger">*</span></label>
                                        <input type="text" id="date_discussion"
                                            required
                                            data-parsley-required-message="Date of discussion is required"
                                            name="date_discussion" class="form-control datepicker" placeholder="Select Date">
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label for="Time" class="form-label">START TIME <span class="text-danger">*</span></label>
                                        <input type="time" id="Time" name="start_time" 
                                            required
                                            data-parsley-required-message="Time is required"
                                            class="form-control" placeholder="Select Start Time">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label for="Time" class="form-label">END TIME <span class="text-danger">*</span></label>
                                        <input type="time" id="Time" name="end_time" 
                                            required
                                            data-parsley-required-message="Time is required"
                                            class="form-control" placeholder="Select End Time">
                                    </div>
                                    
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">Meeting Place <span class="text-danger">*</span></label>
                                        <input type="text" name="Meeting_Place"
                                            required
                                            data-parsley-required-message="Meeting Place is required"
                                            class="form-control" placeholder="Enter Meeting Place">
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">AREA OF DISCUSSION <span class="text-danger">*</span></label>
                                        <input type="text" name="Area_of_Discussion" 
                                            required
                                            data-parsley-required-message="Area Of Discussion is required"
                                            class="form-control" placeholder="Enter Area">
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">AREA OF IMPROVEMENT <span class="text-danger">*</span></label>
                                        <input type="text" name="Area_of_Improvement" 
                                            required
                                            data-parsley-required-message="Area Of Improvement is required"
                                            class="form-control" placeholder="Enter Improvement Area">
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label">TIME LINE <span class="text-danger">*</span></label>
                                        <input type="text" name="Time_Line" 
                                            required
                                            data-parsley-required-message="Time Line is required"
                                            class="form-control" placeholder="Enter Timeline">
                                    </div>

                                    <div class="mb-md-4 mt-3 mb-3 pb-2">
                                        <label for="summary" class="form-label">COMMENT <span class="text-danger">*</span></label>
                                        <textarea id="summary" name="comment" class="form-control" rows="4" placeholder="Type Here..."></textarea>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-2 mt-4 mb-3 pb-2">
                                            <a href="javascript:void(0)" class="AddTraning btn btn-themeSkyblue btn-sm">Add Training</a>
                                        </div>


                                        <div class="col-md-5 checkTraning d-none">
                                                <label for="select_training" class="form-label">SELECT TRAINING</label>
                                                <select class="form-select" id="select_training" name="tranining_id">
                                                    <option value="">Select Training</option>
                                                    @if(isset($learningProgram) && $learningProgram->isNotempty())
                                                        @foreach($learningProgram as $l)
                                                            <option value="{{$l->id}}">{{$l->name}}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                           
                                        </div>
                                        <div class="col-md-5 checkTraning d-none">
                                           
                                                <label for="select_learning_manager_id" class="form-label">SELECT LEARNING MANAGER</label>
                                                <select class="form-select" id="select_learning_manager_id" name="learning_manager_id">
                                                    <option value="">Select Learning Manager</option>
                                                    @if(isset($learningManagers) && $learningManagers->isNotempty())
                                                        @foreach($learningManagers as $l)
                                                            <option value="{{$l->id}}">{{$l->resortAdmin->first_name}}  {{$l->resortAdmin->last_name}}  ({{$l->position->position_title}})</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                           
                                        </div>
                                    </div>
                                </div>
                            
                            </div>
                        </div>
                        <input type="hidden" name="emp_id" id="emp_hidden_id">

                        <div class="card-footer text-end">
                            <button type='submit' class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){
    $('#MonthlyCheckinForm').parsley();

    $("#select_training").select2({
        placeholder:"select Tranining"
    });
    $("#date_discussion").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });
})
$(document).on("click",".Employee",function(){
    $('.Employee').removeClass('select'); // clear active from all
    $(this).addClass('select');  

    var id  = $(this).data('id');
    var profile =   $(this).data('profile');
    var position =   $(this).data('position');
    var first_name =   $(this).data('first_name');
    var last_name =   $(this).data('last_name');
    var Emp_id =   $(this).data('emp_id');
    $("#emp_hidden_id").val(Emp_id);
    $("#Append_id").html(`<div class="img-circle userImg-block me-lg-4 me-md-3 me-2">
                            <img id="profileImg"   src="${profile}" alt="user">
                        </div>
                        <div>
                            <h4 class="mb-md-2 mb-1">${first_name} ${last_name}<span class="badge badge-themeNew">${Emp_id}</span>
                            </h4>
                            <p>${position}</p>
                        </div>`);
});
$(document).on("click",".AddTraning",function(){
    $(".checkTraning").toggleClass("d-none");
});

$(document).on("keyup",".search",function(){

    $.ajax({
                url: "{{ route('Performance.GetEmployeeDetailsMonthlyChecking') }}", 
                type: 'get',
                data: {"_token":"{{csrf_token()}}","search":$(this).val()},
                success: function(response) {
                    if (response.success)
                    {
                        $(".AppendEmp_id").html(response.data);
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


$(document).on("submit","#MonthlyCheckinForm",function(e)
{
    e.preventDefault();
    let Emp = $("#emp_hidden_id").val();

    if(!isNaN(Emp))
    {
     
        toastr.error("Please Select Employee", "Error",
        {
            positionClass: 'toast-bottom-right'
        });
        return false;
    }


    e.preventDefault();
    let form = $(this);
        if (form.parsley().isValid()) 
        {
            let formData = new FormData(this);
            $.ajax({
                url: "{{ route('Performance.MonltyCheckInStore') }}", 
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
                        window.location.href = response.route;
                        $('.select2t-none').val('').trigger('change');
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
        }
});

</script>
@endsection
