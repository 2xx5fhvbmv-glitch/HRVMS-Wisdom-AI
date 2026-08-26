@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Profile</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <form method="post" name="ProfileUpdate" id="ProfileUpdate" enctype="multipart/form-data" >
            @csrf
            <div class="card mb-30">
                <div class="card-title">
                    <h3>Update Profile </h3>
                </div>

                <div class="talentAc-main">

                    <div class="talentAc-block">
                        <div class="row g-md-4 g-3">
                            @if($profile->type == 'sub')
                                @if($profile->GetEmployee)
                                <div class="col-md-12">
                                    <label class="form-label"> Department Name : @php   echo $profile->GetEmployee->department->name @endphp </label><br/>
                                    <label class="form-label"> Position Name :   @php   echo $profile->GetEmployee->position->position_title @endphp </label><br/>
                                    <label class="form-label"> Employee Id :     @php   echo $profile->GetEmployee->Emp_id @endphp </label>
                                </div>

                                @endif
                            @else
                                @php $type="Resort Admin"; @endphp

                            @endif
                        </div>
                    </div>

                    <div class="talentAc-block">
                        <div class="mb-2">
                            <h6>Personal Details</h6>
                        </div>
                        <input type="hidden" name="id" value="{{ old('id',isset($profile->id) ? $profile->id : '') }}">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">First Name <span class="req_span">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="{{ old('first_name',isset($profile->first_name) ? $profile->first_name : '') }}" placeholder="First Name"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" value="{{ old('middle_name',isset($profile->middle_name) ? $profile->middle_name : '') }}" placeholder="Middle Name"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Last Name <span class="req_span">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="{{ old('last_name',isset($profile->last_name) ? $profile->last_name : '') }}" placeholder="Last Name"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Gender <span class="req_span">*</span> </label>
                                <input type="text" readonly id="gender" name="gender" class="form-control" value="{{ old('gender',isset($profile->gender) ? $profile->gender : '') }}" required/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Phone Number  <span class="req_span">*</span></label>
                                <input type="text" id="personal_phone" name="personal_phone" class="form-control" value="{{ old('personal_phone',isset($profile->personal_phone) ? $profile->personal_phone : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Email Id <span class="req_span">*</span> </label>
                                <input type="text" id="email" name="email" class="form-control" value="{{ old('email',isset($profile->email) ? $profile->email : '') }}" readonly/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Role </label>
                                @if($profile->type == 'sub')
                                    @if($profile->GetEmployee)
                                    @php $type=(in_array($profile->GetEmployee->rank,['HOD','HR']))  ?  $profile->GetEmployee->rank : $type="Employee"; @endphp
                                    @else
                                        @php $type="Employee";@endphp
                                    @endif
                                @else
                                    @php $type="Resort Admin"; @endphp

                                @endif
                                <input type="text" readonly id="type" name="type" class="form-control" value="{{ old('type',$type) }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">
                                    @if($profile->type == 'sub') Profile @else Brand  @endif Image
                                </label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn wfp-btn-primary btn-sm">Upload File</a>
                                        <input type="file" name="profile_picture" id="profile_picture" >
                                    </div>
                                    <div class="uploadFile-text">

                                        <img id="profileimg" class="logo-img" width="100px" src="{{ Common::getResortUserPicture($profile->id) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="header_img">Select Authorized Signature Image </label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn wfp-btn-primary btn-sm">Upload File</a>
                                        <input type="file" name="signature_img" id="signature_img"  >
                                    </div>
                                    <div class="uploadFile-text">
                                        <img id="signature_show_img" class="logo-img" width="100px" accept="image/*" src="{{ Common::getResortUserPicture($profile->id,1) }}">
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
                                <input type="text" id="address_line_1" name="address_line_1" class="form-control" value="{{ old('address_line_1',isset($profile->address_line_1) ? $profile->address_line_1 : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 2 <span class="req_span">*</span></label>
                                <input type="text" id="address_line_2" name="address_line_2" class="form-control" value="{{ old('address_line_2',isset($profile->address_line_2) ? $profile->address_line_2 : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Country <span class="req_span">*</span></label>
                                <input type="text" id="country " name="country" class="form-control" placeholder="Please enter country " value="{{ old('country ',isset($profile->country ) ? $profile->country : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">ATOLL <span class="req_span">*</span></label>
                                <input type="text" id="state " name="state" class="form-control" placeholder="Please enter ATOLL " value="{{ old('state',isset($profile->state ) ? $profile->state : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">City <span class="req_span">*</span></label>
                                <input type="text" id="city" name="city" class="form-control" placeholder="Please enter City" value="{{ old('city',isset($profile->city) ? $profile->city : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Pincode <span class="req_span">*</span></label>
                                <input type="text" id="zip" required name="zip" class="form-control" placeholder="Please enter Pincode" value="{{ old('zip', isset($profile->zip) ? $profile->zip : '') }}"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-none d-xl-block" style="height: 126px;"></div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn wfp-btn-primary btn-sm">Submit</button>
                    <a class="btn btn-sm wfp-btn-secondary me-2" href="{{url()->previous()}}">Back</a>
                </div>
            </div>
        </form>

        <form method="post" name="ChangePassword" id="ChangePassword" >
            @csrf
            <div class="card">
                <div class="card-title">
                    <h3>Change Password </h3>
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
                                <input type="password" id="password" name="password" class="form-control" value="{{ old('password') }}">
                                <button  type="button" id="togglePassword">
                                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="col-md-4">
                            <label for="emp-grade-select" class="form-label">Confirm Password <span class="req_span">*</span></label>
                            <div class="input-password">
                                <input type="password" id="confirmpassword" name="confirmpassword" class="form-control" value="{{ old('confirmpassword') }}"/>
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
                    <button type="submit" class="btn wfp-btn-primary btn-sm">Change Password</button>
                    <a class="btn btn-sm wfp-btn-secondary me-2" href="{{url()->previous()}}">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Cropper modal — opened when the user picks a profile picture. The
     cropped result replaces the file in the original input via DataTransfer
     so the existing form submit logic doesn't need to change. --}}
