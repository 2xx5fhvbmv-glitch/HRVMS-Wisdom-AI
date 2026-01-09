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
            <div class="row g-4">
                <div class="col-lg-6 ">
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <h3>Deposit Rate</h3>
                                </div>
                                <div class="col-auto ms-auto"><a href="#UploadList-modal" data-bs-toggle="modal"  class="UploadList a-link">Upload List</a></div>
                                <div class="col-auto "><a href="{{route('resort.visa.NationalityIndex')}}" class="a-link">View Existing</a></div>

                            </div>
                        </div>
                        <form id="NationalityForm" data-parsley-validate>
                            @csrf
                            <div class="incidentCategories-main">
                                <div class="incidentCategories-block AppendNationalityRow">
                                    <div class="row g-2 mb-md-4 mb-3">
                                        <div class="col-sm-6">
                                            <label for="txt-nationality" class="form-label">NATIONALITY <span class="red-mark">*</span></label>
                                                <select class="form-select" name="nationality[]" id="nationality_1"
                                                    aria-label="Default select example"
                                                    required 
                                                    data-parsley-required-message="Please select a nationality"
                                                    data-parsley-errors-container="#nationality_error_1">
                                                    <option value=""> </option>
                                                    @if(!empty($nationality))
                                                        @foreach ($nationality as $item)
                                                            <option value="{{ $item }}">{{ $item }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <div id="nationality_error_1" class="text-danger mt-1"></div>
                                        </div>

                                        <div class="col-sm-6">
                                            <label for="txt-amount" class="form-label">AMOUNT (MVR) <span class="red-mark">*</span></label>
                                            <input type="number" min="0" name="amt[]"   id="txt-amount_1" class="form-control" placeholder="Amount" required data-parsley-type="number" data-parsley-required-message="Please enter an amount">
                                        </div>
                                        <input type="hidden" name="id" id="NationalityCount" value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="javascript::void(0)" class="btn btn-themeSkyblue btn-sm blockAdd-Nationality">Add Nationality</a>
                                <button type="submit" class="btn btn-themeBlue btn-sm SubmitNationality">Submit</button>
                            </div>
                        </form>

                    </div>
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Expiry Date</h3>
                        </div>
                        <form id="VisaReminderForm"  data-parsley-validate>
                            @csrf
                            <div class="VisaMan-Configuration-box">
                                <div class="d-flex align-items-center justify-content-between mb-lg-4 mb-3">
                                    <h4>Work Permit Fee</h4>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch" name="Work_Permit_Fee_reminder"  @if(isset($visaReminder) && $visaReminder->Work_Permit_Fee_reminder=="Active") checked @endif  id="Work_Permit_Fee_reminder" >
                                        <label class="form-check-label" for="flexSwitchCheckDefaultnew">Enabled</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txt-work-permit-fee" class="form-label">REMINDER DAYS (BEFORE EXPIRY)</label>
                                        <div class="flotting-text position-relative">
                                            <input type="number" min="0" id="Work_Permit_Fee" 
                                            data-parsley-required="true"
                                            @if(isset($visaReminder) && $visaReminder->Work_Permit_Fee_reminder=="Active") value="{{ $visaReminder->Work_Permit_Fee}}" @endif
                                            data-parsley-required-message="Enter reminder days for Work slot "
                                            disabled name="Work_Permit_Fee" class="form-control"  placeholder="7 Days">
                                            <span class="">Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="VisaMan-Configuration-box">
                                <div class="d-flex align-items-center justify-content-between mb-lg-4 mb-3">
                                    <h4>Slot Fee</h4>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox"    @if(isset($visaReminder) && $visaReminder->Slot_Fee_reminder=="Active") checked @endif    name="Slot_Fee_reminder"  role="switch" id="Slot_Fee_reminder" >
                                        <label class="form-check-label" for="Slot_Fee_reminder">Enabled</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txt-slot-Fee" class="form-label">REMINDER DAYS (BEFORE EXPIRY)</label>
                                        <div class="flotting-text position-relative">
                                            <input type="number" min="0"         
                                             @if(isset($visaReminder) && $visaReminder->Slot_Fee_reminder=="Active") value="{{ $visaReminder->Slot_Fee}}" @endif  id="Slot_Fee" name="Slot_Fee" class="form-control" placeholder="7 Days">
                                            <span class="">Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="VisaMan-Configuration-box">
                                <div class="d-flex align-items-center justify-content-between mb-lg-4 mb-3">
                                    <h4>Insurance</h4>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" name="Insurance_reminder"    @if(isset($visaReminder) && $visaReminder->Insurance_reminder=="Active") checked @endif type="checkbox" role="switch" id="Insurance_reminder">
                                        <label class="form-check-label" for="Insurance_reminder">Enabled</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txt-insurance" class="form-label">REMINDER DAYS (BEFORE EXPIRY)</label>
                                        <div class="flotting-text position-relative">
                                            <input type="number"  
                                            @if(isset($visaReminder) && $visaReminder->Insurance_reminder=="Active") value="{{ $visaReminder->Insurance}}" @endif

                                            min="0" id="insurance" name="Insurance" class="form-control" placeholder="7 Days">
                                            <span class="">Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="VisaMan-Configuration-box">
                                <div class="d-flex align-items-center justify-content-between mb-lg-4 mb-3">
                                    <h4>Medical</h4>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox"  name="Medical_reminder"   @if(isset($visaReminder) && $visaReminder->Medical_reminder=="Active") checked @endif role="switch"  id="Medical_reminder" >
                                        <label class="form-check-label" for="Medical_reminder">Enabled</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txt-medical" class="form-label">REMINDER DAYS (BEFORE EXPIRY)</label>
                                        <div class="flotting-text position-relative">
                                            <input type="number"
                                            @if(isset($visaReminder) && $visaReminder->Medical_reminder=="Active") value="{{ $visaReminder->Medical}}" @endif

                                             min="0" id="txt-medical" name="Medical" class="form-control" placeholder="7 Days">
                                            <span class="">Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="VisaMan-Configuration-box">
                                <div class="d-flex align-items-center justify-content-between mb-lg-4 mb-3">
                                    <h4>Visa</h4>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" name="Visa_reminder" role="switch"  @if(isset($visaReminder) && $visaReminder->Visa_reminder=="Active") checked @endif id="Visa_reminder">
                                        <label class="form-check-label" for="Visa_reminder">Enabled</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txt-visa" class="form-label">REMINDER DAYS (BEFORE EXPIRY)</label>
                                        <div class="flotting-text position-relative">
                                            <input type="number" min="0" id="txt-visa"  
                                            @if(isset($visaReminder) && $visaReminder->Visa_reminder=="Active") value="{{ $visaReminder->Visa}}" @endif

                                            class="form-control"  name="Visa" placeholder="7 Days">
                                            <span class="">Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="VisaMan-Configuration-box">
                                <div class="d-flex align-items-center justify-content-between mb-lg-4 mb-3">
                                    <h4>Passport</h4>
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch"   id="Passport_reminder" @if(isset($visaReminder) && $visaReminder->Passport_reminder=="Active") checked @endif  name="Passport_reminder">
                                        <label class="form-check-label" for="Passport_reminder">Enabled</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="txt-passport" class="form-label">REMINDER DAYS (BEFORE EXPIRY)</label>
                                        <div class="flotting-text position-relative">
                                            <input type="text" id="txt-passport"   
                                            @if(isset($visaReminder) && $visaReminder->Passport_reminder=="Active") value="{{ $visaReminder->Passport}}" @endif

                                            name="Passport" class="form-control" placeholder="7 Days">
                                            <span class="">Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Wallet Type </h3>
                        </div>

                        <div class="WalletType-main">
                            <div class="WalletType-block">
                                <form id="WalletType" data-parsley-validate>
                                    @csrf
                                    <div class="row gx-2 gy-3 mb-md-4 mb-3">
                                        
                                        <div class="col-sm-6">
                                            <label for="txt" class="form-label">WALLET NAME <span class="red-mark">*</span></label>
                                            <input type="text"  name="WalletName" id="WalletName"   data-parsley-required="true" data-parsley-required-message="Wallet Name is required" class="form-control" placeholder="Deposit Wallet">
                                        </div>
                                         <div class="col-sm-6">
                                            <label for="txt" class="form-label">WALLET NAME <span class="red-mark">*</span></label>
                                            <input type="number"  min="1" name="Amt" id="Amt"   data-parsley-required="true" data-parsley-required-message="Wallet Amount is required" class="form-control" placeholder="Amount">
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-themeBlue btn-sm" type="submit">Submit</button>
                                    </div>
                                </form>
                                <hr>
                                <div class="row gx-2 gy-3 mb-md-4 mb-3">
                                    <div class="col-12">
                                            <table id="WalletTypetable" class="table  WalletType w-100">
                                            <thead>
                                                <tr>
                                                    <td>Wallet Name</td>
                                                    <td>Amount</td>
                                                    <td>Action</td>
                                                </tr>
                                            <thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ">
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Fee Amount</h3>
                        </div>
                        <div class="commiAssMem-main">
                        <form id="VisaAmtForm" data-parsley-validate>
                            @csrf 
                            <div class="commiAssMem-block">
                                    <div class="row gx-2 gy-3 mb-md-4 mb-3">
                                        @if($ResortBudgetCost->isNotEmpty())
                                            @foreach($ResortBudgetCost as $c)
                                                <div class="col-sm-6">
                                                    <label for="txt" class="form-label">{{strtoupper($c->particulars)}}</label>
                                                    <div class="position-relative flotting-text">
                                                    <input type="hidden" id="txt" class="form-control" name="id[]" value="{{$c->id}}" placeholder="1000">

                                                        <input type="number" readonly id="txt" class="form-control" value="{{$c->New_Amount}}" name="amount[]"  data-parsley-required-message="Amount is required" data-parsley-type="number" data-parsley-type-message="Please enter a valid number" placeholder="0.00">
                                                        <span>MVR </span>
                                                    </div>
                                              
                                                </div>
                                            @endforeach
                                        @endif
                                    
                                    </div>
                                   <!--  <div class="VisaMan-Configuration-box border-0 mb-0">
                                        <h4>Passport Renewal Cost</h4>
                                    </div>
                                    <div class="row gx-2 gy-3 mb-3">
                                        <div class="col-sm-6">
                                            <label for="txt-nationality1" class="form-label">NATIONALITY</label>
                                            <select class="form-select select2t-none" name="nationality" id="nationalityAmt" data-parsley-required-message="Please select a nationality" aria-label="Default select example">
                                                <option selected >  </option>
                                                @if(!empty($nationality))
                                                    @foreach($nationality as $n)
                                                        <option value="{{$n}}"> {{$n}} </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="txt" class="form-label">AMOUNT BEFORE EXPIRY</label>
                                            <div class="position-relative flotting-text">
                                                <input type="number" id="txt" 
                                                      required
                                                        data-parsley-required-message="Amount before expiry is required"
                                                        data-parsley-type="number"
                                                        data-parsley-type-message="Enter a valid amount" name="AmountbeforExp"  class="form-control" placeholder="1000">
                                                <span>MVR</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="txt" class="form-label">AMOUNT AFTER EXPIRY</label>
                                            <div class="position-relative flotting-text">
                                                <input type="number" id="txt"
                                                    required
                                                    data-parsley-required-message="Amount after expiry is required"
                                                    data-parsley-type="number"
                                                    data-parsley-type-message="Enter a valid amount"
                                                    name="AmountafterExp"  class="form-control" placeholder="1000">
                                                <span>MVR</span>
                                            </div>
                                        </div> 
                                    </div> -->
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <!-- <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button> -->
                            </div>
                        </form>

                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Deposit Refund</h3>
                        </div>
                        <form id="DepositRefundForm" data-parsley-validate>
                            @csrf
                            <div class="row g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <label for="txt" class="form-label">INITIAL REMINDER (DAYS AFTER DEPARTURE) <span class="red-mark">*</span></label>
                                    <div class="position-relative flotting-text">
                                        <input type="text" 
                                        required
                                        data-parsley-required-message="INITIAL REMINDER"
                                        data-parsley-type="number"
                                        data-parsley-type-message="Enter a valid amount"
                                        name="initial_reminder"
                                        value="{{isset($DepositRefound) ?  $DepositRefound->initial_reminder:'' }}"
                                        id="txt" class="form-control" placeholder="1000">
                                        <span>Days</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="txt" class="form-label">FOLLOW-UP REMINDERS (IF NOT APPLIED) <span class="red-mark">*</span></label>
                                    <div class="position-relative flotting-text">
                                        <input type="text"
                                        required
                                         data-parsley-required-message="FOLLOW-UP REMINDERS"
                                         data-parsley-type="number"
                                         
                                        value="{{isset($DepositRefound) ?  $DepositRefound->followup_reminder:'' }}"
                                         data-parsley-type-message="Enter a valid amount"
                                         name="followup_reminder" 
                                         id="txt" class="form-control" placeholder="1000">
                                        <span>Days</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type='submit' class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Add Document Type </h3>
                        </div>

                        <div class="incidentCategories-main">
                            <div class="incidentCategories-block">
                                <form id="DocumentTypeForm" data-parsley-validate>
                                    @csrf
                                    <div class="row gx-2 gy-3 mb-md-4 mb-3">
                                        
                                        <div class="col-sm-6">
                                            <label for="txt" class="form-label">DOCUMENT NAME <span class="red-mark">*</span></label>
                                            <input type="text"  name="documentname" id="txt"   data-parsley-required="true" data-parsley-required-message="Document Name is required" class="form-control" placeholder="Document Name">
                                        </div>
                                    </div>
                                    <div class="card-footer text-end">
                                        <button class="btn btn-themeBlue btn-sm" type="submit">Submit</button>
                                    </div>
                                </form>
                                <hr>
                                <div class="row gx-2 gy-3 mb-md-4 mb-3">
                                    <div class="col-12">
                                            <table id="DocumentType" class="table  DocumentType w-100">
                                            <thead>
                                                <tr>
                                                    <td>Document Name</td>
                                                    <td>Action</td>
                                                </tr>
                                            <thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                       
                    </div>
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Document Segmentation</h3>
                        </div>
                        <form id="DocumentSegmentationForm" data-parsley-validate>
                            <div class="incidentCategories-main">
                                <div class="incidentCategories-block">
                                    <div class="row gx-2 gy-3 mb-md-4 mb-3 ">
                                        <div class="col-sm-6"> 
                                            <label for="txt" class="form-label">DOCUMENT TYPE <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" data-parsley-required-message="Please enter document type"  name="document_id[]" id="VisaDocumentType"
                                                aria-label="Default select example">
                                                <option > </option>
                                                @if($VisaDocumentType->isnotEmpty())
                                                    @foreach($VisaDocumentType as $t)
                                                    <option value="{{$t->id}}">{{$t->documentname}} </option>

                                                    @endforeach
                                                @endif
                                            </select>
                                            
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="txt" class="form-label">DOCUMENT NAME <span class="red-mark">*</span></label>
                                            <input type="text" name="DocumentName[]" required data-parsley-required-message="Please select a Document Type"  id="txt" class="form-control" placeholder="1000">
                                        </div>

                                        
                                    </div>
                                    <div class="AppendDocumenttype">
                                    </div>
                                                                

                                    
                                </div>

                            </div>
                            <input type="hidden" name="documentCount" id="documentCount"  value="1">
                            <div class="card-footer text-end">
                                        <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm AddmoreDocumentType blockAdd-btn">Add More</a>
                                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="UploadList-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-CropImage">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Upload Diposit Rate  </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="NationalityExportForm"  data-parsley-validate enctype="multipart/form-data" >
                    @csrf
                    <div class="modal-body">
                    <div class="row">
                        <div class=col-md-12>
                            <a href="{{route('visa.natioanlity.export')}}"class="btn btn-sm btn-themeSkyblue">Example Export</a>
                        </div>
                        <div class="col-md-12">
                            <label for="txt" class="form-label">UPLOAD DOCUMENT <span class="red-mark">*</span></label>
                            <input type="file"  name="nationality" required data-parsley-required-message="Please Upload File"  class="form-control" accept=".xls,.xlsx">

                            <div id="fileNameDisplay" style="margin-top: 5px; font-weight: 500;"></div>
                        </div>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:vpid(0)" id="close-crop-btn" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Close</a>
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
<script>
$(document).ready(function(){
    
    $('#NationalityForm').parsley(); 
    $('#NationalityExportForm').parsley(); 
    $('#DocumentTypeForm').parsley(); 
    $('#DocumentSegmentationForm').parsley(); 
    $('#WalletType').parsley(); 

    
    DocumentTypeIndex();
    WalletTypetable();
    $("#VisaDocumentType").select2({
        allowClear: true,
        placeholder: "Select Document Type"
    }); $("#nationality_1").select2({
        allowClear: true,
        placeholder: "Select Nationality"
    });
    $("#nationalityAmt").select2({
        allowClear: true,
        placeholder: "Select Nationality"
    });
    
    $('#UploadFile').on('change', function () {
        const file = this.files[0];
        if (file) 
        {
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext === 'xls' || ext === 'xlsx') {
                $('#fileNameDisplay').text(`Selected file: ${file.name}`);
            } else {
                alert('Please select only Excel files (.xls, .xlsx)');
                $(this).val('');
                $('#fileNameDisplay').text('');
            }
        } else  {
            $('#fileNameDisplay').text('');
        }
    });

    $('#VisaReminderForm').parsley();

});

