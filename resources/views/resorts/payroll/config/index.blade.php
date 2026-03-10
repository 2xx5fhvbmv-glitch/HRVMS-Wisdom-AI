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
                                    @for($i=1;$i<=31;$i++)
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
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-4 g-2 mb-3">
                                <div class="col-sm-4">
                                    <label for="maximum_limit_type" class="form-label">LIMIT TYPE</label>
                                    <select class="form-select" id="maximum_limit_type" name="maximum_limit_type">
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="col-sm-8">
                                    <label for="maximum" class="form-label">MAXIMUM DEDUCTION LIMIT</label>
                                    <input type="number" step="0.01" class="form-control maximum_limit" id="maximum"
                                        placeholder="Enter limit value" name="maximum_limit">
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>

                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <h3>Recent Deductions</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('deductions.index') }}" class="btn btn-themeSkyblue btn-sm">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="recentDeductionsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Currency</th>
                                        <th>Limit</th>
                                    </tr>
                                </thead>
                                <tbody id="recentDeductionsBody">
                                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
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
    var allowedFileExtensions = ['csv', 'xls', 'xlsx', 'ods', 'xlsb', 'xlt', 'xltx', 'xltm'];

    function isValidFileExtension(fileInput) {
        if (!fileInput.files || !fileInput.files[0]) return false;
        var ext = fileInput.files[0].name.split('.').pop().toLowerCase();
        return allowedFileExtensions.indexOf(ext) !== -1;
    }

    function showAjaxErrors(response) {
        if (response.responseJSON) {
            var errors = response.responseJSON;
            var errs = '';
            if (errors.errors) {
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
            } else if (errors.message) {
                errs = errors.message;
            }
            toastr.error(errs || 'Something went wrong.', "Error", { positionClass: 'toast-bottom-right' });
        } else {
            toastr.error('Something went wrong.', "Error", { positionClass: 'toast-bottom-right' });
        }
    }

    function submitFileForm(formId, fileInputId, url, $btn) {
        var fileInput = document.getElementById(fileInputId);
        if (!fileInput.files || !fileInput.files[0]) {
            toastr.error('Please select a file to upload.', "Error", { positionClass: 'toast-bottom-right' });
            return;
        }
        if (!isValidFileExtension(fileInput)) {
            toastr.error('Please upload a valid file (csv, xls, xlsx, ods, xlsb, xlt, xltx, xltm).', "Error", { positionClass: 'toast-bottom-right' });
            return;
        }
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Uploading...').prop('disabled', true);

        var formData = new FormData(document.getElementById(formId));
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    document.getElementById(formId).reset();
                    $('#' + formId).find('[id$="ImportFile"], #fileNameImportFile').text('');
                } else {
                    toastr.error(response.message || 'Upload failed.', "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(response) {
                $btn.html(originalText).prop('disabled', false);
                showAjaxErrors(response);
            }
        });
    }

    $(document).ready(function () {
        $('.select2t-none').select2();

        // Upload Previous Service Charge
        $('#ImportServiceChargeForm').on('submit', function(e) {
            e.preventDefault();
            submitFileForm('ImportServiceChargeForm', 'ImportServiceCharge', "{{ route('import.service-charges') }}", $(this).find('button[type=submit]'));
        });

        // Import Earnings
        $('#ImportEarningsForm').on('submit', function(e) {
            e.preventDefault();
            submitFileForm('ImportEarningsForm', 'ImportEarnings', "{{ route('import.earnings') }}", $(this).find('button[type=submit]'));
        });

        // Import Deductions
        $('#ImportDeductionForm').on('submit', function(e) {
            e.preventDefault();
            submitFileForm('ImportDeductionForm', 'ImportDeductions', "{{ route('import.deductions') }}", $(this).find('button[type=submit]'));
        });

        // Cutoff Day
        $('#cutOffDateForm').on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type=submit]');
            var cutoffDay = $('#cutoff_day').val();
            if (!cutoffDay) {
                toastr.error('Please select cutoff day.', "Error", { positionClass: 'toast-bottom-right' });
                return;
            }
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: "{{ route('save.cutoff.day') }}",
                type: "POST",
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.html(originalText).prop('disabled', false);
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(response.message || 'Failed to save.', "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function(response) {
                    $btn.html(originalText).prop('disabled', false);
                    showAjaxErrors(response);
                }
            });
        });
    });

    $(document).ready(function () {
        // Earnings - Add More Fields
        $(document).on('click', '.add-earnings', function () {
            var newRow = '<div class="earnings-block mb-md-4 mb-3"><div class="row align-items-end g-md-4 g-2 earnings-row">' +
                '<div class="col-sm-6"><label class="form-label">ALLOWANCE NAME</label>' +
                '<input type="text" class="form-control allow_name" placeholder="Allowance Name" name="allow_name[]"></div>' +
                '<div class="col-sm-6"><label class="form-label">ALLOWANCE TYPE</label>' +
                '<input type="text" class="form-control allow_type" placeholder="Allowance Type" name="allow_type[]"></div>' +
                '<div class="col"><div class="d-flex align-items-center">' +
                '<label class="form-label mb-0 me-md-4 me-3">CURRENCY:</label>' +
                '<label class="form-label mb-0 me-3">Rufiyaa</label>' +
                '<div class="form-check form-switch form-switchTheme">' +
                '<input class="form-check-input currency-switch" type="checkbox" name="currency[]">' +
                '<label class="form-check-label">USD</label></div></div></div>' +
                '<div class="col-auto"><button type="button" class="btn btn-danger remove-earnings">Remove</button></div>' +
                '</div></div>';
            $('.earnings-main').append(newRow);
        });

        // Earnings - Remove Fields
        $(document).on('click', '.remove-earnings', function () {
            $(this).closest('.earnings-row, .earnings-block').remove();
        });

        // Earnings - Submit Form
        $('#earningsForm').on('submit', function (e) {
            e.preventDefault();
            var earningsData = [];
            $('.earnings-row').each(function () {
                var allowName = $(this).find('.allow_name').val();
                var allowType = $(this).find('.allow_type').val();
                var currency = $(this).find('.currency-switch').is(':checked') ? 'USD' : 'Rufiyaa';
                if (allowName && allowType) {
                    earningsData.push({ allow_name: allowName, allow_type: allowType, currency: currency });
                }
            });
            if (earningsData.length === 0) {
                toastr.error("Please fill in at least one entry.", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }
            var $btn = $(this).find('button[type=submit]');
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: "{{ route('earnings.submit') }}",
                type: "POST",
                data: { earnings: earningsData, _token: "{{ csrf_token() }}" },
                success: function (response) {
                    $btn.html(originalText).prop('disabled', false);
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $('#earningsForm')[0].reset();
                    } else {
                        toastr.error("Failed to save earnings.", "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (response) {
                    $btn.html(originalText).prop('disabled', false);
                    showAjaxErrors(response);
                }
            });
        });

        // Deductions - Add More Fields
        $(document).on('click', '.add-deduction', function (e) {
            e.preventDefault();
            var newRow = '<div class="deduction-block mb-md-4 mb-3"><div class="row align-items-end g-md-4 g-2">' +
                '<div class="col-sm-6"><label class="form-label">DEDUCTION NAME</label>' +
                '<input type="text" class="form-control deduction_name" placeholder="Deduction Name" name="deduction_name[]"></div>' +
                '<div class="col-sm-6"><label class="form-label">DEDUCTION TYPE</label>' +
                '<input type="text" class="form-control deduction_type" placeholder="Deduction Type" name="deduction_type[]"></div>' +
                '<div class="col"><div class="d-flex align-items-center">' +
                '<label class="form-label mb-0 me-md-4 me-3">CURRENCY:</label>' +
                '<label class="form-label mb-0 me-3">Rufiyaa</label>' +
                '<div class="form-check form-switch form-switchTheme">' +
                '<input class="form-check-input currency-switch" type="checkbox" name="currency[]">' +
                '<label class="form-check-label">USD</label></div></div></div>' +
                '<div class="col-auto"><button type="button" class="btn btn-danger remove-deduction">Remove</button></div>' +
                '</div></div>';
            $('.deduction-main').append(newRow);
        });

        // Deductions - Remove Fields
        $(document).on('click', '.remove-deduction', function (e) {
            e.preventDefault();
            $(this).closest('.deduction-block').remove();
        });

        // Update placeholder based on limit type
        $('#maximum_limit_type').on('change', function() {
            var type = $(this).val();
            if (type === 'percentage') {
                $('#maximum').attr('placeholder', 'Enter percentage (e.g. 50)');
            } else {
                $('#maximum').attr('placeholder', 'Enter fixed amount');
            }
        });

        // Deductions - Submit Form
        $('#deductionForm').on('submit', function (e) {
            e.preventDefault();
            var deductionsData = [];
            $('.deduction-block').each(function () {
                var deductionName = $(this).find('.deduction_name').val();
                var deductionType = $(this).find('.deduction_type').val();
                var currency = $(this).find('.currency-switch').is(':checked') ? 'USD' : 'Rufiyaa';
                if (deductionName && deductionType) {
                    deductionsData.push({ deduction_name: deductionName, deduction_type: deductionType, currency: currency });
                }
            });
            var maximumLimit = $('#maximum').val();
            var maximumLimitType = $('#maximum_limit_type').val();
            if (deductionsData.length === 0 || !maximumLimit) {
                toastr.error("Please fill in all required fields.", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }
            var $btn = $(this).find('button[type=submit]');
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: "{{ route('deductions.submit') }}",
                type: "POST",
                data: { deductions: deductionsData, maximum_limit: maximumLimit, maximum_limit_type: maximumLimitType, _token: "{{ csrf_token() }}" },
                success: function (response) {
                    $btn.html(originalText).prop('disabled', false);
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $('#deductionForm')[0].reset();
                        loadRecentDeductions();
                    } else {
                        toastr.error("Failed to save deductions.", "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (response) {
                    $btn.html(originalText).prop('disabled', false);
                    showAjaxErrors(response);
                }
            });
        });

        // Load recent deductions on page load
        loadRecentDeductions();
    });

    function loadRecentDeductions() {
        $.ajax({
            url: "{{ route('deductions.list') }}",
            type: "GET",
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '';
                    var items = response.data.slice(0, 4);
                    items.forEach(function(d) {
                        var limitDisplay = d.maximum_limit;
                        if (d.maximum_limit_type === 'percentage') {
                            limitDisplay += '%';
                        } else {
                            limitDisplay = (d.currency === 'USD' ? '$' : '') + limitDisplay;
                        }
                        html += '<tr>' +
                            '<td>' + d.deduction_name + '</td>' +
                            '<td>' + d.deduction_type + '</td>' +
                            '<td>' + d.currency + '</td>' +
                            '<td>' + limitDisplay + '</td>' +
                            '</tr>';
                    });
                    $('#recentDeductionsBody').html(html);
                } else {
                    $('#recentDeductionsBody').html('<tr><td colspan="4" class="text-center">No deductions found.</td></tr>');
                }
            },
            error: function() {
                $('#recentDeductionsBody').html('<tr><td colspan="4" class="text-center text-danger">Failed to load.</td></tr>');
            }
        });
    }

    function displayImportFileName() {
        var fileInput = document.getElementById('ImportServiceCharge');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
        document.getElementById('fileNameImportFile').innerText = fileName;
    }

    function displayEarningImportFileName(){
        var fileInput = document.getElementById('ImportEarnings');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
        document.getElementById('earningImportFile').innerText = fileName;
    }

    function displayDeductonImportFileName(){
        var fileInput = document.getElementById('ImportDeductions');
        var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
        document.getElementById('deductionImportFile').innerText = fileName;
    }
</script>
@endsection
