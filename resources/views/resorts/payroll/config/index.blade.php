@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #payroll-config-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #payroll-config-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="payroll-config-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="pc-wrap">

            {{-- LEFT COLUMN --}}
            <div class="pc-col">

                {{-- Cutoff day --}}
                <div class="pc-card">
                    <div class="pc-ct"><h2>Cutoff day</h2></div>
                    <form id="cutOffDateForm">
                        @csrf
                        <div class="pc-f">
                            <label>Cutoff day <span class="pc-req">*</span></label>
                            <select name="cutoff_day" id="cutoff_day" class="form-select dd-native-select">
                                @for($i=1;$i<=31;$i++)
                                    <option value="{{$i}}" @if(isset($payroll_config) && $payroll_config->cutoff_day == $i) selected @endif>{{$i}}</option>
                                @endfor
                            </select>
                            @php $selectedCutoff = (isset($payroll_config) && $payroll_config->cutoff_day) ? $payroll_config->cutoff_day : 1; @endphp
                            <div class="dd" data-target="#cutoff_day">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">{{ $selectedCutoff }}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Cutoff day">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a day…"></div>
                                    <div class="dd-scroll">
                                        @for($i=1;$i<=31;$i++)
                                        <div class="dd-item{{ $i == $selectedCutoff ? ' active' : '' }}" role="option" data-value="{{ $i }}"><span class="dd-nm">{{ $i }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pc-cfoot"><button type="submit" class="pc-submit">Submit</button></div>
                    </form>
                </div>

                {{-- Upload previous service charge --}}
                <div class="pc-card">
                    <div class="pc-ct">
                        <h2>Upload previous service charge</h2><span class="pc-spacer"></span>
                        <a href="{{ route('export.service-charges') }}" class="pc-ghostbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>Download template</a>
                    </div>
                    <form id="ImportServiceChargeForm">
                        @csrf
                        <div class="pc-drop" id="scDrop" onclick="document.getElementById('ImportServiceCharge').click();">
                            <div class="pc-dropic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg></div>
                            <div class="pc-t">Click to upload file</div>
                            <div class="pc-s" id="fileNameImportFile" data-default="Accepts .xlsx or .csv — use the template above">Accepts .xlsx or .csv — use the template above</div>
                        </div>
                        <input type="file" id="ImportServiceCharge" name="ImportServiceCharge"
                            accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;" onchange="displayImportFileName()">
                        <div class="pc-cfoot"><button type="submit" class="pc-submit">Submit</button></div>
                    </form>
                </div>

            </div>

            {{-- RIGHT COLUMN --}}
            <div class="pc-col">

                {{-- Deductions --}}
                <div class="pc-card">
                    <div class="pc-ct">
                        <h2>Deductions</h2><span class="pc-spacer"></span>
                        <a href="{{route('deductions.download-template')}}" class="pc-ghostbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>Download template</a>
                        <form id="ImportDeductionForm" style="display:contents">
                            @csrf
                            <button type="button" class="pc-tealbtn" id="importDeductionBtn" onclick="document.getElementById('ImportDeductions').click();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg>Upload file</button>
                            <input type="file" id="ImportDeductions" name="ImportDeductions"
                                accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;">
                        </form>
                    </div>

                    <form id="deductionForm">
                        <div class="pc-grid pc-g2">
                            <div class="pc-f pc-fspan"><label>Deduction name <span class="pc-req">*</span></label><input type="text" class="pc-inp deduction_name" id="deduction_name" placeholder="e.g. Uniform Damage" name="deduction_name[]"></div>
                            <div class="pc-f"><label>Deduction type <span class="pc-req">*</span></label><input type="text" class="pc-inp deduction_type" id="deduction_type" placeholder="e.g. Fixed" name="deduction_type[]"></div>
                            <div class="pc-f">
                                <label>Currency <span class="pc-req">*</span></label>
                                <div class="pc-seg" id="curSeg"><button type="button" data-v="Rufiyaa">Rufiyaa</button><button type="button" class="on" data-v="USD">USD</button></div>
                            </div>
                            <div class="pc-f">
                                <label>Limit type <span class="pc-req">*</span></label>
                                <select class="form-select dd-native-select" id="maximum_limit_type" name="maximum_limit_type">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed amount</option>
                                </select>
                                <div class="dd" data-target="#maximum_limit_type">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Percentage (%)</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Limit type">
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value="percentage"><span class="dd-nm">Percentage (%)</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="fixed"><span class="dd-nm">Fixed amount</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pc-f"><label>Maximum deduction limit <span class="pc-req">*</span></label><input type="number" step="0.01" class="pc-inp maximum_limit" id="maximum" placeholder="Enter limit value" name="maximum_limit"></div>
                        </div>
                        <div class="pc-cfoot"><button type="submit" class="pc-submit">Submit</button></div>
                    </form>
                </div>

                {{-- Recent deductions --}}
                <div class="pc-card">
                    <div class="pc-ct">
                        <h2>Recent deductions</h2><span class="pc-spacer"></span>
                        <a href="{{ route('deductions.index') }}" class="pc-ghostbtn">View all <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    </div>
                    <table class="pc-tbl" id="recentDeductionsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Currency</th>
                                <th class="pc-num">Limit</th>
                            </tr>
                        </thead>
                        <tbody id="recentDeductionsBody">
                            <tr><td colspan="4" class="pc-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.payroll.config._payroll_config_styles')
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
                    var sub = document.getElementById('fileNameImportFile');
                    if (sub) sub.textContent = sub.getAttribute('data-default');
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
        // Upload Previous Service Charge
        $('#ImportServiceChargeForm').on('submit', function(e) {
            e.preventDefault();
            submitFileForm('ImportServiceChargeForm', 'ImportServiceCharge', "{{ route('import.service-charges') }}", $(this).find('button[type=submit]'));
        });

        // Deductions bulk import — the "Upload file" button opens the picker;
        // selecting a file submits immediately (no separate Submit step —
        // matches the finalized design, which shows only one action here).
        $('#ImportDeductions').on('change', function() {
            submitFileForm('ImportDeductionForm', 'ImportDeductions', "{{ route('import.deductions') }}", $('#importDeductionBtn'));
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
        // Currency segmented toggle
        $(document).on('click', '#curSeg button', function() {
            $('#curSeg button').removeClass('on');
            $(this).addClass('on');
        });

        // Update placeholder based on limit type (fires on the real
        // <select>'s change event, which the .dd component dispatches
        // whenever an item is picked — see resorts._dropdown_script).
        $('#maximum_limit_type').on('change', function() {
            var type = $(this).val();
            if (type === 'percentage') {
                $('#maximum').attr('placeholder', 'Enter percentage (e.g. 50)');
            } else {
                $('#maximum').attr('placeholder', 'Enter fixed amount');
            }
        });

        // Deductions - Submit Form (manual add — a single entry)
        $('#deductionForm').on('submit', function (e) {
            e.preventDefault();
            var deductionName = $('#deduction_name').val();
            var deductionType = $('#deduction_type').val();
            var currency = $('#curSeg button.on').data('v');
            var maximumLimit = $('#maximum').val();
            var maximumLimitType = $('#maximum_limit_type').val();
            if (!deductionName || !deductionType || !maximumLimit) {
                toastr.error("Please fill in all required fields.", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }
            var deductionsData = [{ deduction_name: deductionName, deduction_type: deductionType, currency: currency }];
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
                        $('#curSeg button').removeClass('on');
                        $('#curSeg button[data-v="USD"]').addClass('on');
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

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

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
                            limitDisplay = (d.currency === 'USD' ? '$ ' : 'MVR ') + limitDisplay;
                        }
                        html += '<tr>' +
                            '<td class="pc-name">' + escapeHtml(d.deduction_name) + '</td>' +
                            '<td><span class="pc-chip">' + escapeHtml(d.deduction_type) + '</span></td>' +
                            '<td class="pc-cur">' + escapeHtml(d.currency) + '</td>' +
                            '<td class="pc-num">' + escapeHtml(limitDisplay) + '</td>' +
                            '</tr>';
                    });
                    $('#recentDeductionsBody').html(html);
                } else {
                    $('#recentDeductionsBody').html('<tr><td colspan="4" class="pc-empty">No deductions found.</td></tr>');
                }
            },
            error: function() {
                $('#recentDeductionsBody').html('<tr><td colspan="4" class="pc-empty">Failed to load.</td></tr>');
            }
        });
    }

    function displayImportFileName() {
        var fileInput = document.getElementById('ImportServiceCharge');
        var sub = document.getElementById('fileNameImportFile');
        if (!sub) return;
        sub.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : sub.getAttribute('data-default');
    }
</script>
@include('resorts._dropdown_script')
@endsection