$(document).on("change", "input[type='checkbox'][id$='_reminder']", function () {
    const checkboxId = $(this).attr('id');
    const baseId = checkboxId.replace('_reminder', '');
    // Try finding input by ID or name
    let $input = $('#' + baseId);
    if (!$input.length) {
        $input = $('[name="' + baseId + '"]');
    }

    const isChecked = $(this).is(":checked");

    if (isChecked) {
        $input.prop('disabled', false);
        $input.attr('data-parsley-required', 'true');
    } else {
        $input.prop('disabled', true);
        $input.removeAttr('data-parsley-required');
    }

    $input.parsley().reset();
});

$(function () {
    $("input[type='checkbox'][id$='_reminder']").trigger("change");
});


$(document).on("click",".blockAdd-Nationality",function(){

    var count = $("#NationalityCount").val();
    count= parseInt(count) +1;
    $(".AppendNationalityRow").append(`<div class="row g-2 mb-md-4 mb-3 RemoveNationality_${count}">
                                        <div class="col-sm-4">
                                            <label for="txt-nationality" class="form-label">NATIONALITY<span class="red-mark">*</span></label>
                                            <select class="form-select" name="nationality[]" id="nationality_${count}"
                                                aria-label="Default select example"
                                                required data-parsley-required-message="Please select a nationality"
                                                 data-parsley-required-message="Please select a nationality"
                                                    data-parsley-errors-container="#nationality_error_${count}">
                                                <option value=""> </option>
                                                @if(!empty($nationality))
                                                    @foreach ($nationality as $item)
                                                        <option value="{{$item}}">{{$item}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                              <div id="nationality_error_${count}" class="text-danger mt-1"></div>
                                        </div>
                                        <div class="col-sm-4">
                                            <label for="txt-amount" class="form-label">AMOUNT (MVR) <span class="red-mark">*</span></label>
                                            <input type="number" min="0" name="amt[]" id="txt-amount_${count}" class="form-control" placeholder="Amount" required data-parsley-type="number" data-parsley-required-message="Please enter an amount">
                                        </div>
                                        
                                        <div class="col-sm-2">
                                            <input type="button" value="Remove" style="margin-top:33px;" class="btn btn-danger btn-sm RemoveNationality" data-id="${count}">
                                        </div>
                                        <input type="hidden" name="id" id="NationalityCount" value="1">
                                    </div>`); 
    $("#NationalityCount").val(count);
    
    $("#nationality_"+count).select2({
        allowClear: true,
        placeholder: "Select Nationality"
    });
});
$(document).on("click",".RemoveNationality",function(){
    var id = $(this).data("id");
    $(".RemoveNationality_"+id).remove();
    let count = parseInt(id)-1;
    $("#NationalityCount").val(count);
});
$("#NationalityForm").on("submit", function(e) {
    e.preventDefault(); // Prevent normal form submission

    // Trigger Parsley validation
    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ route('resort.visa.nationality.store') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    // Optional: form[0].reset(); // if you want to clear the form
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
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});


