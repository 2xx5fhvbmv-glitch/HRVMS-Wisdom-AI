@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                        <span>payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-lg-6 ">
                      <div class="card mb-30">
                        <div class="card-title">
                            <h3>Cutoff Day</h3>
                        </div>
                        <form id="cutOffDateForm">
                            @csrf
                            <div class="mb-3 ">
                                <label for="cutoff_day" class="form-label">CutOff Day</label>
                                <select name="cutoff_day" id="cutoff_day" class="form-select select2t-none">
                                    @for($i=1;$i<=28;$i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                    <!-- <div class="card mb-30">
                        <div class="card-title">
                            <h3>Earnings</h3>
                        </div>

                        <div class="row g-2 align-items-center mb-3">
                            <div class="col-auto">
                                <a href="{{route('allowances.download-template')}}" class="btn btn-themeSkyblue btn-sm">Download Template</a>
                            </div>
                            <form id="ImportEarningsForm">
                                @csrf
                                <div class="col-auto">
                                    <div class="uploadFile-btn me-0">
                                        <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                        onclick="document.getElementById('ImportEarnings').click();">
                                        Upload File
                                        </a>
                                        <input type="file" id="ImportEarnings" name="ImportEarnings"
                                        accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;"
                                        onchange="displayEarningImportFileName()">
                                        <div id="earningImportFile" style="margin-top: 10px; color: #333;"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                                </div>
                            </form>
                        </div>

                        <form id="earningsForm">
                            <div class="earnings-main">
                                <div class="earnings-block mb-md-4 mb-3">
                                    <div class="row align-items-end g-md-4 g-2 earnings-row">
                                        <div class="col-sm-6">
                                            <label for="allow_name1" class="form-label">ALLOWANCE NAME</label>
                                            <input type="text" class="form-control allow_name" id="allow_name1"
                                                placeholder="Allowance Name" name="allow_name[]">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="allow_type1" class="form-label">ALLOWANCE TYPE</label>
                                            <input type="text" class="form-control allow_type" id="allow_type1"
                                                placeholder="Allowance type" name="allow_type[]">
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <label for="" class="form-label mb-0 me-md-4 me-3">CURRENCY:</label>
                                                <label for="flexSwitchCheckDefault"
                                                    class="form-label mb-0 me-3">Rufiyaa</label>
                                                <div class="form-check form-switch form-switchTheme">
                                                    <input class="form-check-input currency-switch" type="checkbox" role="switch" id="flexSwitchCheckDefault" name="currency[]">
                                                    <label class="form-check-label" for="flexSwitchCheckDefault">USD</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-danger remove-earnings">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-themeSkyblue btn-sm mb-3 add-earnings">Add More</button>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div> -->
                    <!-- <div class="card mb-30">
                        <div class="card-title">
                            <h3>Bank Payroll Template</h3>
                        </div>
                        <form>
                            <div class="mb-md-3 mb-2">
                                <label for="" class="form-label">BANK</label>
                                <select class="form-select select2t-none" aria-label="Default select example">
                                    <option selected>Select Bank</option>
                                    <option value="1">aaa</option>
                                    <option value="2">bbb</option>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-auto">
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn me-0">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input type="file" id="uploadFile">
                                        </div>
                                        <div class="uploadFile-text"></div>
                                    </div>
                                </div>
                                <div class="col-auto"><a href="#" class="btn btn-themeSkyblue btn-sm">View</a></div>
                            </div>
                        </form>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->
                    <!-- <div class="card mb-30">
                        <div class="card-title">
                            <h3>Previous Month Payroll Upload</h3>
                        </div>
                        <form id="">
                            <div class="mb-md-3 mb-2">
                                <label for="" class="form-label">PAYROLL FOR</label>
                                <select class="form-select select2t-none" aria-label="Default select example">
                                    <option selected>Select Month</option>
                                    <option value="1">aaa</option>
                                    <option value="2">bbb</option>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-auto"><a href="#" class="btn btn-themeSkyblue btn-sm">Download
                                        Template</a></div>
                                <div class="col-auto">
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn me-0">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input type="file" id="uploadFile">
                                        </div>
                                        <div class="uploadFile-text"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                        </div>
                    </div> -->
                     <div class="card card-uploadSerCharg">
                        <div class="card-title">
                            <h3>Upload Previous Service Charge</h3>
                        </div>
                        <div class="row g-md-3 g-2 align-items-center mb-3">
                            <div class="col-auto">
                                <a href="{{ route('export.service-charges') }}" class="btn btn-themeSkyblue btn-sm">Download Template</a>
                            </div>
                            <form id="ImportServiceChargeForm">
                                @csrf
                                <div class="col-auto">
                                    <div class="uploadFile-btn me-0">
                                        <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                            onclick="document.getElementById('ImportServiceCharge').click();">
                                            Upload File
                                        </a>
                                        <input type="file" id="ImportServiceCharge" name="ImportServiceCharge"
                                            accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;" onchange="displayImportFileName()">
                                        <div id="fileNameImportFile" style="margin-top: 10px; color: #333;"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ">
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Deductions</h3>
                        </div>

                        <div class="row g-2 align-items-center mb-3">
                            <div class="col-auto">
                                <a href="{{route('deductions.download-template')}}" class="btn btn-themeSkyblue btn-sm">Download Template</a>
                            </div>
                            <form id="ImportDeductionForm">
                                @csrf
                                <div class="col-auto">
                                    <div class="uploadFile-btn me-0">
                                        <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                        onclick="document.getElementById('ImportDeductions').click();">
                                        Upload File
                                        </a>
                                        <input type="file" id="ImportDeductions" name="ImportDeductions"
                                        accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;"
                                        onchange="displayDeductonImportFileName()">
                                        <div id="deductionImportFile" style="margin-top: 10px; color: #333;"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                                </div>
                            </form>
                        </div>

                        <form id="deductionForm">
                            <div class="deduction-main">
                                <div class="deduction-block mb-md-4 mb-3">
                                    <div class="row align-items-end g-md-4 g-2 deduction-row">
                                        <div class="col-sm-6">
                                            <label for="deduction_name" class="form-label">DEDUCTION NAME</label>
                                            <input type="text" class="form-control deduction_name" id="deduction_name" placeholder="Deduction Name" name="deduction_name[]">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="deduction_type" class="form-label">Deduction type</label>
                                            <input type="text" class="form-control deduction_type" id="deduction_type" placeholder="Deduction type" name="deduction_type[]">
                                        </div>
                                        <div class="col">
                                            <div class="d-flex align-items-center">
                                                <label for="" class="form-label mb-0 me-md-4 me-3">CURRENCY:</label>
                                                <label for="flexSwitchCheckDefault"
                                                    class="form-label mb-0 me-3">Rufiyaa</label>
                                                <div class="form-check form-switch form-switchTheme">
                                                    <input class="form-check-input currency-switch" type="checkbox" role="switch" id="flexSwitchCheckDefault" name="currency[]">
                                                    <label class="form-check-label"
                                                        for="flexSwitchCheckDefault">USD</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-danger remove-deduction">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-themeSkyblue btn-sm mb-3 add-deduction">Add More</button>
                            <div class="mb-3">
                                <label for="maximum" class="form-label">MAXIMUM DEDUCTION LIMIT</label>
                                <input type="text" class="form-control maximum_limit" id="maximum"
                                    placeholder="Enter certain % of basic salary" name="maximum_limit">
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                  
                   
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    // new DataTable('#example');
    $(document).ready(function () {
        $('.select2t-none').select2();
        $('#ImportServiceChargeForm').validate({
            rules: {
                ImportServiceCharge: {
                    required: true,
                    extension: "csv|xls|xlsx|ods|xlsb|xlt|xltx|xltm" // Validate file types
                }
            },
            messages: {
                ImportServiceCharge: {
                    required: "Please select a file to upload.",
                    extension: "Please upload a valid file (csv, xls, xlsx, ods, xlsb, xlt, xltx, xltm)."
                }
            },
            submitHandler: function(form, event) {
                event.preventDefault(); // Prevent page refresh on submit

                var formData = new FormData(form);
                formData.append('fileUpload', $('#ImportServiceCharge')[0].files[0]); // Append the file to the form data
                $.ajax({
                    url: "{{ route('import.service-charges') }}", // Your route for file upload
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

        $('#ImportEarningsForm').validate({
            rules: {
                ImportEarnings: {
                    required: true,
                    extension: "csv|xls|xlsx|ods|xlsb|xlt|xltx|xltm" // Validate file types
                }
            },
            messages: {
                ImportEarnings: {
                    required: "Please select a file to upload.",
                    extension: "Please upload a valid file (csv, xls, xlsx, ods, xlsb, xlt, xltx, xltm)."
                }
            },
            submitHandler: function(form, event) {
                event.preventDefault(); // Prevent page refresh on submit

                var formData = new FormData(form);
                formData.append('fileUpload', $('#ImportEarnings')[0].files[0]); // Append the file to the form data
                $.ajax({
                    url: "{{ route('import.earnings') }}", // Your route for file upload
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

        $('#ImportDeductionForm').validate({
            rules: {
                ImportDeductions: {
                    required: true,
                    extension: "csv|xls|xlsx|ods|xlsb|xlt|xltx|xltm" // Validate file types
                }
            },
            messages: {
                ImportDeductions: {
                    required: "Please select a file to upload.",
                    extension: "Please upload a valid file (csv, xls, xlsx, ods, xlsb, xlt, xltx, xltm)."
                }
            },
            submitHandler: function(form, event) {
                event.preventDefault(); // Prevent page refresh on submit

                var formData = new FormData(form);
                formData.append('fileUpload', $('#ImportDeductions')[0].files[0]); // Append the file to the form data
                $.ajax({
                    url: "{{ route('import.deductions') }}", // Your route for file upload
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

        $('#cutOffDateForm').validate({
            rules: {
                cutoff_day: {
                    required: true,
                }
            },
            messages: {
                cutoff_day: {
                    required: "Please select cutoff day",
                }
            },
            submitHandler: function(form, event) {
                event.preventDefault(); // Prevent page refresh on submit
                var formData = new FormData(form);
                $.ajax({
                    url: "{{ route('save.cutoff.day') }}", // Your route for file upload
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
    });

    document.addEventListener('DOMContentLoaded', function () {
        const earningsMain = document.querySelector('.earnings-main');

        // Add More Fields
        document.querySelector('.add-earnings').addEventListener('click', function () {
            const newRow = `
                <div class="row align-items-end g-md-4 g-2 earnings-row">
                    <div class="col-sm-6">
                        <label for="allow_name" class="form-label">ALLOWANCE NAME</label>
                        <input type="text" class="form-control allow_name" placeholder="Allowance Name" name="allow_name[]">
                    </div>
                    <div class="col-sm-6">
                        <label for="allow_type" class="form-label">ALLOWANCE TYPE</label>
                        <input type="text" class="form-control allow_type" placeholder="Allowance Type" name="allow_type[]">
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center">
                            <label class="form-label mb-0 me-md-4 me-3">CURRENCY:</label>
                            <label class="form-label mb-0 me-3">Rufiyaa</label>
                            <div class="form-check form-switch form-switchTheme">
                                <input class="form-check-input currency-switch" type="checkbox" name="currency[]">
                            </div>
                            <label class="form-label mb-0 me-3">USD</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-danger remove-earnings">Remove</button>
                    </div>
                </div>`;
            earningsMain.insertAdjacentHTML('beforeend', newRow);
        });

        // Remove Fields
        earningsMain.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-earnings')) {
                e.target.closest('.earnings-row').remove();
            }
        });

        // Submit Form
        document.querySelector('#earningsForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Gather all entries
            const rows = document.querySelectorAll('.earnings-row');
            const earningsData = [];
            rows.forEach(row => {
                const allowName = row.querySelector('.allow_name').value;
                const allowType = row.querySelector('.allow_type').value;
                const currency = row.querySelector('.currency-switch').checked ? 'USD' : 'Rufiyaa';


                if (allowName && allowType) { // Validate entries
                    earningsData.push({ allow_name: allowName, allow_type: allowType, currency });
                }
            });
            // console.log(earningsData);

            // Validate data
            if (earningsData.length === 0) {
                // alert('Please fill in at least one entry.');
                toastr.error("Please fill in at least one entry.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Submit via AJAX
            $.ajax({
                url:"{{ route('earnings.submit') }}",
                type: "POST",
                data: {
                    earnings: earningsData, // Wrap data in an 'earnings' key
                    _token: "{{ csrf_token() }}" // Include CSRF token if necessary
                },
                success: function(response) {
                    if (response.success) {
                        // alert(response.message);
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Optionally, reset the form
                        document.querySelector('#earningsForm').reset();
                        // earningsMain.innerHTML = ''; // Clear all dynamic rows
                    } else {
                        toastr.error("Failed to save earnings.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        // alert('Failed to save earnings.');
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
        });

        //Deduction Code
        const deductionMain = document.querySelector('.deduction-main');

        // Add More Fields
        document.querySelector('.add-deduction').addEventListener('click', function (e) {
            e.preventDefault();

            const newRow = `
                <div class="deduction-block mb-md-4 mb-3">
                    <div class="row align-items-end g-md-4 g-2">
                        <div class="col-sm-6">
                            <label for="deduction_name" class="form-label">DEDUCTION NAME</label>
                            <input type="text" class="form-control deduction_name" placeholder="Deduction Name" name="deduction_name[]">
                        </div>
                        <div class="col-sm-6">
                            <label for="deduction_type" class="form-label">DEDUCTION TYPE</label>
                            <input type="text" class="form-control deduction_type" placeholder="Deduction Type" name="deduction_type[]">
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <label class="form-label mb-0 me-md-4 me-3">CURRENCY:</label>
                                <label class="form-label mb-0 me-3">Rufiyaa</label>
                                <div class="form-check form-switch form-switchTheme">
                                    <input class="form-check-input currency-switch" type="checkbox" name="currency[]">
                                    <label class="form-check-label">USD</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger remove-deduction">Remove</button>
                        </div>
                    </div>
                </div>`;
            deductionMain.insertAdjacentHTML('beforeend', newRow);
        });

        // Remove Fields
        deductionMain.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-deduction')) {
                e.preventDefault();
                e.target.closest('.deduction-block').remove();
            }
        });

        // Submit Form
        document.querySelector('#deductionForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Gather all deduction entries
            const rows = document.querySelectorAll('.deduction-block');
            const deductionsData = [];
            rows.forEach(row => {
                const deductionName = row.querySelector('.deduction_name').value;
                const deductionType = row.querySelector('.deduction_type').value;
                const currency = row.querySelector('.currency-switch').checked ? 'USD' : 'Rufiyaa';

                if (deductionName && deductionType) { // Validate entries
                    deductionsData.push({ deduction_name: deductionName, deduction_type: deductionType, currency });
                }
            });

            const maximumLimit = document.querySelector('#maximum').value;

            // Validate overall data
            if (deductionsData.length === 0 || !maximumLimit) {
                toastr.error("Please fill in all required fields.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Submit via AJAX
            $.ajax({
                url: "{{ route('deductions.submit') }}",
                type: "POST",
                data: {
                    deductions: deductionsData,
                    maximum_limit: maximumLimit,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Optionally, reset the form
                        document.querySelector('#deductionForm').reset();
                        // deductionMain.innerHTML = ''; // Clear all dynamic rows
                    } else {
                        toastr.error("Failed to save deductions.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.responseJSON) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessages = '';
                        Object.keys(errors).forEach(function (key) {
                            errorMessages += `${errors[key]}<br>`;
                        });
                        toastr.error(errorMessages, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });
    });

    function displayImportFileName() {
        var fileInput = document.getElementById('ImportServiceCharge');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';

        // Display the file name
        document.getElementById('fileNameImportFile').innerText = fileName;

        // Submit the form if a file is selected
        if (fileInput.files.length > 0) {
            $('#ImportServiceChargeForm').submit(); // Trigger the validation and AJAX submit
        }
        else
        {
            toastr.error('Please select a file before submitting.', "Error", {
                positionClass: 'toast-bottom-right'
            });
        }
    }

    function displayEarningImportFileName(){
        var fileInput = document.getElementById('ImportEarnings');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';

        // Display the file name
        document.getElementById('earningImportFile').innerText = fileName;

        // Submit the form if a file is selected
        if (fileInput.files.length > 0) {
            $('#ImportEarningsForm').submit(); // Trigger the validation and AJAX submit
        }
        else
        {
            toastr.error('Please select a file before submitting.', "Error", {
                positionClass: 'toast-bottom-right'
            });
        }
    }

    function displayDeductonImportFileName(){
        var fileInput = document.getElementById('ImportDeductions');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';

        // Display the file name
        document.getElementById('deductionImportFile').innerText = fileName;

        // Submit the form if a file is selected
        if (fileInput.files.length > 0) {
            $('#ImportDeductionForm').submit(); // Trigger the validation and AJAX submit
        }
        else
        {
            toastr.error('Please select a file before submitting.', "Error", {
                positionClass: 'toast-bottom-right'
            });
        }
    }
</script>
@endsection
