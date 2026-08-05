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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>VISA MANAGEMENT</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 ">
                        <div class="col-lg">
                            <div class="empDetails-user">
                                <div class="img-circle"><img src="{{$Employee->profilePic}}" alt="user">
                                </div>
                                <div>
                                    <h4>{{$Employee->resortAdmin->first_name}}  {{$Employee->resortAdmin->last_name}} <span class="badge badge-themeNew">{{$Employee->Emp_id}}</span></h4>
                                    <p>{{$Employee->position->position_title}}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto">
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn eb-btn-secondary btn-sm EditXpatDetails @if(Common::checkRouteWisePermission('resort.visa.xpactEmployee',config('settings.resort_permissions.edit')) == false) d-none @endif">Edit Expiry Dates</a>
                                {{-- <ul class="employee-details-nav">
                                    <li>Slot Reference: #SR12345</li>
                                    <li>Payment Type: {{$QuotaSlotRenewal->PaymentType ?? '-'}}</li>
                                </ul> --}}
                                    <!-- @php
                                        if ($Employee->status == 'Active') 
                                        {
                                            $statusClass = 'badge badge-success';
                                            $activeStatus = 'Active';
                                        }
                                        elseif($Employee->status == 'InActive')
                                        {
                                            $activeStatus = 'In Active';
                                            $statusClass = 'badge badge-infoBorder';
                                        }
                                        else
                                        {
                                            $activeStatus = $Employee->status;
                                            $statusClass = 'badge badge-secondary';
                                        }
                                        @endphp
                                <span class="{{$statusClass}}">{{$activeStatus}}</span> -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-lg-4 g-3 mb-4">
                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-sm-6">
                        <div class="empDetail-block XpatDetail-box">
                            <div>
                                <h6>Passport Expiry</h6>
                                @if(!empty($passportExpiryDate))
                                    <strong>{{ $passportExpiryDate }}</strong>
                                    <span class="text-danger">{{ $passportExpiryStatus }}</span>
                                @else
                                    <strong>N/A</strong>
                                    <span class="text-danger">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>
                 
                        <div class="col-xxl-2 col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block XpatDetail-box">
                                <div>
                                    <h6>Visa Expiry</h6>

                                        @if(!empty(optional($statisctic_emp_header)->VisaExpiryDate))
                                            <strong>{{$statisctic_emp_header->VisaExpiryDate}}</strong>
                                            <span class="text-danger">{{$statisctic_emp_header->VisaRemingDays}} </span>
                                        @else
                                            <strong>N/A</strong>
                                            <span class="text-danger">N/A</span>
                                        @endif
                                </div>
                            </div>
                        </div>
                  
                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-sm-6">
                        <div class="empDetail-block XpatDetail-box">
                            <div>
                                <h6>Work Permit Fee Expiry</h6>


                                @if(!empty(optional($statisctic_emp_header)->WorkPermitExpiryDate))
                                    <strong>{{$statisctic_emp_header->WorkPermitExpiryDate}}</strong>
                                    <span class="text-danger">{{$statisctic_emp_header->WorkPermitRemingDays}} </span>
                                @else
                                    <strong>N/A</strong>
                                    <span class="text-danger">N/A</span>
                                @endif
                            </div>

                        </div>
                    </div>
                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-sm-6">
                        <div class="empDetail-block XpatDetail-box">
                            <div>
                                <h6>Slot Payment Expiry</h6>
                                @if(isset($QuotaSlotRenewal) )

                                    <strong>{{$QuotaSlotRenewal->QuotaslotExpiryDate}}</strong>
                                    <span class="text-danger">{{$QuotaSlotRenewal->QuotaslotRemingDays}} </span>
                                @else
                                    <strong>N/A</strong>
                                    <span class="text-danger">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-sm-6">
                        <div class="empDetail-block XpatDetail-box">
                            <div>
                                <h6>Insurance Expiry</h6>

                                @if(!empty(optional($statisctic_emp_header)->InsuranceExpiryDate))

                                    <strong>{{$statisctic_emp_header->InsuranceExpiryDate}}</strong>
                                    <span class="text-danger">{{$statisctic_emp_header->InsuranceRemingDays}} </span>
                                @else
                                    <strong>N/A</strong>
                                    <span class="text-danger">N/A</span>
                                @endif
                  
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-sm-6">
                        <div class="empDetail-block XpatDetail-box">
                            <div>
                                <h6>Medical Expiry</h6>
                                @if(!empty($medicalExpiryDate))
                                    <strong>{{ $medicalExpiryDate }}</strong>
                                    <span class="text-danger">{{ $medicalExpiryStatus }}</span>
                                @else
                                    <strong>N/A</strong>
                                    <span class="text-danger">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>

                   
                </div>
                <div class="XpatDetail-documents-box mb-4">
                    <div class="card-title">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <h3>Documents</h3>
                            </div>
                            
                            <div class="col-auto"><a href="javascript:void(0)" class="VisaFileUpload btn eb-btn-accent btn-sm @if(Common::checkRouteWisePermission('resort.visa.xpactEmployee',config('settings.resort_permissions.create')) == false) d-none @endif">Upload Document</a>
                            </div>
                        </div>
                    </div>
                    <div class="row  AppendEmployeeDcoument gx-2">
                        @if($VisaEmployeeExpiryData->isNotEmpty())
                            @foreach($VisaEmployeeExpiryData as $VisaEmployeeExpiry)
                                {{-- Only rows backed by an actual uploaded FILE are real documents.
                                     Edit-Expiry writes date-only blobs (Passport_Copy / Work_Permit_Entry_Pass
                                     with no File_child_id) — those must NOT appear as documents. --}}
                                @if($VisaEmployeeExpiry->DocName !="Other" && !empty($VisaEmployeeExpiry->File_child_id))
                                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                        <div class="d-flex align-items-top justify-content-between XpatDetail-Documents-block">
                                            <div>
                                                <h6>{{$VisaEmployeeExpiry->DocName}}</h6>
                                                <span>Uploaded: {{$VisaEmployeeExpiry->lastUploadedFile}}</span>
                                            </div>
                                            <a target="_blank" href="javascript:void(0)" class="btn-tableIcon btnIcon-blue download-link" title="Download" data-id="{{$VisaEmployeeExpiry->child_id}}">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif

                    </div>
                </div>
                <div class="XpatDetail-documents-box XpatDet-paySchedule mb-4">
                    <div class="card-title">
                        <h3>Payment Schedule</h3>
                    </div>
                    <input type="hidden" name="employee_id" id="employee_id" value="{{base64_encode($Employee->id)}}">
                    <div class="row  gx-2">
                        <ul class="nav nav-tabs " id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active Quota_Slot_Fee" data-flag="Quota_Slot_Fee"  id="tab1" data-bs-toggle="tab"
                                    data-bs-target="#Quota_Slot_Fee"  type="button" role="tab" aria-controls="Quota_Slot_Fee"
                                    aria-selected="false">
                                    Quota Slot Fee</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link Work_Permit_Fee" data-flag="Work_Permit_Fee" id="#tab2" data-bs-toggle="tab" data-bs-target="#Work_Permit_Fee"
                                    type="button" role="tab" aria-controls="Work_Permit_Fee" aria-selected="false">Work Permit
                                    Fee</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="Quota_Slot_Fee" role="tabpanel" aria-labelledby="tab1">
                                <table id="Quota_Slot_Fee_table" class="table">
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

                                    </tbody>
                                </table>
                                <div class="listing-box badge-themeNew1 border-0">
                                    <ul class="nav ">
                                        <li>Total Amount (Payable):</li>
                                        <li>Amount Paid: </li>
                                        <li>Balance Amount: </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="Work_Permit_Fee" role="tabpanel" aria-labelledby="tab2">
                                <table id="Work_Permit_Fee_table" class="table Dep-ref-reqtable w-100 ">
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
                                       
                                    </tbody>
                                </table>
                                <div class="listing-box badge-themeNew1 border-0">
                                    <ul class="nav ">
                                        <li>Total Amount (Payable): </li>
                                        <li>Amount Paid: </li>
                                        <li>Balance Amount: </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="XpatDetail-documents-box XpatDet-paySchedule mb-4">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-2 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Past Transaction History</h3>
                            </div>
                            <div class="col-6">
                                <div class="row justify-content-end g-md-3 g-2 align-items-center">
                                    <div class="col-xl-3 col-md-4 col-sm-4 col-6">
                                        <select class="form-select" id="SelectYear" name="SelectYear">
                                            <option value="ALL"> All Year</option>
                                           <?php
                                            $Years = range(date('Y') + 1, date('Y') - 14);
                                            array_multisort($Years, SORT_DESC);
                                            ?>
                                            @foreach($Years as $year)
                                                <option value="{{$year}}">{{$year}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <table id="PastTransectionData" class="table Dep-ref-reqtable w-100 ">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Transaction Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Receipt No.</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                        <tfoot>
                           
                        </tfoot>
                    </table>
                </div>
                <div class="XpatDetail-documents-box XpatDet-paySchedule mb-4">
                    <div class="card-title">
                        <h3>Total Expenses Since Wisdom AI</h3>
                    </div>
                    <div class="row g-lg-4 g-3 mb-4">
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block expenses-joining-box d-block">
                                <div>
                                    <h6>Deposit Payment</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>MVR {{ number_format($TotalExpensessSinceJoing['totalDepositAmount'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block expenses-joining-box d-block">
                                <div>
                                    <h6>Total Work Permit Fee</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>MVR {{ number_format($TotalExpensessSinceJoing['totalWorkPermitAmount'], 2) }}</strong>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block expenses-joining-box d-block">
                                <div>
                                    <h6>Total Slot Payment</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>MVR {{ number_format($TotalExpensessSinceJoing['totalQuotaSlotPayment'], 2) }}</strong>
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block expenses-joining-box d-block">
                                <div>
                                    <h6>Total Medical Insurance</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>MVR {{ number_format($TotalExpensessSinceJoing['totalInsurancePayment'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block expenses-joining-box d-block">
                                <div>
                                    <h6>Total Work Permit Medical Payment</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>MVR {{ number_format($TotalExpensessSinceJoing['totalWorkPermitMedicalFeePayment'], 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="empDetail-block expenses-joining-box d-block">
                                <div>
                                    <h6>Visa Payment</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>{!! Common::formatMvr($TotalExpensessSinceJoing['totalWorkPermitMedicalFeePayment']) !!}</strong>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                </div>
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

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="Receipt_number" class="form-label">Receipt Number</label>
                                <input type="text" class="form-control" id="Receipt_number" name="Receipt_number"
                                    placeholder="Enter Receipt Number"
                                    data-parsley-errors-container="#div-Receipt_number">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                        <button type="submit" class="btn eb-btn-primary FileUploadButton" href="javascript:void(0)">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <div class="modal fade" id="VisaModuleFileUpload-modal" tabindex="-1" aria-labelledby="quotaslot-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Upload Document  </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="VisaDocumentUpload" data-parsley-validate>
                    @csrf
                    <div class="modal-body">

                        <div class="row mb-3">
                            <div class="mt-3 mb-3">
                                <input type="hidden" name="emp_id" id="upload_emp_id" class="form-control" value="{{base64_encode($Employee->id)}}">

                                <label for="DocumentType" class="form-label">Document Type <span class="red-mark">*</span></label>
                                <select class="form-select" id="DocumentType" name="DocumentType" required 
                                    data-parsley-required-message="Please select document type." 
                                    data-parsley-errors-container="#div-DocumentType">
                                    <option value="">Select Document Type</option>
                                    <option value="Insurance">Insurance</option>
                                    <option value="Passport_Copy">Passport Copy</option>
                                    <option value="Work_Permit_Entry_Pass">Work Permit Entry Pass</option>
                                    <option value="Visa">Visa</option>
                                    <option value="Work_Permit_Card">Work Permit Card</option>
                                    <option value="Medical_Report">Medical Report</option>
                                </select>
                                <div id="div-DocumentType" class="text-danger"></div>
                            </div>

                           <div class="mt-3 mb-3">
                                <label for="DocumentFile" class="form-label">File <span class="red-mark">*</span></label>
                                <input type="file" class="form-control" id="DocumentFile" name="DocumentFile" 
                                    accept=".pdf,.jpg,.jpeg,.png,.heic,.heif,image/heic,image/heif"
                                    required 
                                    data-parsley-required-message="Please upload a document." 
                                    data-parsley-errors-container="#div-DocumentFile"
                                    data-parsley-max-file-size="5" 
                                    data-parsley-max-file-size-message="File size must not exceed 5MB."
                                    data-parsley-fileextension="pdf,jpg,jpeg,png,gif,svg,webp,heic,heif"
                                    data-parsley-fileextension-message="Only PDF, JPG, JPEG, PNG, GIF, SVG, WEBP, HEIC or HEIF files are allowed.">
                                <div id="div-DocumentFile" class="text-danger"></div>
                                <small class="form-text text-muted">Accepted formats: PDF, JPG, JPEG, PNG. Maximum size: 5MB</small>

                                <!-- Preview Area -->
                                <img id="DocumentFilePreviewIMG" style="display:none; max-width:100%; margin-top:10px;" />
                                <iframe id="DocumentFilePreviewPDF" style="display:none; width:100%; height:400px; margin-top:10px;"></iframe>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                        <button type="submit" class="btn eb-btn-primary SeprateFileUploadButton FileUploadButton" href="javascript:void(0)">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Manual edit of expiry dates (when the AI sync couldn't read a field) --}}
    <div class="modal fade" id="EditXpatDetails-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expiry Dates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="EditXpatDetailsForm" data-parsley-validate>
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ base64_encode($Employee->id) }}">
                    <div class="modal-body">
                        <p class="text-muted small mb-3">Enter the correct date for any field the sync couldn't read. Leave a field blank to keep its current value.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Passport Expiry</label><input type="date" name="passport_expiry" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Visa Expiry</label><input type="date" name="visa_expiry" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Insurance Expiry</label><input type="date" name="insurance_expiry" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Medical Expiry</label><input type="date" name="medical_expiry" class="form-control"></div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12"><small class="text-muted">Work Permit Fee — set a start and end date to generate the monthly work-permit-fee schedule (one installment per month, using the configured Work Permit fee).</small></div>
                            <div class="col-md-6"><label class="form-label">Work Permit Start</label><input type="date" name="work_permit_start" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Work Permit End</label><input type="date" name="work_permit_end" class="form-control"></div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12"><small class="text-muted">Slot Payment — set a start and end date to generate the monthly slot-fee schedule (one installment per month, using the configured Quota Slot fee).</small></div>
                            <div class="col-md-6"><label class="form-label">Slot Start</label><input type="date" name="slot_start" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Slot End</label><input type="date" name="slot_end" class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                        <button type="submit" class="btn eb-btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Second Modal (Separate from the First One) -->
    <div class="modal fade" id="bdVisa-iframeModel-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Download File</h5>
                
                    <a href="" class="btn eb-btn-secondary downloadLink" target="_blank"> Download</a>
                
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                    <div class="modal-body">
                    
                            <div class=" ratio ratio-21x9" id="ViewModeOfFiles">

                            </div>
                    
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                    </div>
    
            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection
@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){
    $("#SelectYear").select2({
        placeholder: "Select Year",
        allowClear: true
    });
    $("#DocumentType").select2({
        placeholder: "Select Document Type",
        allowClear: true
    });

     $("#MarkAsReadForm").parsley();
    $("#MarkAsRead").parsley();
     QuotaSlotFeeTable();
     PastTransectionData();

      $("#SelectYear").change(function()
       {
            var selectedYear = $(this).val();
            if (selectedYear)
            {
                PastTransectionData();
            }
        });
        $(".VisaFileUpload").on("click", function() {
            $("#VisaModuleFileUpload-modal").modal('show');
        });

        $("#DocumentFile").on("change", function () {
            const file = this.files[0];
            if (!file)
            {
                $("#DocumentFilePreviewIMG, #DocumentFilePreviewPDF").hide();
                return;
            }
            const fileType = file.type;
            const reader = new FileReader();
            reader.onload = function (e) 
            {
                const fileURL = e.target.result;
                if (fileType === "application/pdf") {
                    $("#DocumentFilePreviewPDF").attr("src", fileURL).show();
                    $("#DocumentFilePreviewIMG").hide();
                }
                else if(fileType.startsWith("image/"))
                {
                    $("#DocumentFilePreviewIMG").attr("src", fileURL).show();
                    $("#DocumentFilePreviewPDF").hide();
                }
                else
                {
                    $("#DocumentFilePreviewIMG, #DocumentFilePreviewPDF").hide();
                    alert("Unsupported file format.");
                }
            };

            reader.readAsDataURL(file);
        });
        $("#VisaDocumentUpload").on("submit", function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var url = "{{route('resort.visa.EmployeeWiseVisaDocumentUpload')}}";

            $(".SeprateFileUploadButton").html("Please Wait Ai Insight's is working dont Refresh Page").prop("disabled",true);
            if ($(this).parsley().isValid()) 
            {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) 
                    {
                        if (response.status == true) 
                        {
                            toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                            $("#VisaModuleFileUpload-modal").modal('hide');
                            $("#DocumentFilePreviewIMG, #DocumentFilePreviewPDF").hide();

                            if(response.data)
                            {
                                let strings='';
                                $.each(response.data, function(index, value) 
                                {
                                    if(value.DocName != "Other" && value.child_id)
                                    {
                                        strings += `<div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                                        <div class="d-flex align-items-top justify-content-between XpatDetail-Documents-block">
                                                            <div>
                                                                <h6>${value.DocName}</h6>
                                                                <span>Uploaded: ${value.lastUploadedFile}</span>
                                                            </div>
                                                            <a target="_blank" href="javascript:void(0)" class="btn-tableIcon btnIcon-blue download-link" title="Download" data-id="${value.child_id}">
                                                                <i class="fa-solid fa-download"></i>
                                                            </a>
                                                        </div>
                                                    </div>`;
                                    }
                                });   
                                $(".AppendEmployeeDcoument").html(strings);
                                $(".SeprateFileUploadButton").html("Submit").prop("disabled",false);

                            }
                        } 
                        else 
                        {
                            toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                            $(".SeprateFileUploadButton").html("Submit").prop("disabled",false);
                        }
                    },
                    error: function(xhr, status, error) 
                    {
                        toastr.error("An error occurred while uploading the document.", "Error", { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });

  
});

    $(document).on("click", ".download-link", function(e) {
        e.preventDefault();
        var childId = $(this).data('id');
        var $downloadLink = $(this);

        // First, set a loading message
        $("#ViewModeOfFiles").html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        // Show the modal with the loading message
        $("#bdVisa-iframeModel-modal-lg").modal('show');
        
        $.ajax({
            url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
            type: 'GET',
            data: { child_id: childId, "_token":"{{csrf_token()}}"},
            success: function(response) 
            {
                let fileUrl = response.NewURLshow;
                $(".downloadLink").attr("href", fileUrl);
                
                let mimeType = response.mimeType.toLowerCase();
                let iframeTypes = [
                                    'video/mp4', 'video/quicktime', 'video/x-msvideo', // Videos
                                    'application/pdf', 'text/plain',                   // PDF & Text
                                    'application/msword', 'application/vnd.ms-excel'   // Word & Excel
                                ];
                let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
                // Clear the loading message and show the actual content
                if (imageTypes.includes(mimeType)) 
                {
                    $("#ViewModeOfFiles").html(`
                        <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">`);
                } 
                // If file type is supported for iframe display
                else if (iframeTypes.includes(mimeType)) {
                    $("#ViewModeOfFiles").html(`
                        <iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>
                    `);
                } 
                // If file is a ZIP or unsupported type → Download it
                else {
                    $("#bdVisa-iframeModel-modal-lg").modal('hide');
                    window.location.href = fileUrl; // Triggers download automatically
                }
            },
            error: function(xhr, status, error) 
            {
                $("#bdVisa-iframeModel-modal-lg").modal('hide');
                toastr.error("An error occurred while downloading the file.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });
$(document).on("click",".Quota_Slot_Fee , .Work_Permit_Fee",function()
{
    QuotaSlotFeeTable();
});

// $(document).on("click",".Work_Permit_Fee",function()
// {
//   PastTransectionData()
// });


function QuotaSlotFeeTable()
{   
    var flag = $('.nav-link.active').data('flag');


    if(flag =="Quota_Slot_Fee")
    {
        if($.fn.DataTable.isDataTable('#Quota_Slot_Fee_table'))
        {
            $('#Quota_Slot_Fee_table').DataTable().destroy();
        }
        var productTable = $('#Quota_Slot_Fee_table').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order: [[6, 'desc']],  
            ajax: 
            {
                url: "{{ route('resort.visa.Quota_Slot_PendingFee') }}",
                type: 'GET',
                data: function(d)
                {
                    d.flag = flag,
                    d.employee_id = $("#employee_id").val();
                }
            },
            columns: [
                    { data: 'Month', name: 'Month' },
                    { data: 'Amount', name: 'Amount' },
                    { data: 'DueDate', name: 'DueDate' },
                    { data: 'Status', name: 'Status' },
                    { data: 'PaymentDate', name: 'PaymentDate' },
                    { data: 'Action', name: 'Action' },
                    {data:'created_at', visible:false,searchable:false},

            ]
        });

    }

    if(flag =="Work_Permit_Fee")
    {
        if($.fn.DataTable.isDataTable('#Work_Permit_Fee_table'))
        {
            $('#Work_Permit_Fee_table').DataTable().destroy();
        }
        var productTable = $('#Work_Permit_Fee_table').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order: [[6, 'desc']],
            ajax: 
            {
                url: "{{ route('resort.visa.Quota_Slot_PendingFee') }}",
                type: 'GET',
                data: function(d)
                {
                    d.flag = flag,
                    d.employee_id = $("#employee_id").val();
                }
            },
            columns: [
                    { data: 'Month', name: 'Month' },
                    { data: 'Amount', name: 'Amount' },
                    { data: 'DueDate', name: 'DueDate' },
                    { data: 'Status', name: 'Status' },
                    { data: 'PaymentDate', name: 'PaymentDate' },
                    { data: 'Action', name: 'Action' },
                    {data:'created_at', visible:false,searchable:false},

            ]
        });

    }

        productTable.on('xhr', function(e, settings, json){
            if(json && json.footerData){
                // Scope to the active tab's listing-box — `$('.listing-box ul')`
                // matched BOTH tab panes, so the Quota Slot totals overwrote the
                // Work Permit totals and vice versa.
                var paneSelector = (flag == "Quota_Slot_Fee") ? '#Quota_Slot_Fee' : '#Work_Permit_Fee';
                $(paneSelector + ' .listing-box ul').html(`
                    <li>Total Amount (Payable): ${json.footerData.PayableAmount}</li>
                    <li>Amount Paid: ${json.footerData.AmountPaid}</li>
                    <li>Balance Amount: ${json.footerData.BalanceAmount}</li>
                `);
            }
    });
}
$(document).on("click",".markasPaid",function()
{
    let id= $(this).data('id');
    let type= $(this).data('type');
    $("#TypeofModel").val(type);
    $("#Mark_id").val(id);
    $("#MarkasRead-modal").modal('show');
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
    
                toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                // Refresh the schedule (active tab) AND the transaction history in
                // real time so the new payment shows without a page reload.
                QuotaSlotFeeTable();
                PastTransectionData();
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


function PastTransectionData()
{
    
    if($.fn.DataTable.isDataTable('#PastTransectionData'))
        {
            $('#PastTransectionData').DataTable().destroy();
        }

        var productTable = $('#PastTransectionData').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order:[[7, 'desc']],
            ajax: 
            {
                url: "{{ route('resort.visa.PastTransectionHistory') }}",
                type: 'GET',
                data: function(d) 
                {
                    d.employee_id = $("#employee_id").val();
                                        d.SelectYear = $("#SelectYear").val();

                }
            },
            columns: [
                    { data: 'Year', name: 'Year' },
                    { data: 'TransactionType', name: 'TransactionType' },
                    { data: 'Amount', name: 'Amount' },
                    { data: 'Date', name: 'Date' },
                    { data: 'ReceiptNo', name: 'ReceiptNo' },
                    { data: 'Status', name: 'Status' },
                    {data:'created_at', visible:false,searchable:false},
        
            ]
        });
}

$(document).on("click", ".EditXpatDetails", function () {
    $("#EditXpatDetails-modal").modal('show');
});
$("#EditXpatDetailsForm").on("submit", function (e) {
    e.preventDefault();
    var $btn = $(this).find('button[type=submit]').prop('disabled', true);
    $.ajax({
        url: "{{ route('resort.visa.UpdateXpatDetails') }}",
        type: "POST",
        data: $(this).serialize()
    }).done(function (res) {
        if (res && res.status) {
            toastr.success(res.message || 'Updated.', "Success", { positionClass: 'toast-bottom-right' });
            setTimeout(function () { location.reload(); }, 800);
        } else {
            toastr.error((res && res.message) || 'Update failed.', "Error", { positionClass: 'toast-bottom-right' });
        }
    }).fail(function (xhr) {
        var m = 'Update failed.';
        try { m = xhr.responseJSON.message || m; } catch (e) {}
        toastr.error(m, "Error", { positionClass: 'toast-bottom-right' });
    }).always(function () { $btn.prop('disabled', false); });
});
</script>
@endsection