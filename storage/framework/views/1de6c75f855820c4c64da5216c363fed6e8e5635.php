
<?php $__env->startSection('page_tab_title', $page_title); ?>

<?php $__env->startSection('content'); ?>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Profile</span>
                        <h1><?php echo e($page_title); ?></h1>
                    </div>
                </div>
            </div>
        </div>
        <form method="post" name="ProfileUpdate" id="ProfileUpdate" enctype="multipart/form-data" >
            <?php echo csrf_field(); ?>
            <div class="card mb-30">
                <div class="card-title">
                    <h3>Update Profile </h3>
                </div>

                <div class="talentAc-main">

                    <div class="talentAc-block">
                        <div class="row g-md-4 g-3">
                            <?php if($profile->type == 'sub'): ?>
                                <?php if($profile->GetEmployee): ?>
                                <div class="col-md-12">
                                    <label class="form-label"> Department Name : <?php   echo $profile->GetEmployee->department->name ?> </label><br/>
                                    <label class="form-label"> Position Name :   <?php   echo $profile->GetEmployee->position->position_title ?> </label><br/>
                                    <label class="form-label"> Employee Id :     <?php   echo $profile->GetEmployee->Emp_id ?> </label>
                                </div>

                                <?php endif; ?>
                            <?php else: ?>
                                <?php $type="Resort Admin"; ?>

                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="talentAc-block">
                        <div class="mb-2">
                            <h6>Personal Details</h6>
                        </div>
                        <input type="hidden" name="id" value="<?php echo e(old('id',isset($profile->id) ? $profile->id : '')); ?>">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">First Name <span class="req_span">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo e(old('first_name',isset($profile->first_name) ? $profile->first_name : '')); ?>" placeholder="First Name"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" value="<?php echo e(old('middle_name',isset($profile->middle_name) ? $profile->middle_name : '')); ?>" placeholder="Middle Name"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Last Name <span class="req_span">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo e(old('last_name',isset($profile->last_name) ? $profile->last_name : '')); ?>" placeholder="Last Name"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Gender <span class="req_span">*</span> </label>
                                <input type="text" readonly id="gender" name="gender" class="form-control" value="<?php echo e(old('gender',isset($profile->gender) ? $profile->gender : '')); ?>" required/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Phone Number  <span class="req_span">*</span></label>
                                <input type="text" id="personal_phone" name="personal_phone" class="form-control" value="<?php echo e(old('personal_phone',isset($profile->personal_phone) ? $profile->personal_phone : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Email Id <span class="req_span">*</span> </label>
                                <input type="text" id="email" name="email" class="form-control" value="<?php echo e(old('email',isset($profile->email) ? $profile->email : '')); ?>" readonly/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Role </label>
                                <?php if($profile->type == 'sub'): ?>
                                    <?php if($profile->GetEmployee): ?>
                                    <?php $type=(in_array($profile->GetEmployee->rank,['HOD','HR']))  ?  $profile->GetEmployee->rank : $type="Employee"; ?>
                                    <?php else: ?>
                                        <?php $type="Employee";?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php $type="Resort Admin"; ?>

                                <?php endif; ?>
                                <input type="text" readonly id="type" name="type" class="form-control" value="<?php echo e(old('type',$type)); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">
                                    <?php if($profile->type == 'sub'): ?> Profile <?php else: ?> Brand  <?php endif; ?> Image
                                </label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                        <input type="file" name="profile_picture" id="profile_picture" >
                                    </div>
                                    <div class="uploadFile-text">

                                        <img id="profileimg" class="logo-img" width="100px" src="<?php echo e(Common::getResortUserPicture($profile->id)); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="header_img">Select Authorized Signature Image </label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                        <input type="file" name="signature_img" id="signature_img"  >
                                    </div>
                                    <div class="uploadFile-text">
                                        <img id="signature_show_img" class="logo-img" width="100px" accept="image/*" src="<?php echo e(Common::getResortUserPicture($profile->id,1)); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="talentAc-block">
                        <div class="mb-2">
                            <h6>Address Details</h6>
                        </div>
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 1 <span class="req_span">*</span></label>
                                <input type="text" id="address_line_1" name="address_line_1" class="form-control" value="<?php echo e(old('address_line_1',isset($profile->address_line_1) ? $profile->address_line_1 : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 2 <span class="req_span">*</span></label>
                                <input type="text" id="address_line_2" name="address_line_2" class="form-control" value="<?php echo e(old('address_line_2',isset($profile->address_line_2) ? $profile->address_line_2 : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Country <span class="req_span">*</span></label>
                                <input type="text" id="country " name="country" class="form-control" placeholder="Please enter country " value="<?php echo e(old('country ',isset($profile->country ) ? $profile->country : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">ATOLL <span class="req_span">*</span></label>
                                <input type="text" id="state " name="state" class="form-control" placeholder="Please enter ATOLL " value="<?php echo e(old('ATOLL',isset($profile->state ) ? $profile->state : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">City <span class="req_span">*</span></label>
                                <input type="text" id="city" name="city" class="form-control" placeholder="Please enter City" value="<?php echo e(old('city',isset($profile->city) ? $profile->city : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Pincode <span class="req_span">*</span></label>
                                <input type="text" id="zip" required name="zip" class="form-control" placeholder="Please enter Pincode" value="<?php echo e(old('zip', isset($profile->zip) ? $profile->zip : '')); ?>"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-none d-xl-block" style="height: 126px;"></div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                    <a class="btn btn-sm btn-themeGray me-2" href="<?php echo e(url()->previous()); ?>"  class="btn btn-default resetForm">Back</a>
                </div>
            </div>
        </form>

        <form method="post" name="ChangePassword" id="ChangePassword" >
            <?php echo csrf_field(); ?>
            <div class="card">
                <div class="card-title">
                    <h3>Change Pasword </h3>
                </div>

                <div class="talentAc-block">
                    <div class="row g-md-4 g-3">
                        <!-- Old Password Field -->
                        <div class="col-md-4">
                            <label for="old_password" class="form-label">Old Password <span class="req_span">*</span></label>
                            <div class="input-password">
                                <input type="password" id="old_password" name="old_password" class="form-control" required>
                                <button type="button" id="toggleOldPassword">
                                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="error-old-password" class="error-message"></div>
                        </div>
                        <!-- Password Field -->
                        <div class="col-md-4">
                            <label for="password" class="form-label">New Password <span class="req_span">*</span></label>
                            <div class="input-password">
                                <input type="password" id="password" name="password" class="form-control" value="<?php echo e(old('password')); ?>">
                                <button  type="button" id="togglePassword">
                                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="col-md-4">
                            <label for="emp-grade-select" class="form-label">Confirm passwords <span class="req_span">*</span></label>
                            <div class="input-password">
                                <input type="password" id="confirmpassword" name="confirmpassword" class="form-control" value="<?php echo e(old('confirmpassword')); ?>"/>
                                <button  type="button" id="toggleConfirmPassword">
                                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="error-confirm" class="error-message"></div>
                        </div>
                    </div>
                </div>
                <div class="d-none d-xl-block" style="height: 126px;"></div>
                <div class="card-footer text-end">  
                    <button type="submit" class="btn btn-themeBlue btn-sm">Change Password</button>
                    <a class="btn btn-sm btn-themeGray me-2" href="<?php echo e(url()->previous()); ?>"  class="btn btn-default resetForm">Back</a>               
                </div>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>

