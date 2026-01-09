@extends('shopkeeper.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Profile</span>
                        <h1>Profile Details</h1>
                    </div>
                </div>
            </div>
        </div>
        <form method="post" name="ProfileUpdate" id="ProfileUpdate" enctype="multipart/form-data" >
            @csrf
            <div class="card">
                <div class="card-title">
                    <h3>Update Profile </h3>
                </div>

                <div class="talentAc-main">
                    <div class="talentAc-block">
                        <div class="mb-2">
                            <h6>Personal Details</h6>
                        </div>
                        <input type="hidden" name="id" value="{{ old('id',isset($profile->id) ? $profile->id : '') }}">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name',isset($profile->name) ? $profile->name : '') }}" placeholder="Full Name"/>
                            </div>
                           
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Contact No  </label>
                                <input type="text" id="contact_no" name="contact_no" class="form-control" value="{{ old('contact_no',isset($profile->contact_no) ? $profile->contact_no : '') }}"/>
                            </div>

                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Email Id  </label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email',isset($profile->email) ? $profile->email : '') }}"/>
                            </div>
                           
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">
                                    Profile Image
                                </label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                        <input type="file" name="profile_photo" id="profile_photo" >
                                    </div>
                                    <div class="uploadFile-text">

                                        <img id="profileimg" class="logo-img" width="100px" src="{{ Auth::guard('shopkeeper')->user()->profile_photo ? asset(config('settings.ShopkeeperProfile_folder') . '/' . Auth::guard('shopkeeper')->user()->profile_photo) : asset('resorts_assets/images/persone.svg') }}" 
                                        >
                                        
                                    </div>
                                </div>
                            </div>
                           
                        </div>
                    </div>

                    <div class="talentAc-block">
                        <div class="mb-2">
                            <h6>Change Password</h6>
                        </div>
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Password  </label>
                                <div class="d-flex">
                                    <input type="password" id="password" name="password" class="form-control" value="{{ old('password')}}" />
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" fdprocessedid="4qeof">
                                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Confirm passwords </label>
                                <div class="d-flex">
                                    <input type="password" id="confirmpassword" name="confirmpassword" class="form-control" value="{{ old('confirmpassword') }}"/>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" fdprocessedid="4qeof">
                                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="error-confirm" class="error-message"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-none d-xl-block" style="height: 126px;"></div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                    <a class="btn btn-sm btn-themeGray me-2" href="{{url()->previous()}}"  class="btn btn-default resetForm">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endSection

@section('import-css')
@endsection

@section('import-scripts')

<script>
$(document).ready(function(){

    if (typeof $.validator !== 'undefined' && $.validator.addMethod) {
        $.validator.addMethod("lettersAndSpaces", function(value, element) {
            return this.optional(element) || /^[A-Za-z\s]+$/.test(value);
        }, "First Name can only contain letters and spaces.");

            $('#ProfileUpdate').validate({
                rules: {
                    full_name: {
                        required: true,
                        lettersAndSpaces: true
                    },
                    contact_no: {
                        required: true,
                        number: true,
                        maxlength: 12,
                    },
                    email: {
                        required: true,
                        email: true,
                    },
                    password: {
                        minlength: 6,
                        maxlength: 20,
                    },
                    confirmpassword: {
                        equalTo: "#password",
                    }
                },
                messages: {
                    full_name: {
                        required: "Please enter Your First Name.",
                        lettersAndSpaces: "Full name can only contain letters and spaces.",
                    },
                    contact_no: {
                        required: "Please enter Your Contact Number.",
                        number: "Please enter a valid Contact number.",
                        maxlength: "Your phone number must be no more than 12 digits.",
                    },
                    email: {
                        required: "Please enter Your Email.",
                        email: "Please enter a valid email address.",
                    },
                    password: {
                        minlength: "Your password must be at least 6 characters long.",
                        maxlength: "Your password must be no more than 20 characters long.",
                    },
                    confirmpassword: {
                        equalTo: "Passwords do not match.",
                    },
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") === "confirmpassword") {
                        error.appendTo("#error-confirm");
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    // Creating a FormData object to capture the form with files
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('shopkeeper.update.profile') }}", // Ensure route is correct
                        type: "POST",
                        data: formData, // Use FormData instead of .serialize()
                        contentType: false, // Prevent jQuery from processing the data
                        processData: false, // Prevent jQuery from automatically transforming the data into a query string
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                toastr.error(response.msg, "Error", {
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
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });
                }
            });

    } else {
        console.error("jQuery Validation plugin is not loaded.");
    }
    $('#togglePassword').on('click', function() {
        // Get the input field
        var inputField = $('#password');

        // Toggle the type attribute between password and text
        if (inputField.attr('type') === 'password') {
            inputField.attr('type', 'text');
            // Change the icon to 'eye'
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            inputField.attr('type', 'password');
            // Change the icon back to 'eye-slash'
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    $('#toggleConfirmPassword').on('click', function() {
        // Get the input field
        var inputField = $('#confirmpassword');

        // Toggle the type attribute between password and text
        if (inputField.attr('type') === 'password') {
            inputField.attr('type', 'text');
            // Change the icon to 'eye'
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            inputField.attr('type', 'password');
            // Change the icon back to 'eye-slash'
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    $("#profile_photo").on("change", function(event) {
        var file = event.target.files[0];
        if (file) {
            var output = document.getElementById("profileimg");
            output.src = URL.createObjectURL(file);
            output.onload = function() {
                URL.revokeObjectURL(output.src);
            };
        }
    });
   
});
</script>
@endsection