$("#WalletType").on("submit", function(e) {
    e.preventDefault(); // Prevent normal form submission

    // Trigger Parsley validation
    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ route('resort.visa.VisaWalletsStore') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) 
                {
                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    WalletTypetable();  
                }
                else
                {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                console.log(errors);
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});

$("#NationalityExportForm").on("submit", function (e) {
    e.preventDefault();
    var form = this;
    if ($(form).parsley().isValid()) {
        let formData = new FormData(form);
        
        // Log what's in the FormData to confirm the file is included
        console.log("File included:", formData.get("nationality"));
        
        $.ajax({
            url: "{{ route('resort.visa.nationality.Import') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    $("#UploadList-modal").modal('hide');
                } else {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) 
            {
                    var errors = response.responseJSON;
                    var errHtml = '';
                    if (errors && errors.errors) {
                        // Loop through each error object
                        $.each(errors.errors, function(index, errorObj) {
                            if (errorObj.error) {
                                errHtml += 'Row ' + errorObj.row + ': ' + errorObj.error + '<br>';
                            }
                        });

                        toastr.error(errHtml, "Import Errors", {
                            positionClass: 'toast-bottom-right',
                            timeOut: 10000
                        });
                    } else {
                        toastr.error("An unexpected error occurred", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
        });
    }
});
$("#VisaAmtForm").on("submit", function(e) {
    e.preventDefault(); // Prevent normal form submission
    // Trigger Parsley validation
    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ route('resort.visa.VisaAmtForm') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    // Optional: form[0].reset(); // if you want to clear the form
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
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});
$("#DepositRefundForm").on("submit", function(e) {
    e.preventDefault(); // Prevent normal form submission

    // Trigger Parsley validation
    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ url('Visa/DepositRefund') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    // Optional: form[0].reset(); // if you want to clear the form
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
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});

$("#VisaReminderForm").on("submit", function(e) {
    e.preventDefault(); // Prevent normal form submission
    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ route('resort.visa.Reminderalert') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    // Optional: form[0].reset(); // if you want to clear the form
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
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});
$("#DocumentTypeForm").on("submit", function(e) 
{
    e.preventDefault(); // Prevent normal form submission

    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ route('resort.visa.DocumentType') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) 
                {
                    DocumentTypeIndex();
                    $("#DocumentTypeForm")[0].reset();
                    form.parsley().reset(); 
                    toastr.success(response.msg, "Success", 
                    {
                        positionClass: 'toast-bottom-right'
                    });
                } 
                else
                {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});

$("#DocumentSegmentationForm").on("submit", function(e) 
{
    e.preventDefault(); // Prevent normal form submission

    var form = $(this);
    if (form.parsley().isValid()) {
        let formData = form.serialize();

        $.ajax({
            url: "{{ route('resort.visa.DocumentSegmentationStore') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) 
                {
                    DocumentTypeIndex();
                    toastr.success(response.msg, "Success", 
                    {
                        positionClass: 'toast-bottom-right'
                    });
                    $("#WalletName").val();
                    $("#Amt").val();
                    form[0].reset();
                    form.parsley().reset();
                } 
                else
                {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    } else {
        // Optionally: scroll to the first invalid field
        form.parsley().validate();
    }
});



        $(document).on("click", "#WalletTypetable .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action
            // Find the parent row

            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var Del_cat_id = $(this).attr('data-del_cat_id');
            var Name = $row.find("td:nth-child(1)").text().trim();
            var Amount = $row.find("td:nth-child(2)").text().trim();
            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control" id="WalletName" value="${Name}"  required data-parsley-required-message="Please Enter Wallet Type"  name="WalletName" placeholder="Please Enter Wallet Type">
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="number" min="1" class="form-control" id="Amt" value="${Amount}"  required data-parsley-required-message="Please Enter Amount"  name="Amt" placeholder="Please Enter Amount"> 
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
           
        });
        $(document).on("click", "#WalletTypetable .update-row-btn_cat", function (event) 
        {
            event.preventDefault();
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var WalletName = $row.find("input[name='WalletName']").val();
            var Amount = $row.find("input[name='Amt']").val();
            $.ajax({
                url: "{{ route('resort.visa.UpdateWallet','')}}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    WalletName:WalletName,
                    Amt:Amount,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        WalletTypetable();
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });
        });
        $(document).on('click', '#WalletTypetable .delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
     
            var main_id = $(this).attr('data-cat-id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('resort.visa.WalletDestroy', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) 
                        {
                            WalletTypetable();
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

              
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });
        function DocumentTypeIndex()
        {
            if ($.fn.DataTable.isDataTable('#DocumentType')) {
                $('#DocumentType').DataTable().clear().destroy(); // Destroy existing instance
            }

            $('#DocumentType tbody').empty(); // Clear the tbody content if needed
            var hiringsource = $('#DocumentType').DataTable({
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
                url: '{{ route("resort.visa.DocumentTypeIndex") }}',
                type: 'GET',
            },
            columns: [
                { data: 'DocumentName', name: 'DocumentName', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false }
            ]
            });

        }
    function WalletTypetable() 
    {
        if ($.fn.DataTable.isDataTable('#WalletTypetable')) {
            $('#WalletTypetable').DataTable().clear().destroy(); // Destroy existing instance
        }

        $('#WalletTypetable tbody').empty(); // Clear previous content

        $('#WalletTypetable').DataTable({
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
                url: '{{ route("resort.visa.WalletIndex") }}',
                type: 'GET',
            },
            columns: [
                { data: 'WalletName', name: 'WalletName', className: 'text-nowrap' },
                { data: 'Amount', name: 'Amt', className: 'text-nowrap' }, // ✅ name must match DB column
                { data: 'Action', name: 'Action', orderable: false, searchable: false, className: 'text-nowrap' }
            ]
        });
    }
        $(document).on("click", "#DocumentType .delete-row-btn", function (event) 
        {
            event.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).attr('data-cat-id');
            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('resort.visa.DocumentTypeDelete', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            DocumentTypeIndex();
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on("click", "#DocumentType .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
            var Del_cat_id = $(this).attr('data-del_cat_id');
            
            var Description = $row.find("td:nth-child(1)").text().trim();
            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control" id="DelegationRuleName" value="${Description}"  required data-parsley-required-message="Please Enter Rule"  name="Del_Rule" placeholder="Set Rule">
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
           
        });
        $(document).on("click", "#DocumentType .update-row-btn_cat", function (event) 
        {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var documentname = $row.find("input").val();
            $.ajax({
                url: "{{ route('resort.visa.DocumentType.update','')}}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    documentname:documentname,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#DocumentType').DataTable().ajax.reload();
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });
        });
        $(document).on("click",".AddmoreDocumentType",function(){

            var count = $("#documentCount").val();
            count= parseInt(count) +1;
            $(".AppendDocumenttype").append(`<div class="row gx-2 gy-3 mb-md-4 mb-3 RemovedoumentCount_${count}">
                                                <div class="col-sm-4">
                                                    <label for="txt" class="form-label">DOCUMENT TYPE <span class="red-mark">*</span></label>
                                                    <select class="form-select select2t-none" name="document_id[]"  required data-parsley-required-message="Please select a Document Type" id="VisaDocumentType_${count}"
                                                        aria-label="Default select example">
                                                        <option > </option>
                                                        @if($VisaDocumentType->isnotEmpty())
                                                            @foreach($VisaDocumentType as $t)
                                                            <option value="{{$t->id}}">{{$t->documentname}} </option>

                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="col-sm-6">
                                                    <label for="txt" class="form-label">DOCUMENT NAME <span class="red-mark">*</span></label>
                                                    <input type="text" name="DocumentName[]" id="txt" class="form-control"required data-parsley-required-message="Please enter document type" placeholder="1000">
                                                </div>
                                                <div class="col-sm-2">
                                                    <input type="button" style="margin-top: 33px;" value="Remove" class="btn btn-danger btn-sm RemovedocumentType" data-id="${count}" fdprocessedid="qi65s">
                                                </div>
                                            </div>`); 
                $("#documentCount").val(count);

                $("#VisaDocumentType_"+count).select2({
                    allowClear: true,
                    placeholder: "Select Nationality"
                });
        });
        $(document).on("click",".RemovedocumentType",function(){
            var id = $(this).data("id");
            $(".RemovedoumentCount_"+id).remove();
            let count = parseInt(id)-1;
            $("#documentCount").val(count);
        });
</script>
@endsection