<script>
$(document).ready(function(){

    if (typeof $.validator !== 'undefined' && $.validator.addMethod) {
        $.validator.addMethod("lettersAndSpaces", function(value, element) {
            return this.optional(element) || /^[A-Za-z\s]+$/.test(value);
        }, "First Name can only contain letters and spaces.");

        $.validator.addMethod("strongPassword", function(value, element) {
            return this.optional(element) || 
                /[A-Z]/.test(value) &&     // uppercase
                /[a-z]/.test(value) &&     // lowercase
                /\d/.test(value) &&        // digit
                /[!@#$%^&*(),.?":{}|<>]/.test(value) && // special character
                value.length >= 8 && value.length <= 16;
        }, "Password must be 8–16 characters and include uppercase, lowercase, number, and special character.");


        $.validator.addMethod("notCommonPassword", function(value) {
            const commonPasswords = ["password", "123456", "12345678", "qwerty", "password123"];
            return commonPasswords.indexOf(value.toLowerCase()) === -1;
        }, "This password is too common. Please choose another.");

        $.validator.setDefaults({
            errorPlacement: function(error, element) {
                error.addClass("mt-2");
                if (element.parent().hasClass('d-flex')) {
                    // Special handling for password fields that are in d-flex containers
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            }
        });

        $('#ProfileUpdate').validate({
            rules: {
                first_name: {
                    required: true,
                    lettersAndSpaces: true
                },
                last_name: {
                    required: true,
                    lettersAndSpaces: true
                },
                personal_phone: {
                    required: true,
                    number: true,
                    maxlength: 12,
                },
                email: {
                    required: true,
                    email: true,
                },
                address_line_1: {
                    required: true,
                },
                address_line_2: {
                    required: true,
                },
                country: {
                    required: true,
                },
                state: {
                    required: true,
                },
                city: {
                    required: true,
                },
                zip: {
                    required: true,
                    number: true,
                },
            },
            messages: {
                first_name: {
                    required: "Please enter your first name.",
                    lettersAndSpaces: "First name can only contain letters and spaces.",
                },
                last_name: {
                    required: "Please enter your last name.",
                    lettersAndSpaces: "Last name can only contain letters and spaces.",
                },
                personal_phone: {
                    required: "Please enter your phone number.",
                    number: "Please enter a valid phone number.",
                    maxlength: "Your phone number must be no more than 12 digits.",
                },
                email: {
                    required: "Please enter your email.",
                    email: "Please enter a valid email address.",
                },
                address_line_1: {
                    required: "Please enter your address.",
                },
                address_line_2: {
                    required: "Please enter your address.",
                },
                country: {
                    required: "Please enter the country name.",
                },
                state: {
                    required: "Please enter your state name.",
                },
                city: {
                    required: "Please enter your city name.",
                },
                zip: {
                    required: "Please enter your zip code.",
                    number: "Please enter a valid zip code.",
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
                    url: "<?php echo e(route('resort.Updateuser.profile')); ?>", // Ensure route is correct
                    type: "POST",
                    data: formData, // Use FormData instead of .serialize()
                    contentType: false, // Prevent jQuery from processing the data
                    processData: false, // Prevent jQuery from automatically transforming the data into a query string
                    success: function(response) {
                        if (response.success) {
                            $('#sendReminder-modal').modal('hide');

                            // Check if a new profile picture file is selected
                            var profilePictureInput = $('#profile_picture')[0];
                            if (profilePictureInput.files && profilePictureInput.files[0]) {
                                var newProfilePictureUrl = URL.createObjectURL(profilePictureInput.files[0]);
                                $('.profile-dropdown .img-box img').attr('src', newProfilePictureUrl);
                            }


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

       $('#ChangePassword').validate({
            rules: {
                old_password: {
                    required: true
                },
                password: {
                    required: true,
                    strongPassword: true,
                    notCommonPassword: true
                },
                confirmpassword: {
                    required: true,
                    equalTo: "#password"
                }
            },
            messages: {
                old_password: {
                    required: "Please enter your old password."
                },
                password: {
                    required: "Please enter a new password."
                },
                confirmpassword: {
                    required: "Please confirm your password.",
                    equalTo: "Passwords do not match."
                }
            },
            errorPlacement: function(error, element) {
                if (element.attr("name") === "confirmpassword") {
                    error.appendTo("#error-confirm");
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "<?php echo e(route('resort.profile.changePassword')); ?>",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
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

   document.getElementById('toggleOldPassword').addEventListener('click', function () {
        togglePasswordVisibility('old_password', 'toggleOldPassword');
    });

    document.getElementById('togglePassword').addEventListener('click', function () {
        togglePasswordVisibility('password', 'togglePassword');
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
        togglePasswordVisibility('confirmpassword', 'toggleConfirmPassword');
    });

    $("#profile_picture").on("change", function(event) {

        var file = event.target.files[0];
        if (file) {
            var output = document.getElementById("profileimg");
            output.src = URL.createObjectURL(file);
            output.onload = function() {
                URL.revokeObjectURL(output.src);
            };
        }
    });

    $("#signature_img").on("change", function(event) {
        var file = event.target.files[0];
        if (file) {
            var output = document.getElementById("signature_show_img");
            output.src = URL.createObjectURL(file);
            output.onload = function() {
                URL.revokeObjectURL(output.src);
            };
        }
    });

    function togglePasswordVisibility(inputId, buttonId) {
        const input = document.getElementById(inputId);
        const icon = document.querySelector(`#${buttonId} i`);
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }
});
</script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/workforce_planning/profile.blade.php ENDPATH**/ ?>