@extends('resorts.layouts.app')
@section('page_tab_title', 'Site Settings')

@section('content')

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
                        <a href="{{ route('resort.Page.Permission') }}" target="_blank" class="btn btn-themeSkyblue btn-sm">PAGE PERMISSION</a>
                    </div>
                </div>
            </div>

            <form method="POST" id="siteSettingsForm" class="form-horizontal" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="resort_id" value="{{ $resort->id }}">
                <div class="mb-md-4 mb-3">
                    <label class="form-label">Select Currency</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="currency" name="currency"
                            value="MVR" {{ (isset($SiteSettings->currency ) && $SiteSettings->currency =="MVR" ?'checked': '') }} >
                            <label class="form-check-label" for="currency">MVR</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="currency1" name="currency" value="Dollar"  {{ (isset($SiteSettings->currency ) && $SiteSettings->currency =="Dollar" ?'checked': '') }} >
                            <label class="form-check-label" for="currency1">Dollar</label>
                        </div>
                    </div>
                </div>


                {{-- FX-rate policy (May 2026 developer reference):
                       - Canonical rate: 1 USD = MVR 15.42 (system-wide).
                       - DollertoMVR is the ONLY stored / editable value.
                       - MVR → USD is always derived by division.
                       - Both fields are read-only — operators must not edit
                         the rate at runtime; the dev-reference doc bakes
                         15.42 in as fixed.
                       - MVRtoDoller is rendered as a calculated read-only
                         label and submitted as a hidden derived value so
                         legacy code paths keep getting the right number
                         until they're migrated to compute it themselves.
                --}}
                @php
                    $_dollarToMvr = isset($SiteSettings->DollertoMVR) && (float) $SiteSettings->DollertoMVR > 0
                        ? (float) $SiteSettings->DollertoMVR
                        : 15.42;
                    $_mvrToDollarDerived = 1.0 / $_dollarToMvr;
                @endphp
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="header_img">  </label>
                        <label for="DollertoMVR" class="form-label">
                            USD &rarr; MVR <span class="red-mark">*</span>
                        </label>
                        <input type="number" step="0.01" class="form-control" id="DollertoMVR" name="DollertoMVR"
                               value="{{ old('DollertoMVR', $_dollarToMvr) }}"
                               readonly
                               title="Locked at the system-wide canonical rate (1 USD = 15.42 MVR)."
                               style="background-color:#f1f5f9; cursor: not-allowed;">
                        <small class="form-text text-muted">Fixed system rate</small>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="header_img">  </label>
                        <label for="MVRtoDoller_display" class="form-label">MVR &rarr; USD</label>
                        <input type="text" class="form-control" id="MVRtoDoller_display"
                               value="{{ number_format($_mvrToDollarDerived, 6) }}"
                               readonly
                               title="Derived as 1 / (USD → MVR). Never edited directly — multiplying by a stored inverse introduces rounding drift."
                               style="background-color:#f1f5f9; cursor: not-allowed;">
                        {{-- Hidden field keeps the legacy column in sync with the
                             derived value on save. Callers that still read
                             MVRtoDoller therefore can't drift from the
                             canonical inverse. --}}
                        <input type="hidden" name="MVRtoDoller" value="{{ $_mvrToDollarDerived }}">
                        <small class="form-text text-muted">Derived: 1 &divide; ({{ number_format($_dollarToMvr, 2) }})</small>
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
                                    @if(!empty($position_rank))
                                        @foreach ($position_rank as  $key=>$position)
                                            <option value="{{$key}}" {{ (isset($SiteSettings->FinalApproval ) && $SiteSettings->FinalApproval ==$key ?'selected': '') }}>{{ $position }}</option>
                                        @endforeach
                                    @endif
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
                                        <img id="Resortimg" class="logo-img" width="100px"accept="image/*"  src="{{ Common::GetResortLogo($Resort->id) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 1 <span class="red-mark">*</span> </label>

                                <input type="text" id="address1" name="address1" class="form-control" value="{{ old('address1',isset($Resort->address1) ? $Resort->address1 : '') }}"/>
                            </div>
                            <div class="col-md-4">
                                <label for="emp-grade-select" class="form-label">Address Line 2 <span class="red-mark">*</span> </label>
                                <input type="text" id="address2" name="address2" class="form-control" value="{{ old('address2',isset($Resort->address2) ? $Resort->address2 : '') }}"/>
                            </div>




                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">Country <span class="red-mark">*</span></label>
                                <input type="text" id="country " name="country" class="form-control" placeholder="Please enter country " value="{{ old('country ',isset($Resort->country ) ? $Resort->country : '') }}"/>
                            </div>
                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">ATOLL <span class="red-mark">*</span></label>
                                <input type="text" id="state " name="state" class="form-control" placeholder="Please enter ATOLL " value="{{ old('ATOLL',isset($Resort->state ) ? $Resort->state : '') }}"/>
                            </div>
                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">City <span class="red-mark">*</span> </label>
                                <input type="text" id="city" name="city" class="form-control" placeholder="Please enter City" value="{{ old('city',isset($Resort->city) ? $Resort->city : '') }}"/>
                            </div>
                            <div class="col-md-3">
                                <label for="emp-grade-select" class="form-label">Pincode <span class="red-mark">*</span></label>
                                <input type="text" id="zip " name="zip" class="form-control" placeholder="Please enter Pincode " value="{{ old('zip ',isset($Resort->zip ) ? $Resort->zip : '') }}"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                    <div class="mb-2">
                        <h6>Footer  Details</h6>
                    </div>
                    <textarea name="footer" class="form-control" id=""  rows="3">  {{ old('footer ',isset($SiteSettings->Footer)  &&  $SiteSettings->Footer !="" ? $SiteSettings->Footer :config('settings.Footer')) }}</textarea>
                </div>
                </div>
                {{-- <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="header_img">Select Header Image </label>
                        <div class="uploadFile-block">
                            <div class="uploadFile-btn">
                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                <input type="file" name="header_img" id="header_img" >
                            </div>
                            <div class="uploadFile-text">
                                <img id="header_show_img" class="logo-img" width="100px" accept="image/*" src="{{  Common::GetResortSiteSettings($resort->resort_id,(isset($SiteSettings->header_img)?$SiteSettings->header_img : '')) }}">
                            </div>
                        </div>
                        <!-- <div class="d-flex align-items-center">


                            <div class="uploadFile-block position-relative">
                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>

                            </div>
                        </div> -->
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="footer_img">Select Footer Image </label>
                        <div class="uploadFile-block">
                            <div class="uploadFile-btn">
                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                <input type="file" name="footer_img" id="footer_img" >
                            </div>
                            <div class="uploadFile-text">
                                <img id="footer_show_img" class="logo-img" width="100px" accept="image/*" src="{{Common::GetResortSiteSettings($resort->resort_id,(isset($SiteSettings->header_img)?$SiteSettings->footer_img : '')) }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="header_img">Select Authorized Signature Image </label>
                        <div class="uploadFile-block">
                            <div class="uploadFile-btn">
                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                <input type="file" name="signature_img" id="signature_img"  >
                            </div>
                            <div class="uploadFile-text">
                                <img id="signature_show_img" class="logo-img" width="100px" accept="image/*" src="{{  Common::GetResortSiteSettings($resort->resort_id,(isset($SiteSettings->header_img)?$SiteSettings->signature_img : '')) }}">
                            </div>
                        </div>
                    </div>
                </div> --}}



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
@endsection

@section('import-css')
@endsection

@section('import-scripts')
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
                    // FX rate fields are read-only and pre-populated server-side
                    // with the canonical 15.42 rate. MVRtoDoller is now a hidden
                    // derived value (no user input → no validation needed).
                    // DollertoMVR is still in the payload but locked at 15.42,
                    // so we only sanity-check presence — length rules removed
                    // since "15.42" was failing the previous min:2 / max:7 mix.
                    DollertoMVR: {
                        required: true,
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
                    // MVRtoDoller validator removed — the field is now a
                    // hidden derived value, never user-input.
                    DollertoMVR:{
                        required: "Exchange rate must be set (1 USD = 15.42 MVR).",
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
                        url: "{{ route('resort.updatesitesettings') }}", // Ensure route is correct
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
                                // Full reload after save so the brand-logo
                                // preview AND every other place that calls
                                // Common::GetResortLogo (header bar, etc.)
                                // pick up the new image. The server-side
                                // cache-buster (?v=<updated_at>) makes the
                                // browser fetch the fresh file from Wasabi
                                // / S3 / local instead of the cached copy.
                                // Delay long enough for the toaster to read.
                                setTimeout(function () {
                                    window.location.reload();
                                }, 900);
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
@endsection
