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
                            <span>Visa Management</span>
                            <h1>{{$page_title}}</h1>
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
                    <div class="row g-md-4 g-3  mb-3">
                        <div class="col-xxl-4 col-xl-5 col-md-6">
                            <label for="Visa_select_emp" class="form-label">EMPLOYEE NAME</label>
                            <input type="text" class="form-control d-none" name="Visa_select_emp" id="Visa_select_emp" value="TEs">
                        </div>
                        <div class="col-xxl-8 col-xl-7 col-md-6">
                            <div class="empDetails-user">
                                <div class="img-circle"><img style="display:none" id="userImage"  alt="user">
                                </div>
                                <div>
                                    <div id="employeeName">
                                    </div>
                                    <p id="position"></p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="row g-md-4 g-3  mb-3 Append Updated_Employee_details">
                       
                    </div>
                </div>
            </div>
        </div>
</div>
    <div class="modal fade" id="selectFileForVisa-modal" tabindex="-1" aria-labelledby="selectFolderLocationLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Select  File </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="VisaFileUpload"enctype="multipart/form-data">
                    <div class="modal-body ">

                        <div class="row">
                            <input type="hidden" name="emp_id" id="emp_id" value="">
                            <input type="hidden" name="flag" id="flag" value="">
                            <div class="bg-themeGrayLight mb-md-4 mb-3">
                                    <div class="uploadFileNew-block">
                                        <img src="{{URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                        <h5>Upload  Documents</h5>
                                        <p>Browse or Drag the file here</p>
                                        <input type="file" id="file"  name="file" accept="image/*,application/pdf">
                                    </div>
                                        <div id="fileNameDisplay" style="margin-top: 10px; font-weight: bold;"></div>

                                </div>
                        </div>
                    
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue FileUploadButton" href="javascript:void(0)">Upload File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
     <div class="modal fade" id="quotaslot-modal" tabindex="-1" aria-labelledby="quotaslot-modal"
        aria-hidden="true">
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
                                        value="Lumpsum" required data-parsley-required="true" 
                                        data-parsley-required-message="Please select a payment type"
                                        data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_lumpsum">Lumpsum</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_installment" class="form-check-input"
                                        value="Installment" required data-parsley-required="true"
                                        data-parsley-required-message="Please select a payment type"
                                        data-parsley-errors-container="#payment_type_error">
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
$(document).ready(function () 
{
    $('#VisaQuotaslot').parsley();
    $("#file").on("change", function() {
        var files = $(this).prop("files");
        var fileNames = $.map(files, function(val) {
            return val.name;
        }).join(", ");
        $("#fileNameDisplay").text("Selected file: " + fileNames);
    });
    $("#Visa_select_emp").select2({
        placeholder: "Select Employee",
        allowClear: true,
    });
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    $("#Visa_select_emp").on("change",function()
    {
        var id = $(this).val();
        var position = $(this).find(':selected').data('position');
        var emp_id = $(this).find(':selected').data('emp_id');
        var profile = $(this).find(':selected').data('profile');
        var name = $(this).find(':selected').data('name');
        $("#userImage").attr("src", profile).css("display", "block");
        $("#Header_Emp_id").html("TEs");
        $("#position").html(position);
        $("#employeeName").html(`<h4>${name}<span class="badge badge-themeNew"> ${emp_id} </span></h4>`);

          $.post("{{ route('resorts.visa.renewal.getEmployeeDetails') }}", 
          {
                emp_id: id,
                _token: "{{ csrf_token() }}"
            }, function (data) 
            {
              

                    var data = data.data;
                    var EmployeeInsurance = data.EmployeeInsurance;
                    var QuotaSlotRenewal = data.QuotaSlotRenewal;
                    var VisaRenewal = data.VisaRenewal;
                    var WorkPermitMedicalRenewal = data.WorkPermitMedicalRenewal;
                   var WorkPermitRenewal = data.WorkPermitRenewal;
                    var html='';
                    if(isNaN(EmployeeInsurance))
                    {
                          html+=`<div class="col-xl-6">  
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Medical Insurance - International Renewal</h3>
                                                    <span>Current Policy Expires: ${EmployeeInsurance.insurance_end_date}</span>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end"><span class="badge badge-themeWarning">${EmployeeInsurance.InsuranceRenewalTime}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row  gx-2">
                                            <div class="col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Policy Number</label>
                                                    <p>Policy Number:    ${EmployeeInsurance.insurance_policy_number || 'N/A'}</p>
                                                </div>
                                                <div class="renewal-innerbox">
                                                    <label>Insurance company name</label>
                                                    <p>${EmployeeInsurance.insurance_company}</p>
                                                </div>

                                            </div>
                                            <div class="col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Premium</label>
                                                    <p>${EmployeeInsurance.cost}</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <a href="javascript:void(0)" data-emp_id="${EmployeeInsurance.employee_id}" data-flag="insurance" class="SendFile btn btn-themeBlue btn-sm">
                                                    Upload The New Insurance Policy
                                                </a>
                                            </div>


                                        </div>
                                    </div>
                                </div>`;
                    }
                    else
                    {
                        html+=`<div class="col-xl-6">
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3> Medical  Insurance - Internationl  Renewal</h3>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end">
                                                
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div class="card-body">
                                            <div class="row gx-2">
                                                No  Record Found
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                    }
                    if(isNaN(WorkPermitMedicalRenewal))
                    {
                        html+=`<div class="col-xl-6">
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Work Permit Medical Test Fee Renewal</h3>
                                                    <span>Last Test Date: ${WorkPermitMedicalRenewal.medical_end_date}</span>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end"><span
                                                        class="badge badge-themeWarning">${WorkPermitMedicalRenewal.MedicalRenewalTime}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row  gx-2">
                                            <div class="col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Reference Number</label>
                                                    <p>${WorkPermitMedicalRenewal.Reference_Number || 'N/A'}</p>
                                                </div>
                                                <div class="renewal-innerbox">
                                                    <label>Medical Center name</label>
                                                    <p>${WorkPermitMedicalRenewal.Medical_Center_name}</p>
                                                </div>

                                            </div>
                                            <div class="col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label> Cost</label>
                                                    <p> ${WorkPermitMedicalRenewal.workpermitcost}</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <a href="javascript:void(0)" data-emp_id="${WorkPermitMedicalRenewal.employee_id}" data-flag="work_permit_card_Test_Fee" class="SendFile btn btn-themeBlue btn-sm">
                                                    Upload The New Work Permit
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                </div>`;
                    }
                    else
                    {
                        html+=`<div class="col-xl-6">
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                     <h3>Work Permit Medical Test Fee Renewal</h3>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end">
                                                
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div class="card-body">
                                            <div class="row gx-2">
                                                No  Record Found
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                    }
                    if(isNaN(VisaRenewal))
                    {
                        html+=`<div class="col-xl-6">
                            <div class="renewal-box mb-4">
                                <div class="card-title">
                                    <div class="row g-2 ">
                                        <div class="col order-sm-1 order-last">
                                            <h3>Visa Renewal</h3>
                                            <span>Current Policy Expires:${VisaRenewal.end_date}</span>
                                        </div>
                                        <div class="col-auto col-sm-auto col-12 order-sm-last order-1 text-end"><span
                                                class="badge badge-themeWarning">${VisaRenewal.VisaRenewalTime}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row  gx-2">
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>Visa Number</label>
                                            <p>${VisaRenewal.Visa_Number || 'N/A'}</p>
                                        </div>
                                        <div class="renewal-innerbox">
                                            <label>WP No</label>
                                            <p>${VisaRenewal.WP_No}</p>
                                        </div>
                                        

                                    </div>
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>Validity</label>
                                            <p>${VisaRenewal.Validitydate}</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>Cost</label>
                                            <p>${VisaRenewal.Amt}</p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <a href="javascript:void(0)" data-emp_id="${VisaRenewal.employee_id}" data-flag="visa" class="SendFile btn btn-themeBlue btn-sm">
                                            Upload The New Visa Renewal
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }
                    else
                    {
                        html+=`<div class="col-xl-6">
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Visa Renewal</h3>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end">
                                                
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div class="card-body">
                                            <div class="row gx-2">
                                                No  Record Found
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                    }
                    if(isNaN(QuotaSlotRenewal))
                    {
                        html+=`<div class="col-xl-6">
                            <div class="renewal-box mb-4">
                                <div class="card-title">
                                    <h3>Quota Slot Renewal</h3>
                                    <span>Last Slot month: ${QuotaSlotRenewal.QuotaSlotRenewal_end_date}</span>
                                </div>
                                <div class="row  gx-2">
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>New slot</label>
                                            <p>${QuotaSlotRenewal.NewSlot}</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>Payment Type</label>
                                            <p>${QuotaSlotRenewal.PaymentType}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <a href="javascript:void(0)" data-emp_id="${QuotaSlotRenewal.employee_id}" data-flag="QuotaSlot" class="QuotaSlot btn btn-themeBlue btn-sm">
                                            Renew
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }
                    else
                    {
                        html+=`<div class="col-xl-6">
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Quota Slot Renewal</h3>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end">
                                                
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div class="card-body">
                                            <div class="row gx-2">
                                                No  Record Found
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                    }

                     if(isNaN(WorkPermitRenewal))
                    {
                        html+=`<div class="col-xl-6">
                            <div class="renewal-box mb-4">
                                <div class="card-title">
                                    <h3>Work Permit Renewal</h3>
                                    <span>Last Slot month: ${WorkPermitRenewal.WorkPermitRenewal_end_date}</span>
                                </div>
                                <div class="row  gx-2">
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>New slot</label>
                                            <p>${WorkPermitRenewal.NewSlot}</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="renewal-innerbox">
                                            <label>Payment Type</label>
                                            <p>${WorkPermitRenewal.PaymentType}</p>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <a href="javascript:void(0)" data-emp_id="${WorkPermitRenewal.employee_id}" data-flag="WorkPermit" class="QuotaSlot  btn btn-themeBlue btn-sm">
                                            Renew
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }
                    else
                    {
                        html+=`<div class="col-xl-6">
                                    <div class="renewal-box mb-4">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Work Permit Slot Renewal</h3>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end">
                                                
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div class="card-body">
                                            <div class="row gx-2">
                                                No  Record Found
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                    }
                    $(".Updated_Employee_details").html(html);
        });
        
    });
    $(document).on("click", ".SendFile", function() {
        var flag = $(this).data("flag");
        var emp_id = $(this).data("emp_id");
        $("#selectFileForVisa-modal").modal('show');
        $("#emp_id").val(emp_id);
        $("#flag").val(flag);
    });
    $('#VisaFileUpload').validate({
        rules: {
            file:
            {
                required: false,
            }
        },
        messages: {
            
            file: 
            {
                required: "Please Select File.",
            }
        },
        errorPlacement: function(error, element)
        {

            if (element.is(':radio') || element.is(':checkbox')) 
            {
                error.insertAfter(element.closest('div'));
            } else {
                var nextElement = element.next('span');
                if (nextElement.length > 0) {
                    error.insertAfter(nextElement);
                } else {
                    error.insertAfter(element);
                }
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form); 

            $.ajax({
                url: "{{ route('resorts.visa.renewal.UploadSeparetFileUsingAi') }}", 
                type: "POST",
                data: formData,
                contentType: false,  
                processData: false, 
                success: function(response) {
                    if (response.success) {


                    $("#selectFileForVisa-modal").modal('hide');
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(function() {
                            window.location.reload();
                        }, 3000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    if (response.status === 422)
                    {
                            var errors = response.responseJSON.errors; // Access error object
                            var errs = '';
                            $.each(errors, function (field, messages) {
                                $.each(messages, function (index, message) {
                                    errs += message + '<br>'; // Append each message
                                });
                            });
                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                    }
                    else
                    {
                            toastr.error("An unexpected error occurred.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                    }
                }
            });
        }
    });
    $(document).on("click", ".QuotaSlot", function() {
        var emp_id = $(this).data("emp_id");
        var flag = $(this).data("flag");
        $("#QuotaSlot_emp_id").val(emp_id);
        $("#QuotaSlot_flag").val(flag);
        $("#quotaslot-modal").modal('show');
    });
    $(document).on("submit", "#VisaQuotaslot", function() 
    {
        if (!$(this).parsley().isValid()) {
            return false; // Prevent form submission if validation fails
        }

        var formData = $(this).serialize();
        $.ajax({
            url: "{{ route('resorts.visa.renewal.UploadQuotaSlot') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    $("#quotaslot-modal").modal('hide');
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });


                    $("#Updated_Employee_details").html(''); // Clear the previous employee details
                    $("#Visa_select_emp").val('').trigger('change'); // Reset the select2 dropdown
                 
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                if (response.status === 422) {
                    var errors = response.responseJSON.errors; // Access error object
                    var errs = '';
                    $.each(errors, function(field, messages) {
                        $.each(messages, function(index, message) {
                            errs += message + '<br>'; // Append each message
                        });
                    });
                    toastr.error(errs, "Validation Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
        return false; // Prevent default form submission

    });

});
</script>
@endsection
