
<?php $__env->startSection('page_tab_title', 'Site Settings'); ?>

<?php $__env->startSection('content'); ?>

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Resort Pages</span>
                        <h1>Site Settings</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col">
                        <div class="card-title border-0 p-0 m-0">
                            <h3>Settings</h3>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="<?php echo e(route('resort.Page.Permission')); ?>" target="_blank" class="btn btn-themeSkyblue btn-sm">PAGE PERMISSION</a>
                    </div>
                </div>
            </div>

            <form method="POST" id="siteSettingsForm" class="form-horizontal" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="resort_id" value="<?php echo e($resort->id); ?>">
                <div class="mb-md-4 mb-3">
                    <label class="form-label">Select Currency</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="currency" name="currency"
                            value="MVR" <?php echo e((isset($SiteSettings->currency ) && $SiteSettings->currency =="MVR" ?'checked': '')); ?> >
                            <label class="form-check-label" for="currency">MVC</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="currency1" name="currency" value="Dollar"  <?php echo e((isset($SiteSettings->currency ) && $SiteSettings->currency =="Dollar" ?'checked': '')); ?> >
                            <label class="form-check-label" for="currency1">Dollar</label>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="header_img">  </label>
                            <label for="MVRtoDoller" class="form-label">MVR To Doller  <span class="red-mark">*</span></label>
                            <input type="number" class="form-control" id="MVRtoDoller" name="MVRtoDoller" value="<?php echo e(old('MVRtoDoller',(isset( $SiteSettings->MVRtoDoller)) ?  $SiteSettings->MVRtoDoller : '')); ?>" placeholder="MVR To Doller Exchange Rate ">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="header_img">  </label>
                            <label for="DollertoMVR" class="form-label"> Doller To MVR   <span class="red-mark">*</span></label>
                            <input type="number" class="form-control" id="DollertoMVR" name="DollertoMVR" value="<?php echo e(old('DollertoMVR',(isset( $SiteSettings->DollertoMVR)) ?  $SiteSettings->DollertoMVR : '')); ?>" placeholder="Doller To MVR  Exchange Rate ">
                    </div>
                    <div class="col-md-4 mb-3">
                        <div>
                            <label class="form-label" for="header_img">  </label>
                            <label for="DollertoMVR" class="form-label"> Final Approval   <span class="red-mark">*</span></label>
                            <?php

                             $position_rank = config('settings.final_rank');


                            ?>
                            <select class="form-control" id="FinalApproval" name="FinalApproval">
                                    <option  ></option>
                                    <?php if(!empty($position_rank)): ?>
                                        <?php $__currentLoopData = $position_rank; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($key); ?>" <?php echo e((isset($SiteSettings->FinalApproval ) && $SiteSettings->FinalApproval ==$key ?'selected': '')); ?>><?php echo e($position); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mb-md-4 mb-3">
                   <div class="talentAc-block">
                        <div class="mb-2">
                            <h6>Header  Details</h6>
                        </div>
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">
                                </label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" >
                                    </div>
                                    <div class="uploadFile-text">
                                        <img id="Resortimg" class="logo-img" width="100px"accept="image/*"  src="<?php echo e(Common::GetResortLogo($Resort->id)); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 1 <span class="red-mark">*</span> </label>

                                <input type="text" id="address1" name="address1" class="form-control" value="<?php echo e(old('address1',isset($Resort->address1) ? $Resort->address1 : '')); ?>"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 2 <span class="red-mark">*</span> </label>
                                <input type="text" id="address2" name="address2" class="form-control" value="<?php echo e(old('address2',isset($Resort->address2) ? $Resort->address2 : '')); ?>"/>
                            </div>




                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">Country <span class="red-mark">*</span></label>
                                <input type="text" id="country " name="country" class="form-control" placeholder="Please enter country " value="<?php echo e(old('country ',isset($Resort->country ) ? $Resort->country : '')); ?>"/>
                            </div>
                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">ATOLL <span class="red-mark">*</span></label>
                                <input type="text" id="state " name="state" class="form-control" placeholder="Please enter ATOLL " value="<?php echo e(old('ATOLL',isset($Resort->state ) ? $Resort->state : '')); ?>"/>
                            </div>
                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">City <span class="red-mark">*</span> </label>
                                <input type="text" id="city" name="city" class="form-control" placeholder="Please enter City" value="<?php echo e(old('city',isset($Resort->city) ? $Resort->city : '')); ?>"/>
                            </div>
                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">Pincode <span class="red-mark">*</span></label>
                                <input type="text" id="zip " name="zip" class="form-control" placeholder="Please enter Pincode " value="<?php echo e(old('zip ',isset($Resort->zip ) ? $Resort->zip : '')); ?>"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                    <div class="mb-2">
                        <h6>Footer  Details</h6>
                    </div>
                    <textarea name="footer" class="form-control" id=""  rows="3">  <?php echo e(old('footer ',isset($SiteSettings->Footer)  &&  $SiteSettings->Footer !="" ? $SiteSettings->Footer :config('settings.Footer'))); ?></textarea>
                </div>
                </div>
                



                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end mt-3"></div>
                            <button type="submit" class="btn btn-theme">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>
