@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Resort")
@else
  @section('page_tab_title' ,"Edit Resort")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Resort</h1>
          @else
            <h1>Edit Resort</h1>
          @endif
        </div>
        <form method=" post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.resorts.store') }}" @else action="{{ route('admin.resorts.update', $data->id) }}" @endif >
          @csrf
          @if($data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
            <h3>Resort Basic Information</h3>
            <hr/>
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Name <span class="red-mark">*</span></label>
                  <input data-parsley-validate-script
                      data-parsley-validate-script-message="Remove script tags"
                      data-parsley-errors-container="#div-resort_name"
                      data-parsley-required-message="Resort name is required."
                      data-parsley-maxlength="50"
                      data-parsley-maxlength-message="Maximum 50 characters allowed" 
                      required
                                      data-parsley-no-special-chars
                    data-parsley-no-special-chars-message="Special characters are not allowed."
                      type="text"
                      name="resort_name"
                      id="resort_name"
                      class="form-control"
                      placeholder="Resort Name"
                      value="{{$data->resort_name}}">
                  <div id="div-resort_name"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort ID <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed." data-parsley-errors-container="#div-resort_id"
                  required type="text"  class="form-control" placeholder="Resort ID" value="{{$data->resort_id?$data->resort_id:$resort_id}}" readonly name="resort_id" id="resort_id">
                  <div id="div-resort_id"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Email <span class="red-mark">*</span></label>
                  <input
                   required
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-errors-container="#div-resort_email"
                  data-parsley-custom-email="true"
                  pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                  data-parsley-pattern-message="Invalid email address"
                  data-parsley-required-message="Resort email is required."
                  name="resort_email" id="resort_email" type="email" required class="form-control" value="{{$data->resort_email}}" placeholder="Resort Email">
                  <div id="div-resort_email"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Contact No <span class="red-mark">*</span></label>
                  <input
                    data-parsley-errors-container="#div-resort_phone"
                    data-parsley-pattern="^(\+960\d{7}|\d{7}|\d{10})$"
                    data-parsley-pattern-message="Invalid phone number. Please enter a 7-digit, 10-digit, or a valid +960 format number."
                    data-parsley-required-message="Resort Contact No is required."
                    type="text" class="form-control" name="resort_phone" id="resort_phone" placeholder="Resort Contact No"
                    value="{{$data->resort_phone}}" required>
                  <div id="div-resort_phone"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort IT Email <span class="red-mark">*</span></label>
                  <input
                  data-parsley-errors-container="#div-resort_it_email"
                  data-parsley-custom-email="true"
                  data-parsley-pattern-message="Invalid email address"
                  data-parsley-required-message="Resort IT Email is required."
                  name="resort_it_email" id="resort_it_email" type="email" required class="form-control" value="{{$data->resort_it_email}}" placeholder="Resort IT Email">
                  <div id="div-resort_it_email"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort IT Contact No <span class="red-mark">*</span></label>
                  <input
                  data-parsley-errors-container="#div-resort_it_phone"
                  data-parsley-pattern="^(\+960\d{7}|\d{7}|\d{10})$"
                  data-parsley-pattern-message="Invalid phone number. Please enter a 7-digit, 10-digit, or a valid +960 format number."
                  data-parsley-required-message="Resort IT Contact No is required."
                  type="text" class="form-control" name="resort_it_phone" id="resort_it_phone" placeholder="Resort IT Contact No" value="{{$data->resort_it_phone}}" required>
                  <div id="div-resort_it_phone"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Status <span class="red-mark">*</span></label>
                  <select
                  required data-parsley-errors-container="#div-resort_status"
                  data-parsley-required-message="Resort Status is required."
                  name="resort_status" id="resort_status" class="select-2-gen form-control" required>
                    <option selected disabled>Select a value</option>
                    @foreach($statuses as $key => $value)
                        <option value="{{ $key }}" @if($data->status == $key) selected @endif>{{ $value }}</option>
                    @endforeach
                  </select>
                  <div id="div-resort_status"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Prefix <span class="red-mark">*</span></label>
                  <input
                  data-parsley-errors-container="#div-resort_prefix"
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Resort Prefix is required."
                  type="text" class="form-control" name="resort_prefix" id="resort_prefix" placeholder="Resort Prefix e.g APV, SAM" value="{{$data->resort_prefix}}" required >
                  <div id="div-resort_prefix"></div>
                </div>
              </div>
            </div>

            <h3>Resort Address</h3>
            <hr/>
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Address Line1 <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Resort Address Line1 is required."
                  type="text" name="address1" id="address1" class="form-control" placeholder="Address Line 1" value="{{$data->address1 ?? old('address1') }}" required>
                  <div id="div-address1"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Resort Address Line2 <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Resort Address Line2 is required."
                  type="text" name="address2" id="address2" class="form-control" placeholder="Address Line 2" value="{{$data->address2 ?? old('address2') }}" required>
                  <div id="div-address2"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>City <span class="red-mark">*</span></label>
                  <input
                      data-parsley-validate-script
                      data-parsley-validate-script-message="Script tags are not allowed."
                      data-parsley-pattern="^[a-zA-Z\s'’\-éàèùâêîôûçëïü]+$"
                      data-parsley-pattern-message="City name can only contain letters, spaces, apostrophes, and accented characters."
                      data-parsley-required-message="City is required."
                      type="text" name="resort_city" id="resort_city" class="form-control" placeholder="City"
                      value="{{$data->city ?? old('city') }}" required>
                  <div id="div-resort_city"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Atoll <span class="red-mark">*</span></label>
                  <select
                  data-parsley-errors-container="#div-resort_state"
                  data-parsley-required-message="Atoll is required."
                  name="resort_state" id="resort_state" class="form-control select-2-gen" required>
                    <option value="">Select Atoll</option>
                    @foreach ($states as $state)
                      <option value="{{ $state }}" @if($state == $data->state) Selected @endif >{{ $state }}</option>
                    @endforeach
                  </select>
                  <div id="div-resort_state"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Country<span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Country is required."
                  type="text" name="resort_country" id="resort_country" value="{{$data->country ?? 'Maldives' }}" class="form-control" placeholder="Country" readonly required>
                  <div id="div-resort_country"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Pincode<span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-type="number"
                  data-parsley-type-message="Pincode must be a numeric value."
                  data-parsley-required-message="Pincode is required."
                  type="text" name="zip" id="zip" class="form-control" placeholder="Pincode" value="{{$data->zip ?? old('zip') }}" required>
                  <div id="div-zip"></div>
                </div>
              </div>
            </div>

            <h3>Resort Head Office Address</h3>
            <hr/>
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label> Address Line1 <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Address Line1 is required."
                  type="text" name="headoffice_address1" id="headoffice_address1" class="form-control" placeholder="Address Line 1" value="{{$data->headoffice_address1 ?? old('headoffice_address1') }}" required>
                  <div id="div-headoffice_address1"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label> Address Line2 <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Address Line2 is required."
                  type="text" name="headoffice_address2" id="headoffice_address2" class="form-control" placeholder=" Address Line 2" value="{{$data->headoffice_address2 ?? old('headoffice_address2') }}" required>
                  <div id="div-headoffice_address2"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label> City <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-pattern="^[a-zA-Z\s'’\-éàèùâêîôûçëïü]+$"
                  data-parsley-pattern-message="City name can only contain letters, spaces, apostrophes, and accented characters."data-parsley-pattern="^[a-zA-Z\s'’\-éàèùâêîôûçëïü]+$"
                  data-parsley-required-message="City is required."
                  type="text" name="headoffice_city" id="headoffice_city" class="form-control" placeholder=" City" value="{{$data->headoffice_city ?? old('headoffice_city') }}" required>
                  <div id="div-headoffice_city"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label> Atoll / State <span class="red-mark">*</span> </label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-pattern="^[a-zA-Z\s]+$"
                  data-parsley-pattern-message="State name can only contain letters and spaces."
                  data-parsley-required-message="State is required."
                  type="text" name="headoffice_state" id="headoffice_state" class="form-control" placeholder=" City" value="{{$data->headoffice_city ?? old('headoffice_city') }}" required>
                  <div id="div-headoffice_state"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Country <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Country is required."
                  type="text" name="headoffice_country" id="headoffice_country" value="{{$data->headoffice_country ?? 'Maldives' }}" class="form-control" placeholder="Country" required>
                  <div id="div-headoffice_country"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Pincode <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-type="number"
                  data-parsley-type-message="Pincode must be a numeric value."
                  data-parsley-required-message="Pincode is required."
                  type="text" name="headoffice_zip" id="headoffice_zip" class="form-control" placeholder="Pincode" value="{{$data->headoffice_pincode ?? old('headoffice_pincode') }}" required>
                  <div id="div-headoffice_zip"></div>
                </div>
              </div>
            </div>

            <h3>Billing and Financial Information</h3>
            <hr/>
            <div class="row">
              <div class="col-lg-12">
                <label class="form-label" for="same_billing_address">Same Resort Address in to Billing
                  Address?</label>
                  <span class="red-mark">*</span>
                  <div class="row">
                    <div class="col-auto">
                      <div class="form-check ps-0">
                        <input type="radio" class="btn-check" id="radio-yes" autocomplete="off"
                          name="same_billing_address" value="Yes" Checked @if($data->same_billing_address == "yes") Checked @endif>
                        <label class="form-radio-label-custome form-check-label w-100" for="radio-yes" title="Yes">Yes</label>
                      </div>
                    </div>
                    <div class="col-auto">
                      <div class="form-check ps-0">
                        <input type="radio" class="btn-check" id="radio-no" autocomplete="off"
                          name="same_billing_address" value="No" @if($data->same_billing_address == 'no') Checked @endif>
                        <label class="form-radio-label-custome form-check-label w-100" for="radio-no" title="No">No</label>
                      </div>
                    </div>
                  </div>
              </div>
            </div>

            <div class="d-none mt-3" id="billed-address-content">
              <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Billing Address1 <span class="red-mark">*</span></label>
                    <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-required-message="Billing Address1 is required."
                    type="text" name="billing_address1" id="billing_address1" class="form-control" placeholder="Billing Address1" value="{{$data->billing_address1 ?? old('billing_address1') }}" required>
                    <div id="div-billing_address1"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Billing Address2 <span class="red-mark">*</span></label>
                    <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-required-message="Billing Address2 is required."
                    type="text" name="billing_address2" id="billing_address2" class="form-control"  placeholder="Billing Address2" value="{{$data->billing_address2 ?? old('billing_address2') }}" required>
                    <div id="div-billing_address2"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>City <span class="red-mark">*</span></label>
                    <input data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-pattern="^[a-zA-Z\s'’\-éàèùâêîôûçëïü]+$"
                    data-parsley-pattern-message="City name can only contain letters, spaces, apostrophes, and accented characters."
                    data-parsley-required-message="City is required."
                    type="text" name="billing_city" id="billing_city" class="form-control"  placeholder="City Name" value="{{$data->billing_city ?? old('billing_city') }}" required>
                    <div id="div-billing_city"></div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Atoll <span class="red-mark">*</span></label>
                    <select data-parsley-errors-container="#div-billing_state"
                    data-parsley-required-message="Atoll is required." name="billing_state" id="billing_state" class="form-control select-2-gen">
                      <option value="">Select Atoll</option>
                      @foreach ($states as $state)
                        <option value="{{ $state }}" @if($state == $data->billing_state) Selected @endif >{{ $state }}</option>
                      @endforeach
                    </select>
                    <div id="div-billing_state"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Country <span class="red-mark">*</span></label>
                    <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-required-message="Country is required."
                    type="text" name="billing_country" id="billing_country" value="Maldives" class="form-control"  placeholder="Country" required readonly>
                    <div id="div-billing_country"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Pincode <span class="red-mark">*</span></label>
                    <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-type="number"
                    data-parsley-type-message="Pincode must be a numeric value."
                    data-parsley-required-message="Pincode is required."
                    type="text" name="billing_pincode" id="billing_pincode" class="form-control"  placeholder="Pincode" value="{{$data->billing_pincode ?? old('billing_pincode') }}" required>
                    <div id="div-billing_pincode"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Tax Identification Number (TIN)<span class="red-mark">*</span></label>
                    <input data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-required-message="Tax Identification Number (TIN) is required."  type="text" name="tin" id="tin" class="form-control" placeholder="Tax Identification Number (TIN)" value="{{$data->tin ?? old('tin') }}" required>
                    <div id="div-tin"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Preferred Payment Method<span class="red-mark">*</span></label>
                    <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-required-message="Preferred Payment Method is required." type="text" name="payment_method" id="payment_method" class="form-control" placeholder="Preferred Payment Method" value="{{$data->payment_method ?? old('payment_method') }}" required>
                    <div id="div-payment_method"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label> Invoicing Email <span class="red-mark">*</span></label>
                    <input data-parsley-errors-container="#div-invoice_email"
                    data-parsley-custom-email="true"
                    data-parsley-pattern-message="Invalid email address"
                    data-parsley-required-message="Invoicing Email is required." name="invoice_email" id="invoice_email" type="email" required class="form-control" value="{{$data->invoice_email}}" placeholder="Invoicing Email">
                  <div id="div-invoice_email"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Payment Status <span class="red-mark">*</span></label>
                    <select required data-parsley-errors-container="#div-payment_status"
                    data-parsley-required-message="Payment Status is required." name="payment_status" id="payment_status" class="select-2-gen form-control" required>
                      <option selected disabled>Select a value</option>
                      @foreach($paymentStatuses as $key => $value)
                          <option value="{{ $key }}" @if($data->payment_status == $key) selected @endif>
                              {{ $value }}
                          </option>
                      @endforeach
                    </select>
                    <div id="div-payment_status"></div>
                  </div>
                </div>
               
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Invoice Status <span class="red-mark">*</span></label>
                    <select required data-parsley-errors-container="#div-invoice_status" data-parsley-required-message="Invoice Status is required." name="invoice_status" id="invoice_status" class="select-2-gen form-control" required>
                      <option selected disabled>Select a value</option>
                      @foreach($invoiceStatuses as $key => $value)
                          <option value="{{ $key }}" @if($data->invoice_status == $key) selected @endif>{{ $value }}</option>
                      @endforeach
                    </select>
                    <div id="div-invoice_status"></div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <label>Service Package <span class="red-mark">*</span></label>
                    <select required data-parsley-errors-container="#div-service_package" data-parsley-required-message="Service Package is required." name="service_package" id="service_package" class="select-2-gen form-control">
                      <option selected disabled>Select a value</option>
                      @foreach($servicePackages as $key => $value)
                          <option value="{{ $key }}" @if($data->service_package == $key) selected @endif>{{ $value }}</option>
                      @endforeach
                    </select>
                    <div id="div-service_package"></div>
                  </div>
                </div>
                <div class="col-md-4">
									<div class="form-group brdrfull">
										<label>Contract Start Date <span class="red-mark">*</span></label>
										<input data-parsley-errors-container="#div-contract_start_date" data-parsley-required-message="Contract Start Date is required." type="text" name="contract_start_date" id="contract_start_date" class="form-control" placeholder="Contract Start Date" value="{{$data->contract_start_date}}" required>
										<div id="div-contract_start_date"></div>
									</div>
								</div>
                <div class="col-md-4">
									<div class="form-group brdrfull">
										<label>Contract End Date <span class="red-mark">*</span></label>
										<input data-parsley-required-message="Contract End Date is required."  data-parsley-errors-container="#div-contract_end_date" data-parsley-end-after-start  type="text" name="contract_end_date" id="contract_end_date" class="form-control" placeholder="Contract End Date" value="{{$data->contract_end_date}}" required>
										<div id="div-contract_end_date"></div>
									</div>
								</div>
                 <div class="col-md-4">
									<div class="form-group brdrfull">
										<label>Due Date <span class="red-mark">*</span></label>
                    <input 
                      data-parsley-errors-container="#div-due_date" 
                      data-parsley-required-message="Due Date is required." 
                      data-parsley-due-date-between 
                      type="text" 
                      name="due_date" 
                      id="due_date" 
                      class="form-control" 
                      placeholder="Due Date" 
                      value="{{$data->due_date}}" 
                      required>
										<div id="div-due_date"></div>
									</div>
								</div>
                <div class="col-sm-5">
                  <div class="form-group">
                    <label for="brand_logo">Brand logo <span class="red-mark">*</span></label>
                    <div class="input-group">
                      <div class="custom-file">
                        <input data-parsley-required-message="Brand logo is required." data-parsley-errors-container="#div-brand_logo" onchange="imagePreview(event, 'brandLogo')" type="file" class="custom-file-input" id="logo" name="logo" accept="image/*" @if(!$data->logo) required @endif>
                        <label class="custom-file-label" for="logo">Choose file</label>
                      </div>
                      <div class="input-group-append">
                        <span class="input-group-text">Upload</span>
                      </div>
                    </div>
                    <div id="div-brand_logo"></div>
                  </div>

                </div>
                <div class="col-sm-1">
                  @if( $data->logo != '' )
                    <img id="brandLogo" class="logo-img" width="100px" src="{{url(config('settings.brand_logo_folder'))."/".$data->logo}}">
                  @else
                    <img id="brandLogo" class="logo-img" width="100px" src="{{url(config('settings.default_picture'))}}" />
                  @endif
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                    <label>No Of Users <span class="red-mark">*</span></label>
                    <input data-parsley-required-message="No Of Users is required." data-parsley-validate-script data-parsley-validate-script-message="Script tags are not allowed." min="0" type="number" name="no_of_users" id="no_of_users" class="form-control" placeholder="No Of Users" value="{{$data->no_of_users ?? old('no_of_users') }}" required>
                    <div id="div-no_of_users"></div>
                  </div>
                </div>
            </div>

            <h3>Support Preferences</h3>
            <hr/>
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>Preferred Support Channels <span class="red-mark">*</span></label>
                        <div class="row gy-3">
                            @foreach ($support_channels as $key => $channel)
                            <div class="col-4">
                                <div class="form-check pl-0">
                                    <input data-parsley-required-message="Preferred Support Channels is required." type="checkbox" class="btn-check channel-checkbox" id="channel_{{ $key }}" autocomplete="off" name="support_preference[]" value="{{ $channel }}"
                                        @if(!empty($data->id) && in_array($channel, $selected_channels)) checked="checked" @endif required>
                                    <label class="form-check-label-custome form-check-label w-100 d-flex align-items-center justify-content-between" for="channel_{{ $key }}" title="{{ $channel }}">
                                        <span>{{ $channel }}</span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div id="div-support_preference"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label>Support SLA <span class="red-mark">*</span></label>
                        <div class="form-group brdrfull">
                            <div class="row">
                                <div class="col-auto pr-0">
                                    <div class="age-title">
                                        <input type="radio" id="Support_SLA-yes" name="Support_SLA" value="24/7 support" {{ $data->Support_SLA == '24/7 support' ? 'checked' : '' }}>
                                        <label for="Support_SLA-yes">24/7 support</label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="age-title">
                                        <input type="radio" id="Support_SLA-no" name="Support_SLA" value="Business Hours only" {{ $data->Support_SLA == 'Business Hours only' ? 'checked' : '' }}>
                                        <label for="Support_SLA-no">Business Hours only</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="div-Support_SLA"></div>
                    </div>
                </div>
            </div>

            <div id="business-hours" style="display:none">
              <h4>Define Business Hours</h4>
              @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                  @php
                      $hours = $data->businessHours->firstWhere('day_of_week', $day);
                  @endphp
                  <div class="row mb-2">
                      <div class="col-sm-2">
                          <label>{{ $day }}</label>
                      </div>
                      <div class="col-sm-5">
                          <input type="time" name="business_hours[{{ $day }}][start]" class="form-control business-hour-input"
                                value="{{ old('business_hours.'.$day.'.start', optional($hours)->start_time) }}">
                      </div>
                      <div class="col-sm-5">
                          <input type="time" name="business_hours[{{ $day }}][end]" class="form-control business-hour-input"
                                value="{{ old('business_hours.'.$day.'.end', optional($hours)->end_time) }}">
                      </div>
                  </div>
              @endforeach
            </div>

            <h3>Account Admin</h3>
            <hr/>
            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>First Name<span class="red-mark">*</span></label>
                  <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-required-message="First Name is required."
                    data-parsley-no-special-chars
                    data-parsley-no-special-chars-message="Special characters are not allowed."
                  type="text" name="first_name" id="first_name" class="form-control" placeholder="First Name" value="{{$resort_admin->first_name}}" required >
                  <div id="div-first_name"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Middle Name </label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                                  data-parsley-no-special-chars
                    data-parsley-no-special-chars-message="Special characters are not allowed."
                  type="text" name="middle_name" id="middle_name" class="form-control" placeholder="Middle Name" value="{{$resort_admin->middle_name}}">
                  <div id="div-middle_name"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Last Name<span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-required-message="Last Name is required."
                                  data-parsley-no-special-chars
                    data-parsley-no-special-chars-message="Special characters are not allowed."
                  type="text" name="last_name" id="last_name" class="form-control" placeholder="Last Name" value="{{$resort_admin->last_name}}" required >
                  <div id="div-last_name"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Gender</label>
                  <select data-parsley-required-message="Gender is required." data-parsley-errors-container="#div-gender"  class="select-2-gen form-control" name="gender" id="gender" required>
                    <option selected disabled>Select a value</option>
                    <option value="male" @if($resort_admin->gender == 'male') Selected @endif>Male</option>
                    <option value="female" @if($resort_admin->gender == 'female') Selected @endif>Female</option>
                    <option value="other" @if($resort_admin->gender == 'other') Selected @endif>Other</option>
                  </select>
                  <div id="div-gender"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Personal Email <span class="red-mark">*</span></label>
                  <input data-parsley-errors-container="#div-email" data-parsley-custom-email="true" data-parsley-pattern-message="Invalid email address"  data-parsley-required-message="Personal Email is required." name="email" id="email" type="email" required class="form-control" value="{{$resort_admin->email}}" placeholder="Personal Email">
                  <div id="div-email"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Personal phone <span class="red-mark">*</span></label>
                  <input data-parsley-errors-container="#div-personal_phone" 
                  data-parsley-pattern="^(\+960\d{7}|\d{7}|\d{10})$"
                  data-parsley-pattern-message="Invalid phone number. Please enter a 7-digit, 10-digit, or a valid +960 format number." 
                  data-parsley-required-message="Personal phone is required." type="text" class="form-control" name="personal_phone" id="personal_phone" placeholder="Personal Phone" value="{{$resort_admin->personal_phone}}" required>
                  <div id="div-personal_phone"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Address Line1 <span class="red-mark">*</span></label>
                  <input data-parsley-validate-script data-parsley-validate-script-message="Script tags are not allowed." data-parsley-required-message="Address Line1 is required." type="text" name="address_line_1" id="address_line_1" class="form-control" placeholder="Address Line 1" value="{{$resort_admin->address_line_1}}" required>
                  <div id="div-address_line_1"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Address Line2 <span class="red-mark">*</span></label>
                  <input data-parsley-required-message="Address Line2 is required."  data-parsley-validate-script data-parsley-validate-script-message="Script tags are not allowed." type="text" name="address_line_2" id="address_line_2" class="form-control"  placeholder="Address Line 2" value="{{$resort_admin->address_line_2}}" required>
                  <div id="div-address_line_2"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>City <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-pattern="^[a-zA-Z\s'’\-éàèùâêîôûçëïü]+$"
                  data-parsley-pattern-message="City name can only contain letters, spaces, apostrophes, and accented characters."
                  data-parsley-required-message="City is required."
                  type="text" name="city" id="city" class="form-control"  placeholder="City Name" value="{{$resort_admin->city ?? old('city') }}" required>
                  <div id="div-city"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>ATOLL <span class="red-mark">*</span></label>
                  <select data-parsley-errors-container="#div-state" data-parsley-required-message="Atoll is required." name="state" id="state" class="form-control select-2-gen" required>
                    <option value="">Select Atoll</option>
                    @foreach ($states as $state)
                      <option value="{{ $state }}" @if($state == $resort_admin->state) Selected @endif >{{ $state }}</option>
                    @endforeach
                  </select>
                  <div id="div-state"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Country <span class="red-mark">*</span></label>
                  <input data-parsley-validate-script data-parsley-validate-script-message="Script tags are not allowed." data-parsley-required-message="Country is required." type="text" name="country" id="country" value="Maldives" class="form-control" placeholder="Country" readonly required>
                  <div id="div-country"></div>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Pincode <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-type="number"
                  data-parsley-type-message="Pincode must be a numeric value."
                  data-parsley-required-message="Pincode is required."
                  type="text" name="pincode" id="pincode" class="form-control" placeholder="Pincode" value="{{$resort_admin->zip}}" required>
                  <div id="div-pincode"></div>
                </div>
              </div>
              <div class="col-sm-5">
                <div class="form-group">
                  <label for="profile_image">Profile image <span class="red-mark">*</span></label>
                  <div class="input-group">
                    <div class="custom-file">
                      <input data-parsley-errors-container="#div-profile_image" data-parsley-required-message="Profile image is required." onchange="ProfileimagePreview(event, 'profile_picture_preview')" type="file" class="custom-file-input" id="profile_picture" name="profile_picture" accept="image/*" @if(!$resort_admin->profile_picture) required @endif>
                      <label class="custom-file-label" for="profile_picture">Choose file</label>
                    </div>
                    <div class="input-group-append">
                      <span class="input-group-text">Upload</span>
                    </div>
                  </div>
                  <div id="div-profile_image" ></div>
                </div>
              </div>
              <div class="col-sm-1">
                @if( $resort_admin->profile_picture != '' )
                  <img id="profile_picture_preview" class="logo-img" width="100px" src="{{ url(config('settings.ResortProfile_folder') . '/' . $resort_admin->profile_picture) }}">
                @else
                    <img id="profile_picture_preview" class="profilelogo-img" width="100px" src="{{url(config('settings.default_picture'))}}" />
                @endif
              </div>
              <div class="col-sm-2">
                <div class="form-group">
                  <label>Status <span class="red-mark">*</span></label>
                  <select data-parsley-errors-container="#div-status" data-parsley-required-message="Status is required." name="status" id="status" class="select-2-gen form-control" required>
                    <option selected disabled>Select a value</option>
                    <option value="active" @if($resort_admin->id == '') Selected @else @if($resort_admin->status == 'active') Selected @endif @endif>Active</option>
                    <option value="inactive" @if($resort_admin->status == 'inactive') Selected @endif>Inactive</option>
                  </select>
                  <div id="div-status"></div>
                </div>
              </div>

                 <div class="col-sm-4">
                <div class="form-group">
                  <label>Select who has full access  <span class="red-mark">*</span></label>
                  <select data-parsley-errors-container="#div-Access" data-parsley-required-message="Access is required." name="Position_access" id="Position_access" class="select-2-gen form-control" required>
                        <option selected disabled>Select a value</option>
                        @if($Position_access->isNotEmpty())
                          @foreach($Position_access as $key => $value)
                              <option value="{{ $value->id }}" @if(isset($data) && $data->Position_access == $value->id) selected @endif>{{ $value->position_title }}</option>
                          @endforeach
                        @else 
                            <option value="0" >Please Add Position </option>
                        @endif
                  </select>
                  <div id="div-Access"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <button type="submit" class="btn btn-info">Submit</button>
            <button type="button" onclick="document.getElementById('formStore').reset();"
            class="btn btn-default resetForm">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

@endsection

@section('import-css')
<!-- Bootstrap CSS -->
{{-- <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet"> --}}

<!-- Bootstrap Bundle with Popper.js -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<style>
  .channel-checkbox{
    position: absolute;
    clip: rect(0, 0, 0, 0);
    pointer-events: none;
}
</style>
@endsection

@section('import-scripts')
<script>
    var imagePreview = function(event, id) {
        var output = document.getElementById(id);
        output.src = URL.createObjectURL(event.target.files[0]);
        console.log(  URL.revokeObjectURL(output.src))

        output.onload = function() {
            URL.revokeObjectURL(output.src);
        }
    };

    var ProfileimagePreview = function(event, id) {
        var output = document.getElementById(id);
        output.src = URL.createObjectURL(event.target.files[0]);

        output.onload = function() {
            URL.revokeObjectURL(output.src); // This will clean up the URL after the image has loaded.
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const slaRadios = document.getElementsByName('Support_SLA');
        const businessHoursDiv = document.getElementById('business-hours');

        function toggleBusinessHours() {
            if (document.getElementById('Support_SLA-no').checked) {
                businessHoursDiv.style.display = 'block';
            } else {
                businessHoursDiv.style.display = 'none';
            }
        }

        slaRadios.forEach(radio => {
            radio.addEventListener('change', toggleBusinessHours);
        });

        // Initialize on page load
        toggleBusinessHours();
    });

    $(document).ready(function() {

        $('#accordion .collapse').first().collapse('show');
        function toggleBillingAddress() {
            if ($("#radio-yes").is(":checked")) {
                // Hide the billing address section
                $("#billed-address-content").removeClass("d-block").addClass("d-none");

                // Clear billing address fields
                $('#billing_address1, #billing_address2, #billing_city, #billing_state, #billing_country, #billing_pincode').val("");
                $('#billing_country').val("Maldives");

                // Remove required attributes since the billing address is not needed
                $('#billing_address1, #billing_address2, #billing_city, #billing_state, #billing_pincode').removeAttr('required');
            } else {
                // Show the billing address section
                $("#billed-address-content").removeClass("d-none").addClass("d-block");

                // Add required attributes for validation
                $('#billing_address1, #billing_address2, #billing_city, #billing_state, #billing_pincode').attr('required', true);
            }
        }

        // Initialize the toggle function on page load
        toggleBillingAddress();

        // Bind the click event for the radio buttons
        $('input[name="same_billing_address"]').on('click', function() {
            toggleBillingAddress();
        });

        window.Parsley.addValidator('checkboxrequired', {
            validateString: function(value, requirement, parsleyInstance) {
                return parsleyInstance.$element.find('input[type="checkbox"]:checked').length > 0;
            },
            messages: {
                en: 'You must select at least one support channel.'
            }
        });

        // Add a custom validator for script tag validation in Parsley

        window.Parsley.addValidator('validateScript', {
            validateString: function(value) {
                // Pattern to match any <script> tags, even with attributes or content
                const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
            },
            messages: {
                en: 'Script tags are not allowed.'
            }
        });

        // Add a custom validator for email validation in Parsley
        window.Parsley.addValidator('customEmail', {
          validateString: function(value) {
            var emailRegex = /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            var disallowedEmailRegex = /(.)\1{2,}|(\+.*?\+)|(\.{2,})|(-{2,})|(@-|-\@)|(@\.)|(\.@)/;

            if (!emailRegex.test(value) || disallowedEmailRegex.test(value)) {
              return false;
            }

            var domain = value.split('@')[1];

            if (domain.includes('..') || domain.match(/\.\w+\.\w+$/)) {
              return false;
            }

            var validTLDs = ['com', 'org', 'net', 'co', 'in', 'uk', 'info','ai'];
            var domainParts = domain.split('.').reverse();

            if (!validTLDs.includes(domainParts[0]) || (domainParts[0] === 'co' && !validTLDs.includes(domainParts[1]))) {
              return false;
            }

            return true;
          },
          messages: {
            en: 'Invalid email address'
          }
        });

        // Helper function to convert dd-mm-yyyy to a Date object
        function parseDate(input) {
            var parts = input.split("-");
            return new Date(parts[2], parts[1] - 1, parts[0]); // year, month (0-based), day
        }

        // Custom validator for ensuring end date is greater than start date
        window.Parsley.addValidator('endAfterStart', {
            validateString: function(value, requirement) {
                var startDate = parseDate($('#contract_start_date').val());
                var endDate = parseDate(value);
                return endDate > startDate;
            },
            messages: {
                en: 'Contract End Date must be after the Contract Start Date.'
            }
        });

        window.Parsley.addValidator('dueDateBetween', {
          validateString: function (value, requirement) {
              var dueDate = parseDate(value);
              var startDate = parseDate($('#contract_start_date').val());
              var endDate = parseDate($('#contract_end_date').val());

              return dueDate >= startDate && dueDate <= endDate;
          },
          messages: {
              en: 'Due Date must be between Contract Start Date and Contract End Date.'
          }
      });
        window.Parsley.addValidator('noSpecialChars', {
        requirementType: 'string',
        validateString: function(value) {
        // Allow letters, spaces, and hyphens only
        return /^[a-zA-Z\s\-]+$/.test(value);
        },
        messages: {
        en: 'Special characters are not allowed. Only letters, spaces, and hyphens are permitted.'
        }
        });

        // Apply custom validation to the checkbox group
        $('.form-group').parsley({
            errorsContainer: function(parsleyField) {
                return $('#div-support_preference');
            }
        });

        // Trigger validation when any checkbox is clicked
        $('.channel-checkbox').on('change', function() {
            $(this).closest('.form-group').parsley().validate();
        });

        $("#due_date, #contract_start_date, #contract_end_date").datepicker({
            format: dt_format,
            todayHighlight: true
        }).on('changeDate', function() {
            // Hide the datepicker after date selection
            $(this).datepicker('hide');

            // Manually trigger Parsley validation for the field
            $(this).parsley().validate();
            // Revalidate the start and end date fields together
            if (this.id === 'contract_start_date' || this.id === 'contract_end_date') {
                $('#contract_start_date').parsley().validate();
                $('#contract_end_date').parsley().validate();
                    $('#due_date').parsley().validate();

            }
        });

        $("#formStore").parsley();

        $("#formStore").on("submit", function(e) {
            e.preventDefault();

            if ($('#formStore').parsley().isValid()) {
              $.ajax({
                  type: "POST",
                  url: $(this).attr('action'),
                  data: new FormData(this),
                  dataType: "JSON",
                  contentType: false,
                  processData: false,
                  beforeSend: function() {
                      HoldOn.open({
                          theme: "sk-cube-grid"
                      });
                  },
                  success: function(result) {
                      HoldOn.close();
                      if (result.success) {
                          toastr.success(result.msg, "Success", {
                              positionClass: 'toast-bottom-right'
                          });

                          window.setTimeout(function() {
                              window.location.href = result.redirect_url;
                          }, 2000);
                      } else {
                          toastr.error(result.msg, "Error", {
                              positionClass: 'toast-bottom-right'
                          });
                      }
                  },
                  error: function() {
                      HoldOn.close();
                      toastr.error("Something went wrong", "Error", {
                          positionClass: 'toast-bottom-right'
                      });
                  }
              });
            }
        });

    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const slaRadios = document.getElementsByName('Support_SLA');
    const businessHoursDiv = document.getElementById('business-hours');
    const hourInputs = document.querySelectorAll('.business-hour-input');

    function toggleBusinessHoursRequirement() {
        const selected = document.querySelector('input[name="Support_SLA"]:checked')?.value;

        if (selected === 'Business Hours only') {
            businessHoursDiv.style.display = 'block';
            hourInputs.forEach(input => input.setAttribute('required', 'required'));
        } else {
            businessHoursDiv.style.display = 'none';
            hourInputs.forEach(input => input.removeAttribute('required'));
        }
    }

    // Trigger on page load (for edit forms)
    toggleBusinessHoursRequirement();

    // Trigger on change
    slaRadios.forEach(radio => {
        radio.addEventListener('change', toggleBusinessHoursRequirement);
    });
});
</script>
@endsection