<div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Profile Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="cropperCancelBtn"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Drag the box to position, scroll/pinch to zoom, then save. The image is cropped to a square (recommended for avatars).</p>
                <div id="cropperContainer">
                    <img id="cropperImage" alt="To crop">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn wfp-btn-secondary btn-sm" data-bs-dismiss="modal" id="cropperCancelBtn2">Cancel</button>
                <button type="button" class="btn wfp-btn-primary btn-sm" id="cropperApplyBtn">Save Crop</button>
            </div>
        </div>
    </div>
</div>
@endSection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
{{-- Cropper.js — lets the user trim a tall/wide image to a square avatar
     before upload. Without this, large portraits broke the page layout. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" integrity="sha512-UtLOu9C7NuThQhuXXrGwx9Jb/z9zPQJctuAgNUBK3Z6kkSYT9wJ+2+dh6klS+TDBCV9kNPBbAxbVD+vCcfGPaA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    /* Hard-cap the avatar preview height so an oversized portrait can't
       push the form rows out of alignment. The cropper itself enforces a
       1:1 ratio for new uploads, but legacy server-stored portraits still
       need this guard. */
    #profileimg, #signature_show_img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        background: #f5f5f5;
    }
    #cropperImage { max-width: 100%; display: block; }
    #cropperContainer {
        max-height: 60vh;
        background: #f8f9fa;
        border-radius: 6px;
        overflow: hidden;
    }
</style>
@endsection

@section('import-scripts')