<script>
$(document).ready(function(){
    $("#FinalApproval").select2({
        "placeholder": "Select Final Approval",
    });
        $("#profile_picture").on("change", function(event) {

            var file = event.target.files[0];
            if (file)
            {
                var output = document.getElementById("Resortimg");
                output.src = URL.createObjectURL(file);
                output.onload = function() {
                    URL.revokeObjectURL(output.src);
                };
            }
        });
            $('#siteSettingsForm').validate({
                rules: {
                    currency: {
                        required: true,

                    },
                    address1:{
                        required: true,
                    },
                    address2:{
                        required: true,
                    },

                    FinalApproval:{
                        required: true,
                    },
                    MVRtoDoller: {
                        required: true,
                        maxlength:2,
                        maxlength:7,
                    },
                    DollertoMVR: {
                        required: true,
                        minlength:2,
                        maxlength:7,

                    },
                    country:{
                        required: true,
                    }
                    ,
                    state :{
                        required: true,
                    }
                    ,
                    city:{
                        required: true,
                    }
                    ,
                    zip :{
                        required: true,
                    },
                },
                messages: {
                    currency: {
                        required: "Please chiose one at  currency.",
                        // maximum: "request massage must be less than  to 700."
                    },
                    MVRtoDoller:{
                        required:"Please Enter MVR to Doller Exchanges Rate.",
                    },
                    DollertoMVR:{
                        required: "Please Enter Doller to MVR Exchange Rate.",
                    },
                    FinalApproval:{
                        required: "Please Select  Final Approval .",
                    },
                    address1:{
                        required: "Please Enter Address line One.",
                    },
                    address2:{
                        required: "Please Enter Address line Two",
                    },
                    country:{
                        required: "Please Enter Country.",
                    },

                    state :{
                        required: "Please Enter State.",

                    }
                    ,
                    city:{
                        required: "Please Enter City.",

                    }
                    ,
                    zip :{
                        required: "Please Enter Zip Code.",

                    },

                },
                submitHandler: function(form) {
                    var formData = new FormData(form); // Use FormData to handle file inputs

                    $.ajax({
                        url: "<?php echo e(route('resort.updatesitesettings')); ?>", // Ensure route is correct
                        type: "POST",
                        data: formData,
                        contentType: false,  // Required for file uploads
                        processData: false,  // Required for file uploads
                        success: function(response) {
                            if (response.success) {
                                $('#sendReminder-modal').modal('hide');
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
                            $.each(errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });
                }
            });
            //image selection
            $("#header_img").on("change", function(event) {

                var file = event.target.files[0];
                if (file) {
                    var output = document.getElementById("header_show_img");
                    output.src = URL.createObjectURL(file);
                    output.onload = function() {
                        URL.revokeObjectURL(output.src);
                    };
                }
            });
            $("#footer_img").on("change", function(event) {

                var file = event.target.files[0];
                if (file) {
                    var output = document.getElementById("footer_show_img");
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
            //End of image selection
});


</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/sitesttings/index.blade.php ENDPATH**/ ?>