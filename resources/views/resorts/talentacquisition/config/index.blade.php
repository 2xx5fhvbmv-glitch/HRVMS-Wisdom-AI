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
                            <span>Talent Acquisition</span>
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
                <div class="row g-4">

                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-3 align-items-center justify-content-between">
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h3>Add job description</h3>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-sm-end align-items-center">
                                            <a href="{{ route('resort.ta.jobdescription.index')}}" class="a-link @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.jobdescription.index',config('settings.resort_permissions.view')) == false) d-none @endif">View All Job Description</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <div class="mb-2">
                                    <select class="form-select  ResortDivision" id="ResortDivision" name='ResortDivision' aria-label="Default select example">
                                        <option  > </option>
                                        @if($resort_divisions->isNotEmpty())
                                            @foreach($resort_divisions as $resort_division)
                                                <option value="{{ $resort_division->id }}">{{ $resort_division->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <select class="form-select select2 Department" aria-label="Default select example">
                                        <option ></option>

                                    </select>
                                </div>
                                <div class="mb-2">
                                    <select class="form-select  Section" aria-label="Default select example">
                                        <option ></option>

                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select select2 Position" aria-label="Default select example">
                                        <option ></option>

                                    </select>
                                </div>
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-auto">
                                        {{-- <div class="uploadFile-block">
                                            <div class="uploadFile-btn me-0">
                                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                                <input type="file" id="uploadFile">
                                            </div>
                                        </div> --}}
                                    </div>
                                    <div class="col-auto">
                                        {{-- <span>OR</span> --}}
                                    </div>
                                    <div class="jobdiscriptionForm" >
                                        <a href="#jobDesEdit-modal" disabled data-bs-toggle="modal" class=" jobDesEdit_Button btn btn-themeSkyblue btn-sm @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.jobdescription.index',config('settings.resort_permissions.create')) == false) d-none @endif">
                                            Create Job Description
                                        </a>
                                        @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.jobdescription.index',config('settings.resort_permissions.create')) == false)
                                            <span class="req_span">You do not have permission to create job descriptions.</span>
                                        @endif
                                    </div>
                                </div>

                            <div class="card-footer text-end">
                                {{-- <button  type class="btn btn-themeBlue btn-sm">Submit</button> --}}
                            </div>


                        </div>
                        <div class="card mb-30 @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                            <div class="card-title">
                                <div class="row g-3 align-items-center justify-content-between">
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h3>Interview Assessment</h3>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-sm-end align-items-center">
                                            <a href="{{route('interview-assessment.index')}}" class="a-link">View All </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div><a href="{{route('interview-assessment.create')}}" class="btn btn-themeSkyblue btn-sm @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.create')) == false) d-none @endif">Build Interview Assessment Form</a>
                            </div>

                        </div>

                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-3 align-items-center justify-content-between">
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h3> Final Approval</h3>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div>
                                <form id="TaFinalApprovalForm">
                                    @csrf
                                    <div class="row g-3 align-items-center  ">
                                        <div class="col-xl-9 col-lg-8 col-sm-9">
                                            <?php $position_rank = config('settings.final_rank'); ?>
                                            <select class="form-control" id="FinalApproval" name="FinalApproval">
                                                <option  ></option>
                                                @if(!empty($position_rank))
                                                    @foreach ($position_rank as  $key=>$position)
                                                        <option value="{{$key}}" {{ (isset($configset->FinalApproval ) && $configset->FinalApproval ==$key ?'selected': '') }}>
                                                            {{ $position }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-sm-auto">
                                            <button type="submit" class="btn btn-themeSkyblue btn-sm">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-title">
                                <div class="row g-3 align-items-center justify-content-between">
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h3>Terms And conditions</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form id="terms_conditions_form">
                                @csrf
                                <div class="row align-items-top  g-3 mb-3">
                                    <div class="col-lg-12">
                                        <label>Terms and Conditions</label>
                                        <div class="ticketBook-form">
                                            <textarea class="form-control cke_notifications_area" rows="7" name="terms_and_condition" id="terms_and_condition">{{ $termsAndCondition->terms_and_condition ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-themeSkyblue AddAgent mt-3">Save</button>
                                    </div>
                                </div>
                            </form>

                        </div>

                    </div>

                    <div class="col-lg-6 ">
                        <div class="row g-4">
                            <div class="col-xxl-8 col-lg-7 col-md-8 col-sm-7">
                                <form id="AdTemplete" enctype="multipart/form-data">
                                    @csrf
                                    <div class="card">
                                        <div class="card-title">
                                            <div class="row g-3 align-items-center justify-content-between">
                                                <div class="col-auto">
                                                    <div class="d-flex justify-content-start align-items-center">
                                                        <h3>Job Ad Template</h3>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="d-flex justify-content-sm-end align-items-center">
                                                        <a href="{{ route('resort.ta.jobadvertisment.index') }}" class="a-link">View All Templates</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="uploadFileNew-block mb-md-3 mb-2" id="dropzone">
                                            <img src="{{ URL::asset('resorts_assets/images/upload.svg') }}" alt="icon">
                                            <h5>Upload The Job Ad Template</h5>
                                            <p>Browse or Drag the file here</p>
                                            <input type="file" id="file" name="Jobadvimg" hidden>
                                            <div id="fileName" class="mt-2 text-primary"></div>
                                        </div>
                                        @if(isset($configset) && !empty($configset->Jobadvimg))
                                        <div class="mb-2 text-center" id="currentTemplate">
                                            <p class="mb-1 text-muted"><small>Current Template:</small></p>
                                            <img src="{{ URL::asset(config('settings.Resort_JobAdvertisement').'/'.$configset->Resort_id.'/'.$configset->Jobadvimg) }}" alt="Job Ad Template" class="img-fluid rounded" style="max-height: 200px;">
                                        </div>
                                        @endif
                                        <div class="card-footer text-end">
                                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                            <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-5">
                                <div class="card h-100 @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.Questionnaire',config('settings.resort_permissions.view')) ==false) d-none @endif">
                                    <div class="addQue-block ">
                                        <div>
                                            <img src="{{ URL::asset('resorts_assets/images/help-circle.svg')}}" alt="icon">
                                            <h5>Add Questionnaire For Interview</h5>
                                            <a href="{{ route('resort.ta.add.Questionnaire') }}" class="btn btn-themeSkyblue btn-sm">Add Questions</a>
                                        </div>
                                        <a href="{{ route('resort.ta.Questionnaire') }}" class="a-link">View All Questions</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 ">
                                <div class="card">
                                    <div class="card-title">
                                        <div class="row g-3 align-items-center justify-content-between">
                                            <div class="col-auto">
                                                <div class="d-flex justify-content-start align-items-center">
                                                    <h3>Ticket Booking Agents</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="ConfigAgent">
                                        @csrf
                                        <div class="row align-items-top  g-3 mb-3">
                                            <div class="col-lg-4">
                                                <label for="name" class="form-label">Agent Name</label>
                                                <div class="ticketBook-form ">
                                                    <input type="text" name="name" class="form-control" placeholder="Add Name" />
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label">Agent Email</label>
                                                <div class="ticketBook-form ">
                                                    <input type="email" name="agents_email" class="form-control" placeholder="Add Email Address" />
                                                </div>
                                            </div>
                                            <div class="col-lg-4">


                                                <button  class="btn btn-themeSkyblue AddAgent mt-3">Submit</button>

                                            </div>

                                        </div>
                                    </form>
                                    <div class="row g-1 ticketBook-list">
                                        <div class="col-12">
                                            <table class="table table-sm AgentTicket"  id="AgentTicket">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Email</th>
                                                        <th scope="col">Action</th>

                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>

                                    </div>


                                </div>
                            </div>
                            <div class="col-12 ">
                                <div class="card">
                                    <div class="card-title">
                                        <div class="row g-3 align-items-center justify-content-between">
                                            <div class="col-auto">
                                                <div class="d-flex justify-content-start align-items-center">
                                                    <h3>Hiring Sources</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="HiringSource">
                                        @csrf
                                        <div class="row align-items-top  g-3 mb-3">
                                            <div class="col-lg-4">
                                                <label class="form-label">Source Name</label>
                                                <div class="hiringsource-form ">
                                                    <input type="text" name="source_name" class="form-control" placeholder="Add Source Name" />
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label">Select Color</label>
                                                <div class="inputCustom-color"> Select Color
                                                    <input type="color" name="color" id="color" value="#A264F7" style="top:50%"/>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <button class="btn btn-themeSkyblue AddSource mt-3">Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="row g-1 hiringsource-list">
                                        <div class="col-12">
                                            <table class="table table-sm hiringsource"  id="hiringsource">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Source Name</th>
                                                        <th scope="col">Colour</th>
                                                        <th scope="col">Action</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="jobDesEdit-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="jobDesEditForm">

                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Edit Job Description </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="Division_id" id="FormResortDivision">

                        <input type="hidden" name="Department_id"  id="FormResortDepartment">
                        <input type="hidden" name="Section_id" id="FormResortSeciton">
                        <input type="hidden" name="Position_id" id="FormResortPosition">
                        <textarea class="form-control cke_notifications_area" rows="7" name="jobdescription" id="editor">

                        </textarea>

                    </div>
                    <div class="modal-footer">
                        {{-- <a href="#" class="btn btn-themeBlue">Save As Draft</a>
                        <a href="#" class="a-link">View</a> --}}
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/additional-methods.min.js" integrity="sha512-owaCKNpctt4R4oShUTTraMPFKQWG9UdWTtG6GRzBjFV4VypcFi6+M3yc4Jk85s3ioQmkYWJbUl1b2b2r41RTjA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script type="text/javascript">
$(document).ready(function() 
{
    AgentTicket();
    HiringSourceTable();
        CKEDITOR.replace('terms_and_condition');
        $('#jobDesEdit-modal').on('shown.bs.modal', function () {
            if (CKEDITOR.instances['editor']) {
                CKEDITOR.instances['editor'].destroy(); // Destroy existing CKEditor instance if it exists
            }
            CKEDITOR.replace('editor'); // Re-initialize CKEditor
        });

        $(".Position , .Section").on("change",function(){
            $(".jobDesEdit_Button").attr('disabled', false);
            $("#FormResortDivision").val($('#ResortDivision').val());
            $("#FormResortDepartment").val($('.Department').val());
            $("#FormResortSeciton").val($('.Section').val());
            $("#FormResortPosition").val($('.Position').val());
        });

        $('#jobDesEditForm').validate({
            rules: {
                jobdesription:{
                    required: true,
                }
            },
            messages: {
                jobdesription:{
                    required: "Please Enter Job desription.",
                }
            },
            submitHandler: function(form)
            {
                var $submitBtn = $('#jobDesEditForm button[type="submit"]');
                if($submitBtn.prop('disabled')) return false;
                $submitBtn.prop('disabled', true).text('Submitting...');

                const content = CKEDITOR.instances['editor'].getData(); // Use CKEDITOR to get content

                let Division_id = $("#FormResortDivision").val();
                let Department_id =  $("#FormResortDepartment").val();
                let Section_id =  $("#FormResortSeciton").val();
                let Position_id =  $("#FormResortPosition").val();

                $.ajax({
                    url: "{{ route('resort.ta.jobdescription.store') }}",
                    type: "POST",
                    data: {
                        "Division_id": Division_id,
                        "Department_id": Department_id,
                        "Section_id": Section_id,
                        "Position_id": Position_id,
                        "jobdescription": content,
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            // Close modal after success
                            $('#jobDesEdit-modal').modal('hide');
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
                        $submitBtn.prop('disabled', false).text('Submit');
                        // If you're using any rich text editor, reset it here
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['editor']) {
                            CKEDITOR.instances['editor'].setData('');
                        }
                    }
                });



            }
        });

        $("#ResortDivision").select2({placeholder: "Select Division"});
        $(".Department").select2({placeholder: "Select Department"});
        $(".Section").select2({placeholder: "select Section"});
        $(".Position").select2({placeholder: "select Position"});

        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('center-id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed)
                {

                    $.ajax({
                        type: "delete",
                        url: "{{ route('resort.ta.destroyAgentList','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            AgentTicket();
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on('click', '.delete-source-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('source-id');


            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed)
                {
                    $.ajax({
                        type: "delete",
                        url: "{{ route('resort.ta.delete.source','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            HiringSourceTable();
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on('change', '.ResortDivision', function() {
            $.ajax({
                url: "{{ route('resort.get.ResortDivision') }}",
                type: "post",
                data: {
                    division_id: $(this).val(),
                },
                success: function(data) {

                    // Clear the dropdown and add a placeholder option
                    $(".Department").html('<option value="">Select Department</option>');
                    if(data.success == true) {
                        let string='<option></option>';
                        // Append new options
                        $.each(data.data, function(key, value) {

                            string+='<option value="'+value.id+'">'+value.name+'</option>';
                        });

                        $(".Department").html(string);

                    } else {
                        let string='<option></option>';
                    }
                },
                error: function(response) {
                    toastr.error("Department Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $(document).on('change', '.Department', function() {
            var deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "post",
                data: {
                    deptId: deptId
                },
                success: function(d) {
                    // Clear the dropdown and add a placeholder option


                    if(d.success == true) {

                        let string='<option></option>';
                        $.each(d.data.ResortPosition, function(key, value) {
                            string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                        });
                        $(".Position").html(string);

                        let string1='<option></option>';
                        $.each(d.data.ResortSection, function(key, value) {
                            string1+='<option value="'+value.id+'">'+value.name+'</option>';
                        });
                        $(".Section").html(string1);

                    }
                },
                error: function(response) {
                    toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $.validator.addMethod("noSpecialChars", function(value, element) {
            return this.optional(element) || /^[a-zA-Z\s]*$/.test(value);
        }, "Name should only contain letters and spaces.");

        $('#ConfigAgent').validate({
                rules: {
                    agents_email: {
                        required: true,
                        email: true,
                        maxlength: 100,
                        minlength: 5,

                    },
                    name:{
                        required: true,
                        maxlength: 50,
                        minlength: 1,
                        noSpecialChars: true

                    }

                },
                messages: {
                    agents_email: {
                        required: "Please enter email address.",
                        email: "Please enter a valid email address.",
                        maxlength: "Email cannot be longer than 100 characters",
                        minlength: "Email must be at least 5 characters long"
                    },
                    name :{
                        required: "Please Name .",
                    }
                },
            submitHandler: function(form)
            {
                var $btn = $(form).find('button[type="submit"]');
                if($btn.prop('disabled')) return false;
                $btn.prop('disabled', true).text('Saving...');

                var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                var email = $(form).find('[name="agents_email"]').val();

                if (!emailRegex.test(email)) {
                    toastr.error("Invalid email format", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    $btn.prop('disabled', false).text('Save');
                    return false;
                }
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.ta.add.Agent') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            AgentTicket();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        console.log(errors);
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, {  positionClass: 'toast-bottom-right' });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Save');
                    }
                });
            }
        });

    $.validator.addMethod("fileSize", function (value, element, param) {
        if (element.files && element.files.length > 0) {
            var fileSize = element.files[0].size;
            return fileSize <= param;
        }
        return true;
    }, "The file size must not exceed 2MB.");

    function AgentTicket()
    {
        
        if ($.fn.DataTable.isDataTable('#AgentTicket')) 
        {
            $('#AgentTicket').DataTable().clear().destroy();
        }
            $('#AgentTicket tbody').empty();
        var AgentTicket = $('#AgentTicket').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("resort.ta.getResortAgentTicketList") }}',
                type: 'GET',
                data: function(d) {
                    d.flag = 'Leave'; // Add your custom parameter here
                }
            },
            columns: [
                { data: 'name', name: 'name', className: 'text-nowrap' },
                { data: 'Email', name: 'Email', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false }
            ]
        });
    }

    function HiringSourceTable()
    {
        if ($.fn.DataTable.isDataTable('#hiringsource'))
        {
            $('#hiringsource').DataTable().clear().destroy();
        }
        $('#hiringsource tbody').empty();
        $('#hiringsource').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("resort.ta.get.source") }}',
                type: 'GET',
            },
            columns: [
                { data: 'source_name', name: 'source_name', className: 'text-nowrap' },
                { data: 'colour', name: 'colour', className: 'text-nowrap',
                    render: function(data) {
                        if(data) {
                            return '<span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:'+data+'"></span> '+data;
                        }
                        return '-';
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
    }

    $('#HiringSource').validate({
        rules: {
            source_name: { required: true, maxlength: 100 },
            color: { required: true }
        },
        messages: {
            source_name: { required: "Please enter source name." },
            color: { required: "Please select a color." }
        },
        submitHandler: function(form) {
            var $btn = $(form).find('button');
            if($btn.prop('disabled')) return false;
            $btn.prop('disabled', true).text('Saving...');

            var formData = new FormData(form);
            $.ajax({
                url: "{{ route('resort.ta.add.source') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success) {
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();
                        $('#color').val('#A264F7');
                        HiringSourceTable();
                    } else {
                        toastr.error(response.msg || response.message || "Failed to add.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    var errs = '';
                    if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errs += value[0] + '<br>';
                        });
                    } else {
                        errs = 'An unexpected error occurred.';
                    }
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Submit');
                }
            });
        }
    });

    $(document).on("click", "#AgentTicket .edit-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        // Find the parent row
        var $row = $(this).closest("tr");

        // Extract division ID
        var agentId = $(this).data('agent-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentEmail = $row.find("td:nth-child(2)").text().trim();

        var editRowHtml = `<form class="inline-agent-form" data-parsley-validate>
                            <tr>
                                <td class="py-1">
                                    <div class="form-group">
                                        <input type="text" class="form-control name" name="name" required />
                                    </div>
                                </td>
                                <td class="py-1">
                                    <div class="form-group">
                                        <input type="email" class="form-control email" name="agents_email" required data-parsley-type="email" />
                                    </div>
                                </td>
                                <td class="py-1">
                                    <a href="#" class="btn btn-theme update-row-btn_agent" data-agent-id="${agentId}">Submit</a>
                                </td>
                            </tr>
                        </form>
            `;

        // Replace row content with editable form
        $row.html(editRowHtml);
    });

    $(document).on("click", "#AgentTicket .update-row-btn_agent", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var $form = $(this).closest("form");

        if (!$form.parsley().validate()) {
            return false; // Prevent AJAX if validation fails
        }

        var agentId = $(this).data('agent-id');
        var updatedName = $row.find("input.name").val();
        var updatedEmail = $row.find("input.email").val();

        $.ajax({
            url: "{{ route('resort.ta.inlineUpdateAgent', '') }}/" + agentId,
            type: "PUT",
            data: {
                name: updatedName,
                agents_email: updatedEmail,
            },
            success: function (response) {
                if (response.success == true) {
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedEmail}</td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-agent-id="${agentId}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-agent-id="${agentId}">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                </a>
                            </div>
                        </td>
                    `;
                    $row.html(updatedRowHtml);
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                let errorMessage = '';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, error) {
                        errorMessage += error + "<br>";
                    });
                } else {
                    errorMessage = "An error occurred while updating.";
                }

                toastr.error(errorMessage, "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $('#terms_conditions_form').on('submit', function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        if($btn.prop('disabled')) return false;

        var content = CKEDITOR.instances['terms_and_condition'].getData();
        if(!content || content.trim() === '') {
            toastr.error("Please enter Terms and Conditions.", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: "{{ route('resort.ta.termscondition.storeOrUpdate') }}",
            type: "POST",
            data: {
                terms_and_condition: content,
                _token: "{{ csrf_token() }}"
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.message || "Failed to save.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(xhr) {
                var errs = '';
                if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        errs += value[0] + '<br>';
                    });
                } else if(xhr.responseJSON && xhr.responseJSON.message) {
                    errs = xhr.responseJSON.message;
                } else {
                    errs = 'An unexpected error occurred. Please try again.';
                }
                toastr.error(errs, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save');
            }
        });
    });

    $('#TaFinalApprovalForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        if($btn.prop('disabled')) return false;
        $btn.prop('disabled', true).text('Submitting...');

        let formData = new FormData(this);
        $.ajax({
            url: "{{ route('ta.finalApproval.save') }}",
            method: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'Final approval saved successfully.', "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function (xhr) {
                let res = xhr.responseJSON;
                if (res && res.message) {
                    toastr.error(res.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("Error submitting the form.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Submit');
            }
        });
    });
 });

document.addEventListener('DOMContentLoaded', function () {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file');
    const fileNameDisplay = document.getElementById('fileName');

    // Highlight drop area
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('drag-over');
    });

    dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('drag-over');
    });

    // Handle file drop
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files; // for form submission
            fileNameDisplay.textContent = `📎 ${files[0].name}`;
        }
    });

    // Show file name on manual selection
    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = `📎 ${fileInput.files[0].name}`;
        }
    });

    // Optional: click area triggers file input
    dropzone.addEventListener('click', () => fileInput.click());
});

$('#AdTemplete').on('submit', function(e) {
    e.preventDefault();
    var $submitBtn = $(this).find('button[type="submit"]');
    if($submitBtn.prop('disabled')) return false;

    var fileInput = document.getElementById('file');
    if(!fileInput.files || fileInput.files.length === 0) {
        toastr.error("Please select an image to upload.", "Error", {
            positionClass: 'toast-bottom-right'
        });
        return false;
    }

    $submitBtn.prop('disabled', true).text('Uploading...');

    var formData = new FormData(this);
    $.ajax({
        url: "{{ route('resort.ta.jobadvertisment.upload') }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if(response.success) {
                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
                document.getElementById('fileName').textContent = '';
                fileInput.value = '';
            } else {
                toastr.error(response.message || "Upload failed.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        },
        error: function(xhr) {
            var errs = '';
            if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function(key, value) {
                    errs += value[0] + '<br>';
                });
            } else {
                errs = 'An unexpected error occurred. Please try again.';
            }
            toastr.error(errs, "Error", {
                positionClass: 'toast-bottom-right'
            });
        },
        complete: function() {
            $submitBtn.prop('disabled', false).text('Submit');
        }
    });
});
</script>

@endsection
