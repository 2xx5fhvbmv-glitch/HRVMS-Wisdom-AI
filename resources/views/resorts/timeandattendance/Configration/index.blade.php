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
                            <span>Time And Attendance</span>
                            <h1>{{ $page_title }}</h1>
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
                <div class="row g-4">
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <h3>Attendance Data</h3>
                            </div>
                            <div class="card-body">
                                <h5 class="mb-3">Export Attendance</h5>
                                <!-- Export Attendance Form -->
                                <form id="exportAttendance" method="POST" action="{{ route('attendance.export') }}" class="mb-4" data-parsley-validate>
                                    @csrf
                                    <div class="row g-3 align-items-center">
                                        <!-- Start Date -->
                                        <div class="col-md-4">
                                            <label for="start_date" class="form-label">Start Date:</label>
                                            <input type="text" id="start_date" name="start_date"
                                                class="form-control datepicker" placeholder="dd-mm-yyyy" required data-parsley-errors-container="#start-date-error">
                                            <div id="start-date-error"></div>
                                        </div>
                                        <!-- End Date -->
                                        <div class="col-md-4">
                                            <label for="end_date" class="form-label">End Date:</label>
                                            <input type="text" id="end_date" name="end_date"
                                                class="form-control datepicker" placeholder="dd-mm-yyyy" required
                                                data-parsley-date-after="#start_date"
                                                data-parsley-date-after-message="End Date must be after Start Date"
                                                data-parsley-errors-container="#end-date-error">
                                            <div id="end-date-error"></div>
                                        </div>
                                        <!-- Export Button -->
                                        <div class="col-md-4 text-end">
                                            <button type="submit" class="btn btn-themeBlue">Export Employees</button>
                                        </div>
                                    </div>
                                </form>

                                <hr>
                                <h5 class="mb-3">Import Attendance</h5>
                                <!-- File Upload Section -->
                                <form id="ImportAttandanceForm">
                                    @csrf
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('resort.timeandattendance.DownloadAttendanceTemplate') }}" class="btn btn-themeSkyblue btn-sm">
                                                Download Template
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <small></small>
                                            {{-- <small id="error-message" class="text-themeDanger" >Only Excel files (.xls, .xlsx) are accepted.</small> --}}
                                            <div class="uploadFile-btn me-0">
                                                <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                                    onclick="document.getElementById('UploadImportattandance').click();">
                                                    Upload File
                                                </a>
                                                <input type="file" id="UploadImportattandance" name="UploadImportattandance"
                                                    style="opacity: 0; position: absolute; z-index: -1;"
                                                    onchange="displayImportFileName()">
                                                <div id="fileNameImportFile" style="margin-top: 10px; color: #333;"></div>
                                            </div>
                                            <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                                        </div>
                                    </div>
                            </div>
                            {{-- <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div> --}}
                        </form>
                        </div>
                        <!-- <div class="card mb-30">
                            <div class="card-title">
                                <h3>Upload past Data</h3>
                            </div>
                            <form>
                                <div class="mb-2">
                                    <select class="form-select  ResortDivision" id="ResortDivision" name='ResortDivision'
                                        aria-label="Default select example">
                                        <option> </option>
                                        @if ($resort_divisions->isNotEmpty())
                                            @foreach ($resort_divisions as $resort_division)
                                                <option value="{{ $resort_division->id }}">{{ $resort_division->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <select class="form-select select2 Department" aria-label="Default select example">
                                        <option></option>

                                    </select>
                                </div>
                                <div class="mb-2">
                                    <select class="form-select  Section" aria-label="Default select example">
                                        <option></option>

                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select select2 Position" aria-label="Default select example">
                                        <option></option>

                                    </select>
                                </div>
                                <div class="mb-3">
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input type="file" id="uploadFile">
                                        </div>
                                        <div class="uploadFile-text">PDF or Excel</div>
                                    </div>
                                </div>
                            </form>
                            <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                            </div>
                        </div> -->
                        <div class="card">
                            <div class="card-title">
                                <h3>Color Theme</h3>
                            </div>
                            <form id="color-theme-form">
                                @csrf()
                                <div class="row gx-md-4 g-3 mb-3" id="color-theme-container">
                                    @if ($colorThemes)
                                        @foreach ($colorThemes as $index => $theme)
                                            <div class="col-sm-6 theme-row" data-theme-id="{{ $theme->id }}">
                                                <div class="inputCustom-color">
                                                    <input type="text" name="themes[{{ $index }}][name]"
                                                        placeholder="Label" class="form-control mb-2"
                                                        value="{{ $theme->name }}">
                                                    <input type="color" name="themes[{{ $index }}][color]"
                                                        value="{{ $theme->color }}" style="top: 28% !important;">
                                                    <button type="button"
                                                        class="btn btn-danger remove-theme-btn">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <!-- Default Themes -->
                                        <div class="col-sm-6 theme-row" data-theme-id="new">
                                            <div class="inputCustom-color">
                                                <input type="text" name="themes[0][name]"
                                                    placeholder="Label (e.g., On Time)" class="form-control mb-2"
                                                    value="On Time">
                                                <input type="color" name="themes[0][color]" value="#63C67E" style="top: 28% !important;">
                                                <button type="button"
                                                    class="btn btn-danger remove-theme-btn">Remove</button>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 theme-row" data-theme-id="new">
                                            <div class="inputCustom-color">
                                                <input type="text" name="themes[1][name]"
                                                    placeholder="Label (e.g., Late)" class="form-control mb-2"
                                                    value="Late">
                                                <input type="color" name="themes[1][color]" value="#A264F7" style="top: 28% !important;">
                                                <button type="button"
                                                    class="btn btn-danger remove-theme-btn">Remove</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <a href="#" id="add-more-theme" class="btn btn-themeSkyblue">Add More</a>
                                </div>
                            </form>
                            <div class="card-footer text-end">
                                <button type="button" id="submit-themes" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <h3>Shift Settings</h3>
                            </div>
                            <form id="ShirfFrom" data-parsley-validate>
                                @csrf()
                                <div class="shift-main">
                                    <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm mb-3 add-shift">Add
                                        Shift</a>

                                    <!-- Dynamic Shift Blocks -->
                                    <div class="shift-block AppendShift">
                                        <input type="hidden" name="resort_id" value="{{ $resort_id }}">
                                        @if ($ShiftSettings->isNotEmpty())
                                            @foreach ($ShiftSettings as $s)
                                                <div class="row align-items-end g-2 mb-2 shift-row">
                                                    <input type="hidden" name="id[]" value="{{ $s->id }}">

                                                    <div class="col">
                                                        <label for="shift-name" class="form-label">SHIFT NAME</label>
                                                        <input type="text" name="ShiftName[]"
                                                            class="form-control shift-name" placeholder="First Shift"
                                                            required data-parsley-required="true"
                                                            data-parsley-required-message="Please enter a shift name"
                                                            value="{{ $s->ShiftName }}">
                                                    </div>
                                                    <div class="col">
                                                        <label for="start-time" class="form-label">Start Time</label>
                                                        <input type="time" class="form-control start-time"
                                                            name="StartTime[]" required data-parsley-required="true"
                                                            data-parsley-required-message="Please enter a start time"
                                                            value="{{ $s->StartTime }}">
                                                    </div>
                                                    <div class="col">
                                                        <label for="end-time" class="form-label">End Time</label>
                                                        <input type="time" class="form-control end-time"
                                                            name="EndTime[]" required data-parsley-required="true"
                                                            data-parsley-required-message="Please enter an end time"
                                                            value="{{ $s->EndTime }}">
                                                    </div>
                                                    <div class="col-auto">
                                                        <a href="#"
                                                            class="btn btn-themeBlue btn-sm btn-minus minus-shift"
                                                            data-id="{{ $s->id }}">
                                                            <i class="fa-solid fa-minus"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="row align-items-end g-2 mb-2 shift-row">
                                                <div class="col">
                                                    <label for="shift-name" class="form-label">SHIFT NAME</label>
                                                    <input type="text" name="ShiftName[]"
                                                        class="form-control shift-name" placeholder="First Shift" required
                                                        data-parsley-required="true"
                                                        data-parsley-required-message="Please enter a shift name">
                                                </div>
                                                <div class="col">
                                                    <label for="start-time" class="form-label">Start Time</label>
                                                    <input type="time" class="form-control start-time"
                                                        name="StartTime[]" required data-parsley-required="true"
                                                        data-parsley-required-message="Please enter a start time">
                                                </div>
                                                <div class="col">
                                                    <label for="end-time" class="form-label">End Time</label>
                                                    <input type="time" class="form-control end-time" name="EndTime[]"
                                                        required data-parsley-required="true"
                                                        data-parsley-required-message="Please enter an end time">
                                                </div>
                                                <div class="col-auto">
                                                    <a href="#"
                                                        class="btn btn-themeBlue btn-sm btn-minus minus-shift"
                                                        data-id="0">
                                                        <i class="fa-solid fa-minus"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div>
                            </form>



                        </div>
                        <div class="card mb-30 @if(Common::checkRouteWisePermission('resort.upcomingholiday.list',config('settings.resort_permissions.create')) == false) d-none @endif">
                            <div class="card-title">
                                <h3>Public Holidays</h3>
                                <div class="text-end">
                                    <a href="{{ route('resort.timeandattendance.publicholidaylist') }}"
                                        class="a-link">View All</a>
                                </div>
                            </div>
                            <div class="row g-md-3 g-2 align-items-center mb-3">
                                    <div class="col-md-4"><a
                                        href="{{ URL::asset('resorts_assets/Resort_holiday/ResrortHolidayFormat.xlsx') }}"
                                        class="btn btn-themeSkyblue btn-sm">Download Template</a>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn me-0">
                                                <form id="fileUploadForm" enctype="multipart/form-data" parsley-validate>

                                                    <a href="#" class="btn btn-themeBlue btn-sm"
                                                        onclick="document.getElementById('fileUpload').click();">
                                                        Upload File
                                                    </a>

                                                    <input type="file" id="fileUpload" style="display:none"
                                                        accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm,.csv"
                                                        onchange="UplodeAttandancedisplayFileName()">
                                                    <div id="fileName" style="margin-top: 10px; color: #333;"></div>
                                                    <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                {{-- <div class="col-auto ms-auto"><a href="#" class="a-link">Find Gov. Public
                                        Holidays</a>
                                </div> --}}
                            </div>
                            <div class="col-auto mb-3"><span style="font-size: 14px; font-weight: 600;">OR</span></div>

                            <div class="col-md-4 justify-content-end">
                                <a href="javascript:void();" class="btn btn-themeSkyblue btn-sm AddPublicHolidays">Add Public
                                    Holidays</a>
                            </div>

                            {{-- <div class="card-footer text-end"><a href="#"
                                    class="btn btn-themeBlue btn-sm">Submit</a>
                            </div> --}}
                        </div>
                        <div class="card card-confiTimeAtten">
                            <div class="row align-items-center g-2 mb-md-3 mb-2">
                                <div class="col-auto">
                                    <h6>Geo-Fencing Attendance</h6>
                                </div>
                                <div class="col-auto">
                                    <button type='button' class="btn btn-themeBlue btn-sm AddGeoFancing"
                                        style="display:none">Add Geo-Fencing</button>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input flexSwitchCheckDefaultCheck" type="checkbox"
                                            role="switch" id="flexSwitchCheckDefault" checked>
                                        <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center g-2 ">
                                <div class="col-auto">
                                    <h6>Mobile Attendance</h6>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="flexSwitchCheckDefault1">
                                        <label class="form-check-label" for="flexSwitchCheckDefault1"></label>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    {{-- Model Start --}}
    <!-- Modal Structure -->
    <div class="modal fade" id="GeoFacing-modal" tabindex="-1" aria-labelledby="GeoFacing-modalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-GeoFacingModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Add Geo Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="GeoLocation">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 align-items-center">
                            <!-- Latitude Input -->
                            <input type="hidden" name="resort_id" value="{{ $resort_id }}">

                            <div class="col-md-12">
                                <input type="text" class="form-control" placeholder="Latitude" id="latitude"
                                    value="{{ isset($ResortGeoLocation) && $ResortGeoLocation->latitude != null ? $ResortGeoLocation->latitude : '' }}"
                                    name="latitude">
                            </div>
                            <!-- Longitude Input -->
                            <div class="col-md-12">
                                <input type="text" class="form-control" placeholder="Longitude" id="longitude"
                                    name="longitude"
                                    value="{{ isset($ResortGeoLocation) && $ResortGeoLocation->longitude != null ? $ResortGeoLocation->longitude : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="PublicHoliday-modal" tabindex="-1" aria-labelledby="PublicHoliday-modalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-PublicHoliday">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Add Public Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="PublicHolidayForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 align-items-center">
                            <!-- Latitude Input -->
                            <input type="hidden" name="resort_id" value="{{ $resort_id }}">

                            <div class="col-md-12">
                                <select name="PublicHoliday" id="PublicHoliday" class="form-select">
                                    <option value="" data-id="0">Select Holiday</option>
                                    @foreach ($PublicHoliday as $p)
                                        <option value="{{ $p->id }}" data-id="{{ $p->id }}"
                                            data-date='{{ $p->holiday_date }}'>{{ $p->name }}</option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="col-md-12">
                                <input type="text" name="ResortPublicHolidayDate" id="ResortPublicHolidayDate"
                                    class="form-control" style="display:none" readonly></input>
                            </div>

                            <label class="mx-auto d-block text-center mb-2">OR</label>
                            <div class="col-md-12">
                                <input type="date" name="PublicHolidaydate" placeholder="Holiday Date"
                                    id="PublicHolidaydate" class="form-control"></input>
                            </div>

                            <div class="col-md-12">
                                <input type="text" name="PublicHolidayName" placeholder="Holiday Name"
                                    id="PublicHolidayName" class="form-control"></input>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
             // Custom validator for checking if one date is after another
            window.Parsley.addValidator('dateAfter', {
                validateString: function (value, selector) {
                    let startDate = document.querySelector(selector).value;
                    if (!startDate) return true; // skip if no start date
                    let start = new Date(startDate.split("-").reverse().join("-"));
                    let end = new Date(value.split("-").reverse().join("-"));
                    return end > start;
                },
                messages: {
                    en: 'End Date must be after Start Date.'
                }
            });

            $("#fileUploadForm").parsley();
            $("#start_date , #end_date").datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
            });
            $("#ResortDivision").select2({
                placeholder: "Select Division"
            });
            $(".Department").select2({
                placeholder: "Select Department"
            });
            $(".Section").select2({
                placeholder: "select Section"
            });
            $(".Position").select2({
                placeholder: "select Position"
            });


            $('#ImportAttandanceForm').validate({
                rules: {
                    UploadImportattandance: {
                        required: true,
                    }
                },
                messages: {
                    UploadImportattandance: {
                        required: "Please select a file to upload.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    formData.append('fileUpload', $('#fileUpload')[0].files[0]); // Append the file to the form data

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.ImportAttandance') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
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
                            if(response.responseJSON)

                            {
                                var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                            }

                        }
                    });
                }
            });

            $('#fileUploadForm').validate({
                rules: {
                    fileUpload: {
                        required: true,
                        extension: "xls|xlsx" // Only allow .xls or .xlsx files
                    }
                },
                messages: {
                    fileUpload: {
                        required: "Please select a file to upload.",
                        extension: "Only .xls or .xlsx files are allowed."
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "fileUpload") {
                        $('#file-extension-error').text('Only .xls or .xlsx files are allowed.').show();
                    } else {
                        error.insertAfter(element);
                    }
                },
                success: function(label, element) {
                    if ($(element).attr("name") == "fileUpload") {
                        $('#file-extension-error').hide();
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    formData.append('fileUpload', $('#fileUpload')[0].files[
                    0]); // Append the file to the form data

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.HolidayfileUpload') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {

                                toastr.error(response.message, "Error", {
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
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });

            $(document).on('change', '.ResortDivision', function() {
                $.ajax({
                    url: "{{ route('resort.get.ResortDivision') }}",
                    type: "post",
                    data: {
                        division_id: $(this).val(),
                    },
                    success: function(data) {

                        // Clear the dropdown and add a placeholder option
                        $(".Department").html('<option value="">Select Department</option>');
                        if (data.success == true) {
                            let string = '<option></option>';
                            // Append new options
                            $.each(data.data, function(key, value) {

                                string += '<option value="' + value.id + '">' + value
                                    .name + '</option>';
                            });

                            $(".Department").html(string);

                        } else {
                            let string = '<option></option>';
                        }
                    },
                    error: function(response) {
                        toastr.error("Department Not Found", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });

            $(document).on('change', '.Department', function() {
                var deptId = $(this).val();
                $.ajax({
                    url: "{{ route('resort.ta.PositionSections') }}",
                    type: "post",
                    data: {
                        deptId: deptId
                    },
                    success: function(d) {
                        // Clear the dropdown and add a placeholder option


                        if (d.success == true) {

                            let string = '<option></option>';
                            $.each(d.data.ResortPosition, function(key, value) {
                                string += '<option value="' + value.id + '">' + value
                                    .position_title + '</option>';
                            });
                            $(".Position").html(string);

                            let string1 = '<option></option>';
                            $.each(d.data.ResortSection, function(key, value) {
                                string1 += '<option value="' + value.id + '">' + value
                                    .name + '</option>';
                            });
                            $(".Section").html(string1);

                        }
                    },
                    error: function(response) {
                        toastr.error("Position Not Found", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
            $('#GeoFacing-modal').on('hidden.bs.modal', function() {
                $(this).attr('aria-hidden', 'true'); // Reapply aria-hidden on modal close
            });
            getcheckit();
            // Initialize Parsley validation on the form
            $('#ShirfFrom').parsley();

            // Add Shift Block Dynamically
            $(".add-shift").on("click", function(e) {
                e.preventDefault();

                const newShift = `
            <div class="row align-items-end g-2 mb-2 shift-row">
                <div class="col">
                    <label for="shift-name" class="form-label">SHIFT NAME</label>
                    <input type="text" name="ShiftName[]" class="form-control shift-name" required data-parsley-required-message="Please enter a shift name">
                </div>
                <div class="col">
                    <label for="start-time" class="form-label">Start Time</label>
                    <input type="time" class="form-control start-time" name="StartTime[]" required data-parsley-required-message="Please enter a start time">
                </div>
                <div class="col">
                    <label for="end-time" class="form-label">End Time</label>
                    <input type="time" class="form-control end-time" name="EndTime[]" required data-parsley-required-message="Please enter an end time">
                </div>
                <div class="col-auto">
                    <a href="#" class="btn btn-themeBlue btn-sm btn-minus minus-shift">
                        <i class="fa-solid fa-minus"></i>
                    </a>
                </div>
            </div>
            `;
                $(".AppendShift").append(newShift);


                $('#ShirfFrom').parsley().reset();
                $('#ShirfFrom').parsley().validate();
            });

            // Remove Shift Block
            $(document).on("click", ".minus-shift", function(e) {
                e.preventDefault();

                let id = $(this).data('id');


                // Show SweetAlert2 confirmation
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {

                        if (id != 0) {
                            $(this).closest(".shift-row").remove();
                            $.ajax({
                                url: "{{ route('resort.timeandattendance.removeShift') }}",
                                type: "POST",
                                data: {
                                    id: id
                                }, // Pass the id in the data
                                success: function(response) {
                                    $('#respond-HoldModel').modal('hide');
                                    $(this).closest(".shift-row").remove();
                                    if (response.success) {
                                        // Update the view with the new data

                                        toastr.success(response.message, "Success", {
                                            positionClass: 'toast-bottom-right'
                                        });

                                        // Reset form validation if needed
                                        $('#ShirfFrom').parsley().reset();
                                        $('#ShirfFrom').parsley().validate();
                                    } else {

                                        toastr.error(response.message, "Error", {
                                            positionClass: 'toast-bottom-right'
                                        });
                                    }
                                },
                                error: function(response) {
                                    var errors = response.responseJSON;
                                    var errs = '';
                                    $.each(errors.errors, function(key,
                                    error) { // Adjust according to your response format
                                        errs += error + '<br>';
                                    });
                                    toastr.error(errs, {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            });

                        } else {
                            $(this).closest(".shift-row").remove();
                        }


                    } else {
                        // User canceled, do nothing
                        return;
                    }
                });
                $('#ShirfFrom').parsley().reset();
                $('#ShirfFrom').parsley().validate();
            });

            $(document).on("blur", ".start-time, .end-time", function() {
                const parentRow = $(this).closest(".shift-row");
                const startTime = parentRow.find(".start-time").val();
                const endTime = parentRow.find(".end-time").val();

                // Clear any previous Parsley validation messages
                parentRow.find(".end-time").parsley().reset();

                if (startTime && endTime) {
                    const start = new Date(`1970-01-01T${startTime}`);
                    const end = new Date(`1970-01-01T${endTime}`);

                    if (start >= end) {
                        // Manually trigger Parsley validation error for End Time
                        parentRow.find(".end-time")
                            .attr("data-parsley-error-message", "End Time must be later than Start Time")
                            .parsley()
                            .validate();
                    }
                }
            });


            // Form Submission
            $("#ShirfFrom").on("submit", function(e) {
                e.preventDefault();

                // Ensure form passes Parsley validation
                const isValid = $(this).parsley().isValid();
                if (!isValid) {
                    return; // Stop submission if validation fails
                }

                // Perform AJAX request if everything is valid
                const formData = new FormData(this);
                $.ajax({
                    url: "{{ route('resort.timeandattendance.ShiftStore') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success)
                        {

                            toastr.success(response.message, "Success", {
                                positionClass: "toast-bottom-right",
                            });
                            window.location.reload();

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: "toast-bottom-right",
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {

                                toastr.error(messages, "Error", {
                                    positionClass: "toast-bottom-right",
                                });
                            });
                        } else {
                            // General errors
                            toastr.error("An unexpected error occurred. Please try again.",
                                "Error", {
                                    positionClass: "toast-bottom-right",
                                });
                        }
                    },
                });
            });

            /// Geo Fencing Location




            $(".flexSwitchCheckDefaultCheck").on("change", function(e) {

                getcheckit();
            });

            $(document).on("click", ".AddGeoFancing", function() {

                $("#GeoFacing-modal").modal('show');
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            if ($("#latitude").val() == '')

                            {
                                $("#latitude").val(position.coords.latitude);

                            }
                            if ($("#longitude").val() == '') {
                                $("#longitude").val(position.coords.longitude);

                            }
                        },
                        function(error) {
                            console.error("Error obtaining location: ", error.message);
                            toastr.error("Unable to fetch location. Please allow location access.",
                                "Error", {
                                    positionClass: "toast-bottom-right",
                                });
                        }
                    );
                } else {
                    toastr.error("Geolocation is not supported by your browser.", "Error", {
                                    positionClass: "toast-bottom-right",
                                });
                }

            });

            function getcheckit() {
                if ($('.flexSwitchCheckDefaultCheck').is(':checked')) {
                    $(".AddGeoFancing").show();
                } else {
                    $(".AddGeoFancing").hide();
                }
            }

            $('#GeoLocation').validate({
                rules: {
                    latitude: {
                        required: true

                    },
                    longitude: {
                        required: true
                    }
                },
                messages: {
                    latitude: {
                        required: "Please Calculate and enter Resort Latitude.",
                    },
                    longitude: {
                        required: "Please Calculate and enter Resort longitude..",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.GeoFencing') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,

                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                                $("#GeoFacing-modal").modal("hide");

                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });
            $(document).on("click", ".AddPublicHolidays", function() {

                $("#PublicHoliday-modal").modal('show');
                $("#PublicHoliday").select2();
            });

            $(document).on("change", "#PublicHoliday", function() {
                let selectedOption = $(this).find(':selected');
                let dataId = selectedOption.data('id');

                if (dataId == 0) {
                    $("#PublicHolidaydate").attr('disabled', false);
                    $("#PublicHolidayName").attr('disabled', false);
                    $("#ResortPublicHolidayDate").val(''); // Clear the date
                } else {
                    $("#PublicHolidaydate").attr('disabled', true);
                    $("#PublicHolidayName").attr('disabled', true);

                    let dates = selectedOption.data('date');
                    if (dates) {
                        // Ensure the date is in YYYY-MM-DD format
                        let formattedDate = '';

                        // Handle different date format possibilities
                        if (dates.includes('-')) {
                            // If already in YYYY-MM-DD format
                            formattedDate = dates;
                        } else if (dates.includes('/')) {
                            // Convert from DD/MM/YYYY to YYYY-MM-DD
                            let parts = dates.split('/');
                            formattedDate =
                                `${parts[2]}/${parts[1].padStart(2, '0')}/${parts[0].padStart(2, '0')}`;
                        }



                        $("#ResortPublicHolidayDate").val(formattedDate);
                        $("#ResortPublicHolidayDate").show();
                    } else {
                        $("#ResortPublicHolidayDate").val('');
                    }
                }
            });



            $(document).on("keyup", "#PublicHolidaydate , #PublicHolidayName", function() {

                if ($(this).val() != "") {
                    $("#PublicHoliday").attr('disabled', true);
                } else {
                    $("#PublicHoliday").attr('disabled', false);
                }

            });




            $.validator.addMethod(
                "requirePublicHolidayNameOrDate",
                function(value, element) {
                    // Check if the current field is valid based on the other field's value
                    if ($('#PublicHolidayName').val() !== '' && $('#PublicHolidaydate').val() !== '') {
                        return false; // Both fields cannot be filled
                    }
                    return true; // Valid if only one is filled
                },
                "You cannot fill both Holiday Name and Holiday Date. Please choose one."
            );

            $('#PublicHolidayForm').validate({
                rules: {
                    PublicHolidaydate: {
                        required: function() {
                            // Validate PublicHolidaydate if both other fields are empty
                            return $('#PublicHolidayName').val() === '' && $('#PublicHolidaydate')
                            .val() === '';
                        }
                    },
                    PublicHolidayName: {
                        required: function() {
                            // Validate PublicHolidayName if PublicHolidaydate is disabled
                            return $('#PublicHolidaydate').is(':disabled');
                        }
                    },
                    PublicHolidaydate: {
                        required: function() {
                            // Validate PublicHoliday if PublicHolidaydate is disabled
                            return $('#PublicHolidaydate').is(':disabled');
                        }
                    }
                },
                messages: {
                    PublicHolidaydate: {
                        required: "Please select a Holiday Date if Name and Public Holiday are empty."
                    },
                    PublicHolidayName: {
                        required: "Please enter a Holiday Name if Holiday Date is disabled."
                    },
                    PublicHolidaydate: {
                        required: "Please select a Public Holiday if Holiday Date is disabled."
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.ResortHoliday') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-HoldModel').modal('hide');
                            $("#PublicHoliday-modal").modal('hide');
                            if (response.success) {


                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                             $("#PublicHoliday-modal").modal('hide');
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key,
                            error) { // Adjust according to your response format
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });


        });

        function displayImportFileName() {
            var fileInput = document.getElementById('UploadImportattandance');
            var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';


            document.getElementById('fileNameImportFile').innerText = fileName;

            // Trigger validation and submit only if valid
            var $form = $('#ImportAttandanceForm');

            if (fileInput.files.length > 0) {
                if ($form.valid()) {
                    $form.submit(); // This will call the `submitHandler` in `.validate({})`
                }
            } else {
                toastr.error('Please select a file before submitting.', "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        }
        function UplodeAttandancedisplayFileName() {
            var fileInput = document.getElementById('fileUpload');
            var fileName = fileInput.files[0] ? fileInput.files[0].name : 'No file selected';
            document.getElementById('fileName').innerText = fileName;
            $('#fileUploadForm').submit();

        }


        document.addEventListener('DOMContentLoaded', function() {
            // console.log('DOM fully loaded and parsed');

            const container = document.getElementById('color-theme-container');
            const addMoreButton = document.getElementById('add-more-theme');
            const submitButton = document.getElementById('submit-themes');

            // Dynamically add new color theme inputs
            addMoreButton.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent page reload
                let index = container.children.length; // Get the number of current rows
                console.log(index);
                let newThemeHTML = `
                <div class="col-sm-6 theme-row" data-theme-id="new">
                    <div class="inputCustom-color">
                        <input type="text" name="themes[${index}][name]" placeholder="Label (e.g., Overtime)" class="form-control mb-2">
                        <input type="color" name="themes[${index}][color]" value="#000000" style="top: 28% !important;">
                        <button type="button" class="btn btn-danger remove-theme-btn">Remove</button>
                    </div>
                </div>
            `;
                container.insertAdjacentHTML('beforeend', newThemeHTML); // Append new theme inputs
            });

            // Handle the "Remove" button click event
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-theme-btn')) {
                    e.preventDefault();
                    const themeRow = e.target.closest('.theme-row');
                    const themeId = themeRow.getAttribute('data-theme-id');
                    console.log(themeId); // This will log the correct theme ID

                    if (themeId !== 'new') {
                        // Send a request to delete the theme from the database
                        const formData = new FormData();
                        formData.append('theme_id', themeId); // Append the theme ID to FormData

                        fetch("{{ route('colorThemes.delete') }}", {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content,
                                },
                                body: formData, // Send FormData instead of JSON
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    toastr.success(data.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    themeRow.remove(); // Remove the row from the UI
                                } else {
                                    toastr.error(data.message, "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            })
                            .catch(err => {
                                toastr.error("An error occurred. Please try again.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                    } else {
                        themeRow.remove(); // Remove the row if it's newly added (not saved yet)
                    }
                }
            });


            // Submit form via AJAX
            submitButton.addEventListener('click', function() {
                const form = document.getElementById('color-theme-form');
                // console.log(form);
                let formData = new FormData(form);

                fetch("{{ route('colorThemes.save') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {

                        if (data.success == true) {
                            toastr.success(data.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            }); // Show success message

                            // Clear the current theme container
                            const container = document.getElementById('color-theme-container');
                            container.innerHTML = ''; // Remove all existing rows

                            // Dynamically add saved themes
                            data.themes.forEach((theme, index) => {
                                let themeHTML = `
                            <div class="col-sm-6 theme-row" data-theme-id="${theme.id}">
                                <div class="inputCustom-color">
                                    <input type="text" name="themes[${index}][name]" placeholder="Label" class="form-control mb-2" value="${theme.name}">
                                    <input type="color" name="themes[${index}][color]" value="${theme.color}" style="top: 28% !important;">
                                    <button type="button" class="btn btn-danger remove-theme-btn">Remove</button>
                                </div>
                            </div>
                        `;
                                container.insertAdjacentHTML('beforeend', themeHTML);
                            });
                        } else {
                            toastr.error(data.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            }); // Show error message
                        }
                    });
            });
        });
    </script>
    <script>
        // Validate file extension on file input change
        document.getElementById('UploadImportattandance').addEventListener('change', function() {
            const fileInput = this;
            const filePath = fileInput.value;
            const allowedExtensions = /(\.xls|\.xlsx)$/i;
            const errorSpan = document.getElementById('file-extension-error');
            if (!allowedExtensions.exec(filePath)) {
                errorSpan.textContent = 'Only .xls or .xlsx files are allowed.';
                errorSpan.style.display = 'block';
                fileInput.value = '';
                document.getElementById('fileNameImportFile').innerText = '';
            } else {
                errorSpan.style.display = 'none';
            }
        });


    </script>
@endsection
