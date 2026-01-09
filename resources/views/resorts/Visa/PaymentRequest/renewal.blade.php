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
                            <h1>{{$page_title}} {{$PaymentRequest_id}}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-12">
                    <div class="card payment-request-renewal-card">
                        <div class="card-header">
                            <div class="row g-md-3 g-2 ">
                                <div class="col-lg">
                                    <div class="empDetails-user">
                                        <div class="img-circle">
                                            <img src=" {{$Employees->ProfilePic ?? '' }}" alt="user">
                                        </div>
                                        <div>
                                            <h4>{{$Employees->Emp_name ?? '' }} <span class="badge badge-themeNew">{{$Employees->Emp_id ?? '' }} </span></h4>
                                            <p>{{$Employees->Department_name ?? '' }} - {{$Employees->Position_name ?? '' }} </p>
                                        </div>
                                       
                                    </div>
                                </div>


                            </div>
                        </div>
                        <div class="row g-md-4 g-3  ">
                            @if($child->InsuranceShow =="yes")
                                <div class="col-xl-4 InsuranceShow"> 
                                    <div class="renewal-box ">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Insurance Renewal</h3>
                                                    <span>Current Policy Expires: {{$EmployeeInsurance->insurance_end_date ?? 'N/A'}}</span>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end"><span    class="badge badge-themeWarning">Expires  in 30days</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row  gx-2">
                                            <div class="col-xxl-6 col-xl-12 col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Policy Number</label>
                                                    <p>Policy Number:  {{$EmployeeInsurance->insurance_policy_number ?? 'N/A'}}</p>
                                                </div>
                                                <div class="renewal-innerbox">
                                                    <label>Insurance company name</label>
                                                    <p> fdsdff {{ (isset($EmployeeInsurance->insurance_company)) ? $EmployeeInsurance->insurance_company : 'N/A'}}</p>
                                                </div>
                                            <div class="col-sm-6">
                                                    <div class="renewal-innerbox">
                                                        <label>Premium</label>
                                                        <p>MVR {{$EmployeeInsurance->cost ?? 'N/A'}}</p>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="col-xxl-6 col-xl-12 col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Policy Number</label>
                                                    <p>Policy Number: {{$EmployeeInsurance->cost }}</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <a href="javascript:void(0)" data-child="{{base64_encode($child->id)}}" data-emp_id="{{base64_encode($child->Employee_id)}}" data-flag="insurance" class="SendFile btn btn-themeBlue btn-sm">
                                                    Upload The New Insurance Policy
                                                </a>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if($child->MedicalReportShow =="yes")
                                <div class="col-xl-4 MedicalReportShow">
                                    <div class="renewal-box ">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Work Permit Medical Test Fee Renewal</h3>
                                                    <span>Last Test Date: {{$WorkPermitMedicalRenewal->medical_end_date ?? ''}}</span>
                                                </div>
                                                <div class="col-sm-auto col-12 order-sm-last order-1 text-end"><span
                                                        class="badge badge-themeWarning">{{$WorkPermitMedicalRenewal->MedicalRenewalTime ?? ''}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row  gx-2">
                                            <div class="col-xxl-6 col-xl-12 col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Reference Number</label>
                                                    <p>{{$WorkPermitMedicalRenewal->Reference_Number ?? ''}}</p>
                                                </div>
                                                <div class="renewal-innerbox">
                                                    <label>Medical Center name</label>
                                                    <p>{{$WorkPermitMedicalRenewal->Medical_Center_name ?? ''}}</p>
                                                </div>

                                            </div>
                                            <div class="col-xxl-6 col-xl-12 col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Test Cost</label>
                                                    <p> {{$WorkPermitMedicalRenewal->workpermitcost ?? ''}}</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <a  href="javascript:void(0)"   data-child="{{base64_encode($child->id)}}" data-emp_id="{{base64_encode($child->Employee_id)}}" data-flag="work_permit_card_Test_Fee" class="SendFile btn btn-themeBlue btn-sm">
                                                    Upload The New Work Permit
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if($child->VisaShow =="yes")
                                <div class="col-xl-4 VisaShow">
                                    <div class="renewal-box ">
                                        <div class="card-title">
                                            <div class="row g-2 ">
                                                <div class="col order-sm-1 order-last">
                                                    <h3>Visa Renewal</h3>
                                                    <span>Current Policy Expires: {{$VisaRenewal->end_date}}</span>
                                                </div>
                                                <div class="col-auto col-sm-auto col-12 order-sm-last order-1 text-end">
                                                    <span class="badge badge-themeWarning">{{$VisaRenewal->end_date}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row  gx-2">
                                            <div class="col-xxl-6 col-xl-12 col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Visa Number</label>
                                                    <p>Policy Number:{{$VisaRenewal->Visa_Number ?? 'N/A'}}</p>
                                                </div>
                                                <div class="renewal-innerbox">
                                                    <label>WP No</label>
                                                    <p>{{$VisaRenewal->WP_No ?? 'N/A'}}</p>
                                                </div>

                                            </div>
                                            <div class="col-xxl-6 col-xl-12 col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Validity</label>
                                                    <p>{{$VisaRenewal->Validitydate ?? 'N/A'}}</p>
                                                </div>
                                            </div>
                                             <div class="col-sm-6">
                                                <div class="renewal-innerbox">
                                                    <label>Cost</label>
                                                    <p>{{$VisaRenewal->Amt ?? 'N/A'}}</p>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <a href="javascript:void(0)" data-child="{{base64_encode($child->id)}}"  data-emp_id="{{base64_encode($child->Employee_id)}}" data-flag="visa"  class="SendFile btn btn-themeBlue btn-sm">
                                                    Upload The New Visa Renewal
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif



                        </div>
                    </div>
                </div>
                @if($child->QuotaslotShow =="yes" &&isset($QuotaSlotVariable))
                    <div class="col-lg-6 QuotaslotShow">

                        <div class="card payment-request-renewal-card XpatDet-paySchedule ">
                            <div class="card-title">
                                <div class="row g-md-2 g-1 align-items-center">
                                    <div class="col">
                                        <h3 class="text-nowrap">
                                            Quota Slot Fee</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="a-link">View All</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="" class="table Dep-ref-reqtable w-100 ">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Payment Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($QuotaSlotVariable)
                                            <tr>
                                                <th>{{$QuotaSlotVariable->Month}}</th>
                                                <td>MVR {{ number_format($QuotaSlotVariable->Amt,2)}}</td>    
                                                <td>{{$QuotaSlotVariable->Due_Date}}</td>
                                                <td>
                                                    <span class="badge badge-themeDanger">Pending</span>
                                                </td>
                                                <td>{{$QuotaSlotVariable->PaymentDate?? '-'}}</td>
                                                <td>
                                                    <a target="_blank" data-id="{{base64_encode($QuotaSlotVariable->id)}}" data-child="{{base64_encode($child->id)}}" data-type="QuotaSlot"  class="a-link markasPaid">Mark as Paid</a>
                                                </td>
                                            
                                            </tr>
                                        @endif
                                            
                                    

                                    </tbody>
                                </table>
                            </div>
                            <div class="listing-box badge-themeNew1 border-0">
                                <ul class="nav ">
                                    <li>Total Amount (Payable): MVR {{ number_format($QuotaSlotPayableAmt,2)}}</li>
                                    <li>Amount Paid: MVR {{ number_format($QuotaSlotPaidAmt,2)}}</li>
                                    <li>Balance Amount: {{ number_format($QuotaSlotUnPaidAmt,2)}}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
                @if($child->WorkPermitShow =="yes" && isset($WorkPermitCommonVariable))
                    <div class="col-lg-6 WorkPermitShow">

                        <div class="card payment-request-renewal-card XpatDet-paySchedule">
                            <div class="card-title">
                                <div class="row g-md-2 g-1 align-items-center">
                                    <div class="col">
                                        <h3 class="text-nowrap">Work Permit Fee</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="a-link">View All</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="" class="table Dep-ref-reqtable w-100 ">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Payment Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th>{{$WorkPermitCommonVariable->Month}}</th>
                                            <td>MVR {{ number_format($WorkPermitCommonVariable->Amt,2)}}</td>    
                                            <td>{{$WorkPermitCommonVariable->Due_Date}}</td>
                                            <td>
                                                    <span class="badge badge-themeDanger">Pending</span>
                                            </td>
                                                <td>{{$WorkPermitCommonVariable->PaymentDate ?? '-'}}</td>
                                            <td>
                                                <a target="_blank" data-id="{{base64_encode($WorkPermitCommonVariable->id)}}" data-child="{{base64_encode($child->id)}}" data-type="WorkPermit"  class="a-link markasPaid">Mark as Paid</a>
                                            </td>
                                        
                                        </tr>
                                    

                                    </tbody>
                                </table>
                            </div>
                            <div class="listing-box badge-themeNew1 border-0">
                                <ul class="nav ">
                                    <li>Total Amount (Payable): MVR {{ number_format($WorkPermitPayableAmt,2)}}</li>
                                    <li>Amount Paid: MVR {{ number_format($WorkPermitPaidAmt,2)}}</li>
                                    <li>Balance Amount: {{ number_format($WorkPermitUnPaidAmt,2)}}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
                <!-- <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a></div> -->
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
                            <input type="hidden" name="child_id" id="child_id" value="">

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
        <div class="modal fade" id="MarkasRead-modal" tabindex="-1" aria-labelledby="MarkasRead-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Payment Type </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="MarkAsReadForm" data-parsley-validate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="Mark_id" id="Mark_id" class="form-control" placeholder="Enter Employee ID">
                        <input type="hidden" name="TypeofModel" id="TypeofModel" class="form-control" placeholder="Enter Flag">
                        <input type="hidden" name="child_id" id="Mark_child_id" class="form-control" placeholder="Enter child_id">

                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="Receipt_number" class="form-label">Receipt Number <span class="red-mark">*</label>
                                <input type="text" class="form-control" id="Receipt_number" name="Receipt_number"
                                    placeholder="Enter Receipt Number" required data-parsley-required-message="Please enter receipt number."
                                    data-parsley-errors-container="#div-Receipt_number">
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
$(document).ready(function(){
  $("#MarkAsReadForm").parsley();
    $(document).on("click",".markasPaid",function()
    {
        let id     = $(this).data('id');
        let type   = $(this).data('type');
        let child  = $(this).data('child');
        $("#TypeofModel").val(type);
        $("#Mark_id").val(id);
        $("#Mark_child_id").val(child);
        $("#MarkasRead-modal").modal('show');
    });
    $("#file").on("change", function() {
        var files = $(this).prop("files");
        var fileNames = $.map(files, function(val) {
            return val.name;
        }).join(", ");
        $("#fileNameDisplay").text("Selected file: " + fileNames);
    });
     $(document).on("click", ".SendFile", function() {
        var flag = $(this).data("flag");
        var emp_id = $(this).data("emp_id");
        var child_id = $(this).data("child");

        $("#selectFileForVisa-modal").modal('show');
        $("#emp_id").val(emp_id);
        $("#child_id").val(child_id);
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
                        var flag = $("#flag").val();
                        if (flag == "insurance") 
                        {
                               $(".InsuranceShow").hide();
                        }
                        else if (flag == "work_permit_card_Test_Fee") 
                        {
                            $(".MedicalReportShow").hide();
                        } 
                        else if (flag == "visa") 
                        {
                            $(".VisaShow").hide();
                        }
                     
                       
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

    $(document).on("submit", "#MarkAsReadForm", function(e) {
        e.preventDefault();
        var dataString = $(this).serialize();
        var url = "{{route('resort.visa.Quota_Slot_MakrasPaid')}}";
        if ($(this).parsley().isValid()) 
        {
            $.post(url, dataString, function(response) 
            {
                if (response.status == true) 
                {
                    if("QuotaSlot" == $("#TypeofModel").val())
                    {
                        $(".QuotaslotShow").hide();
                    }
                    if("WorkPermit" == $("#TypeofModel").val())
                    {
                        $(".WorkPermitShow").hide();
                    }
                    
                    $("#MarkasRead-modal").modal('hide');
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    QuotaSlotFeeTable();
                } 
                else 
                {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
                $("#MarkasRead-modal").modal('hide');
                    $("#MarkAsReadForm")[0].reset();
            });
        }
    });
});
</script>
@endsection