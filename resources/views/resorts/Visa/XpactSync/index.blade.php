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
                        <span>VISA MANAGEMENT</span>
                        <h1>{{$page_title}}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card Xpat-Sync-card">
                <form enctype="multipart/form-data" data-parsley-validate id="XpatSyncForm">
                    @csrf  
                    <div class="row">
                        <!-- Xpat File Upload -->
                        <div class="col-lg-6">
                            <div class="card bg h-100">
                                <div class="uploadFileNew-block h-100">
                                    <img src="{{URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                    <h5>Upload Screenshot(S) From Xpat</h5>
                                    <input 
                                        type="file" 
                                        id="Xpatfile" 
                                        name="Xpatfile" 
                                        class="form-control" 
                                        accept=".jpg,.jpeg,.png,.pdf" 
                                        required 
                                        data-parsley-required-message="Please upload a file from Xpat"
                                        data-parsley-fileextension="jpg,jpeg,png,pdf"
                                        data-parsley-fileextension-message="Only JPG, JPEG, PNG, or PDF files are allowed."
                                        data-parsley-trigger="change"
                                    >
                                    <p class="mb-0">You can upload Single file.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quota Slot Fees Upload -->
                        <div class="col-lg-6">
                            <div class="card bg h-100">
                                <div class="uploadFileNew-block h-100">
                                    <img src="{{URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                    <h5>Upload Screenshot(S) From Quota Slot Fees</h5>
                                    <input 
                                        type="file" 
                                        id="QuotaSlotFees" 
                                        name="QuotaSlotFees" 
                                        class="form-control" 
                                        accept=".jpg,.jpeg,.png,.pdf" 
                                        required
                                        data-parsley-required-message="Please upload files from Quota Slot Fees"
                                        data-parsley-fileextension="jpg,jpeg,png,pdf"
                                        data-parsley-fileextension-message="Only JPG, JPEG, PNG, or PDF files are allowed."
                                        data-parsley-trigger="change">
                                    <p class="mb-0">You can upload Single files.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previews -->
                    <div class="row mt-3">
                        <div class="col-lg-6 Xpatfile">
                            <img id="XpatfilePreviewIMG" style="display:none; max-width:100%; height:auto;" />
                            <iframe id="XpatfilePreviewPDF" style="display:none; width:100%; height:400px;"></iframe>
                        </div>

                        <div class="col-lg-6 QuotaSlotFees">
                            <img id="QuotaSlotFeesPreviewIMG" style="display:none; max-width:100%; height:auto;" />
                            <iframe id="QuotaSlotFeesPreviewPDF" style="display:none; width:100%; height:400px;"></iframe>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="card-footer mt-3">
                        <button type="submit" class="btn btn-themeBlue btn-sm float-end next SubmitFile">Submit</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
    <div class="modal fade" id="quotaslot-modal" tabindex="-1" aria-labelledby="quotaslot-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Payment Type </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="VisaQuotaslot" data-parsley-validate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="emp_id" id="QuotaSlot_emp_id" class="form-control" placeholder="Enter Employee ID">
                        <input type="hidden" name="flag" id="QuotaSlot_flag" class="form-control" placeholder="Enter Flag">

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label d-block">Payment Type<span class="red-mark">*</span></label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_lumpsum" class="form-check-input"
                                        value="Lumpsum" required data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_lumpsum">Lumpsum</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_installment" class="form-check-input"
                                        value="Installment" required data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_installment">Installment</label>
                                </div>
                                <div id="payment_type_error" class="text-danger"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue FileUploadButton" href="javascript:void(0)">Submit</button>
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

    window.Parsley.addValidator('fileextension', {
        requirementType: 'string',
        validateString: function(value, requirement, parsleyInstance) {
            const fileInput = parsleyInstance.$element[0];
            const allowedExtensions = requirement.split(',');

            if (!fileInput.files.length) return false;

            for (let i = 0; i < fileInput.files.length; i++) {
                const file = fileInput.files[i];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(ext)) return false;
            }
            return true;
        },
        messages: {
            en: 'Invalid file extension.'
        }
    });
    $("#Xpatfile").on("change", function () 
    {
        const file = this.files[0];
        if (!file) 
        {
            console.log("No file selected.");
            return false;
        }

        const fileType = file.type;
        const reader = new FileReader();

        reader.onload = function (e) {
            const fileURL = e.target.result;
            console.log("File URL: " + fileURL,fileType);
            if (fileType === "application/pdf") {
                $("#XpatfilePreviewPDF").attr("src", fileURL).show();
                $("#XpatfilePreviewIMG").hide();
            } else if (fileType.startsWith("image/")) {
                $("#XpatfilePreviewIMG").attr("src", fileURL).show();
                $("#XpatfilePreviewPDF").hide();
            } 
            else
             {
                toster.error("Unsupported file type. Please upload a valid image or PDF file.");
                $("#XpatfilePreviewIMG").hide();
                $("#XpatfilePreviewPDF").hide();
            }
        };

        reader.readAsDataURL(file);
    });

     $("#QuotaSlotFees").on("change", function () 
    {
        const file = this.files[0];
        if (!file) 
        {
            console.log("No file selected.");
            return false;
        }

        const fileType = file.type;
        const reader = new FileReader();

        reader.onload = function (e) {
            const fileURL = e.target.result;
            console.log("File URL: " + fileURL,fileType);
            if (fileType === "application/pdf") {
                $("#QuotaSlotFeesPreviewPDF").attr("src", fileURL).show();
                $("#QuotaSlotFeesPreviewIMG").hide(); 
            } else if (fileType.startsWith("image/")) {
                $("#QuotaSlotFeesPreviewIMG").attr("src", fileURL).show();
                $("#QuotaSlotFeesPreviewPDF").hide();
            } 
            else
             {
                toster.error("Unsupported file type. Please upload a valid image or PDF file.");
                $("#QuotaSlotFeesPreviewIMG").hide();
                $("#QuotaSlotFeesPreviewPDF").hide();
            }
        };

        reader.readAsDataURL(file);
    });

    $('#XpatSyncForm').on('submit', function(e) {
        e.preventDefault();
        if ($(this).parsley().isValid()) 
        {
            $(".SubmitFile").addClass('btn-danger').removeClass('btn-themeBlue')
                   .html("Please Wait AI Insights is Working Don't Refresh Page")
                   .attr('data-processing', 'true');
            
            // Add beforeunload event to prevent page refresh
            $(window).on('beforeunload', function(e) {
                if ($(".SubmitFile").attr('data-processing') === 'true') {
                    // Show confirmation dialog
                    e.preventDefault();
                    // Message for modern browsers
                    e.returnValue = 'Data is currently being processed. Are you sure you want to leave?';
                    return 'Data is currently being processed. Are you sure you want to leave?';
                }
            });
            
            $.ajax({
            url: "{{ route('resorts.visa.xpactsync.store') }}",
            type: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(response) {
                // Remove beforeunload event when processing is complete
                $(window).off('beforeunload');
                
                if (response.success) 
                {
                toastr.success(response.msg, "Success", {
                    positionClass: 'toast-bottom-right'
                });
                // Restore original button state
                $(".SubmitFile").removeClass('btn-danger').addClass('btn-themeBlue')
                           .html('Submit').attr('data-processing', 'false');
                } 
                else
                {
                toastr.error(response.msg, "Success", 
                {
                    positionClass: 'toast-bottom-right'
                });
                $(".SubmitFile").removeClass('btn-danger').addClass('btn-themeBlue')
                           .html('Submit').attr('data-processing', 'false');
                }
            },
            error: function(response) {
                // Remove beforeunload event when processing is complete
                $(window).off('beforeunload');
                
                if (response.status === 422) {
                var errs = '';

                // Laravel Validation Errors
                if (response.responseJSON.errors) {
                    let errors = response.responseJSON.errors;

                    if (typeof errors === 'object') {
                    $.each(errors, function (field, messages) {
                        // Check if it's an array (standard Laravel validation)
                        if (Array.isArray(messages)) {
                        $.each(messages, function (index, message) {
                            errs += message + '<br>';
                        });
                        } else {
                        // Just a message string
                        errs += messages + '<br>';
                        }
                    });
                    } else {
                    // If errors is a string
                    errs = errors;
                    }
                }
                $(".SubmitFile").removeClass('btn-danger').addClass('btn-themeBlue')
                           .html('Submit').attr('data-processing', 'false');

                toastr.error(errs, "Validation Error", {
                    positionClass: 'toast-bottom-right'
                });
                } else {
                $(".SubmitFile").removeClass('btn-danger').addClass('btn-themeBlue')
                           .html('Submit').attr('data-processing', 'false');
                toastr.error("An unexpected error occurred.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                }
            }
            });
            
            // Prevent multiple submissions
            $(this).on('submit', function(e) {
            if ($(".SubmitFile").attr('data-processing') === 'true') {
                e.preventDefault();
                return false;
            }
            });
        } 
        else
        {
            console.log("Validation failed.");
        }
    });

</script>
@endsection