{{-- Cropper.js library — used by the profile-image change handler below. --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehUTiOuK87A986BZJMko0eWQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
{{-- heic2any: iPhone photos default to HEIC, which Chrome/Firefox/Edge
     can't render. Before passing the file to Cropper, we detect HEIC
     and silently transcode to JPEG in the browser so the rest of the
     flow stays unchanged. --}}
<script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
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
                    url: "{{ route('resort.Updateuser.profile') }}", // Ensure route is correct
                    type: "POST",
                    data: formData, // Use FormData instead of .serialize()
                    contentType: false, // Prevent jQuery from processing the data
                    processData: false, // Prevent jQuery from automatically transforming the data into a query string
                    success: function(response) {
                        if (response.success) {
                            $('#sendReminder-modal').modal('hide');

                            // Update only the avatar in the navbar dropdown TOGGLE, not
                            // the dropdown menu items (Profile / Settings / Currency etc.
                            // each have their own .img-box icon and would otherwise all
                            // get replaced with the user's avatar). The toggle's avatar
                            // sits inside `.profile-dropdown > .nav-link.dropdown-toggle`
                            // — scope the selector to that anchor only.
                            var profilePictureInput = $('#profile_picture')[0];
                            if (profilePictureInput.files && profilePictureInput.files[0]) {
                                var newProfilePictureUrl = URL.createObjectURL(profilePictureInput.files[0]);
                                $('.profile-dropdown > .dropdown-toggle .img-box img').first().attr('src', newProfilePictureUrl);
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
                    url: "{{ route('resort.profile.changePassword') }}",
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

    // Profile-image crop flow.
    //
    // When the user picks a file, open a Cropper.js modal instead of
    // dumping the raw file into the form. After they confirm the crop,
    // the cropped Blob is wrapped in a File and stuffed back into
    // #profile_picture via DataTransfer — the existing FormData submit
    // path then sends the trimmed image without any further changes.
    var __cropper = null;
    var __cropperTargetInput = document.getElementById('profile_picture');
    var __cropperPreviewImg  = document.getElementById('profileimg');

    // Helper: a file is HEIC/HEIF if either the MIME type says so or the
    // file extension does. Some iPhones (esp. older iOS) report HEIC
    // files with MIME "" or "application/octet-stream" rather than
    // image/heic, so we also sniff the extension.
    function __isHeicFile(file) {
        if (!file) return false;
        var t = (file.type || '').toLowerCase();
        if (t === 'image/heic' || t === 'image/heif') return true;
        var n = (file.name || '').toLowerCase();
        return n.endsWith('.heic') || n.endsWith('.heif');
    }

    // Convert HEIC → JPEG Blob in the browser using heic2any. Returns
    // a Promise<File>. Resolves with a normal JPEG File the rest of
    // the pipeline can handle.
    function __heicToJpegFile(originalFile) {
        if (typeof heic2any !== 'function') {
            return Promise.reject(new Error('heic2any not loaded'));
        }
        return heic2any({ blob: originalFile, toType: 'image/jpeg', quality: 0.9 })
            .then(function (jpegBlob) {
                var baseName = (originalFile.name || 'profile').replace(/\.(heic|heif)$/i, '');
                return new File([jpegBlob], baseName + '.jpg', { type: 'image/jpeg' });
            });
    }

    $("#profile_picture").on("change", function (event) {
        var inputEl = this;
        var file = event.target.files[0];
        if (!file) return;

        // HEIC path: transcode first, then re-enter the normal flow.
        // We swap the input's FileList to the converted JPEG so the
        // form-submit handler picks up the right file even if the
        // user skips the crop step.
        if (__isHeicFile(file)) {
            toastr.info('Converting iPhone photo...', '', { positionClass: 'toast-bottom-right', timeOut: 2000 });
            __heicToJpegFile(file).then(function (jpegFile) {
                try {
                    var dt = new DataTransfer();
                    dt.items.add(jpegFile);
                    inputEl.files = dt.files;
                } catch (e) { /* older browsers — fall through */ }
                __openCropperForFile(jpegFile);
            }).catch(function (err) {
                console.error('HEIC conversion failed:', err);
                toastr.error('Could not read this HEIC photo. Please convert it to JPEG and try again.', 'Error', { positionClass: 'toast-bottom-right' });
                inputEl.value = '';
            });
            return;
        }

        if (!file.type || file.type.indexOf('image/') !== 0) {
            // Non-image (shouldn't normally happen — input has no
            // accept=, but defend just in case). Fall back to the old
            // direct-preview behaviour.
            __cropperPreviewImg.src = URL.createObjectURL(file);
            return;
        }
        __openCropperForFile(file);
    });

    // Extracted from the original change handler so HEIC and non-HEIC
    // paths share the same Cropper bootstrap code.
    function __openCropperForFile(file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById('cropperImage');
            img.src = e.target.result;

            // Tear down any previous cropper instance.
            if (__cropper) { try { __cropper.destroy(); } catch (e) {} }

            // Show the modal first, then init Cropper after the image
            // has a non-zero width — Cropper needs measurable dimensions.
            var modalEl = document.getElementById('cropperModal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            modalEl.addEventListener('shown.bs.modal', function _init() {
                modalEl.removeEventListener('shown.bs.modal', _init);
                __cropper = new Cropper(img, {
                    aspectRatio: 1,             // square avatar
                    viewMode: 1,                 // crop box stays inside the canvas
                    autoCropArea: 0.9,
                    movable: true,
                    zoomable: true,
                    rotatable: false,
                    scalable: false,
                    background: false,
                    minContainerHeight: 320,
                });
            }, { once: true });
        };
        reader.readAsDataURL(file);
    }

    // "Save crop" — turn the crop selection into a JPEG Blob, wrap as
    // File, and replace the file input's FileList. Bumping the size cap
    // to 600px keeps the avatar small (~50KB) so we're not shipping
    // megapixel portraits to Wasabi.
    $('#cropperApplyBtn').on('click', function () {
        if (!__cropper) return;
        var canvas = __cropper.getCroppedCanvas({
            width: 600,
            height: 600,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        if (!canvas) return;
        canvas.toBlob(function (blob) {
            if (!blob) return;
            var croppedFile = new File([blob], 'profile.jpg', { type: 'image/jpeg' });
            // Replace the file input's FileList. DataTransfer is the
            // standard way to programmatically set <input type=file> .files.
            try {
                var dt = new DataTransfer();
                dt.items.add(croppedFile);
                __cropperTargetInput.files = dt.files;
            } catch (err) {
                console.warn('DataTransfer not supported, falling back to direct assignment', err);
            }
            // Update the inline preview to the cropped image.
            var url = URL.createObjectURL(blob);
            __cropperPreviewImg.src = url;
            __cropperPreviewImg.onload = function () { URL.revokeObjectURL(url); };

            // Tear down + close.
            try { __cropper.destroy(); } catch (e) {}
            __cropper = null;
            var modal = bootstrap.Modal.getInstance(document.getElementById('cropperModal'));
            if (modal) modal.hide();
        }, 'image/jpeg', 0.9);
    });

    // Cancelling the crop — clear the file input so a partially-picked
    // file doesn't sit in the form half-loaded.
    $('#cropperCancelBtn, #cropperCancelBtn2').on('click', function () {
        if (__cropper) { try { __cropper.destroy(); } catch (e) {} __cropper = null; }
        if (__cropperTargetInput) __cropperTargetInput.value = '';
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
@endsection


