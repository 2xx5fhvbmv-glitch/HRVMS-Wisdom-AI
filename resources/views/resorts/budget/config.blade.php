@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #budget-config-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #budget-config-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="budget-config-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="cfg-scope">
            @php
                $BudgetConfig = Common::GetBudgetConfigLinks(Auth::guard('resort-admin')->user()->resort_id);
                $canUpload = App\Helpers\Common::checkRouteWisePermission('resort.budget.config', config('settings.resort_permissions.create'));
            @endphp
            <form id="BudgetConfigFiles">
                @csrf
                <div class="cfg-grid">
                    <!-- LEFT 60%: the two config sections -->
                    <div class="cfg-left">

                        <!-- section 1: ratio -->
                        <div class="cfg-card">
                            <div class="cfg-shead"><span class="cfg-slbl">Xpat : Local Ratio</span></div>
                            <div class="cfg-ratio">
                                <div class="cfg-field">
                                    <span class="cfg-k">Xpat<span class="cfg-req">*</span></span>
                                    <div class="cfg-suffix"><input class="cfg-inp cfg-num" id="xpat" name="xpat" type="number" min="0" max="100" inputmode="numeric" value="{{ old('xpat', $BudgetConfig['xpat'] ?? '') }}"><span class="cfg-pc">%</span></div>
                                </div>
                                <span class="cfg-colon">:</span>
                                <div class="cfg-field">
                                    <span class="cfg-k">Local<span class="cfg-req">*</span></span>
                                    <div class="cfg-suffix"><input class="cfg-inp cfg-num" id="local" name="local" type="number" min="0" max="100" inputmode="numeric" value="{{ old('local', $BudgetConfig['local'] ?? '') }}"><span class="cfg-pc">%</span></div>
                                </div>
                            </div>
                            <div class="cfg-bar">
                                <div class="cfg-track"><div class="cfg-seg cfg-x" id="cfgSegX"></div><div class="cfg-seg cfg-l" id="cfgSegL"></div></div>
                                <div class="cfg-keys">
                                    <span><span class="cfg-dot cfg-x"></span>Xpat <b id="cfgKx">0%</b></span>
                                    <span>Local <b id="cfgKl">0%</b><span class="cfg-dot cfg-l" style="margin:0 0 0 6px"></span></span>
                                </div>
                                <div class="cfg-note" id="cfgNote">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                    <span id="cfgNoteText">Xpat and Local must add up to 100%.</span>
                                </div>
                            </div>
                        </div>

                        <!-- section 2: upload past year's budget -->
                        <div class="cfg-card">
                            <div class="cfg-shead">
                                <span class="cfg-slbl">Upload Past Year's Consolidated Budget</span>
                                <a href="{{ route('resort.budget.GetConsolidateFile') }}" class="cfg-gbtn consolidatedBudget">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                    Download Template
                                </a>
                            </div>

                            <div class="cfg-flabel">Pick the year, then upload the completed Excel file exported from the template.</div>
                            <div class="cfg-uploadrow">
                                <select class="form-select dd-native-select" name="consolidatdebudget_Year" id="year">
                                    <option value="">Select Year</option>
                                </select>
                                <div class="dd" id="yr" data-target="#year">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl cfg-ph" id="yrv">Select Year</span>
                                        <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Year">
                                        <div class="dd-scroll"></div>
                                    </div>
                                </div>
                                @if($canUpload)
                                    <label class="cfg-choose">
                                        <input type="file" name="consolidatedbudget" id="consolidatedbudget" accept=".xls,.xlsx" hidden>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
                                        <span id="chooseTxt">Choose File</span>
                                    </label>
                                    <span class="cfg-fname" id="fname">No file selected</span>
                                    <button type="submit" form="BudgetConfigFiles" class="cfg-btn cfg-primary ConsolidateBudget" id="uploadBtn" disabled>Upload File</button>
                                @endif
                            </div>

                            @if($canUpload)
                                <div class="cfg-cardfoot">
                                    <button type="submit" form="BudgetConfigFiles" class="cfg-btn cfg-primary ConsolidateBudget">Submit</button>
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- RIGHT 40%: configuration areas (navigation, not a checklist) -->
                    <div class="cfg-card cfg-list">
                        <div class="cfg-clbl-h">Configurations</div>
                        <div class="cfg-sub">Set up each area — open to view or edit.</div>
                        <ul>
                            @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning', config('settings.resort_permissions.view')))
                                <li><a href="{{ route('resort.manning.index') }}"><span class="cfg-txt"><b>Resort Configuration</b><small>Property, departments & structure</small></span><svg class="cfg-go" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a></li>
                            @endif
                            @if(App\Helpers\Common::checkRouteWisePermission('resort.benifitgrid.index', config('settings.resort_permissions.view')))
                                <li><a href="{{ route('resort.benifitgrid.index') }}"><span class="cfg-txt"><b>Benefit Grid List</b><small>Grids applied across ranks</small></span><svg class="cfg-go" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a></li>
                            @endif
                            @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.index', config('settings.resort_permissions.view')))
                                <li><a href="{{ route('resort.budget.index') }}"><span class="cfg-txt"><b>Cost Configuration</b><small>Salary bands & cost inputs</small></span><svg class="cfg-go" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a></li>
                                <li><a href="{{ route('resort.budget.nonpermanent.index') }}"><span class="cfg-txt"><b>Cost Configuration for Casuals & Interns</b><small>Separate cost items for non-permanent staff</small></span><svg class="cfg-go" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a></li>
                            @endif
                            <li><a href="{{ route('resort.Add.Employee') }}"><span class="cfg-txt"><b>Add Employee</b><small>Onboard staff records</small></span><svg class="cfg-go" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a></li>
                            @if(App\Helpers\Common::checkRouteWisePermission('resort.benifitgrid.index', config('settings.resort_permissions.create')))
                                <li><a href="{{ route('resort.benifitgrid.create') }}"><span class="cfg-txt"><b>Add Benefit Grids</b><small>Create a new benefit grid</small></span><svg class="cfg-go" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.budget._config_page_styles')
@endsection

@section('import-scripts')
@include('resorts._dropdown_script')
<script>
    $(document).ready(function () {
        // Initialize form validation
        $('#BudgetConfigFiles').validate({
            rules: {
                xpat: {
                    required: true
                },
                local: {
                    required: true
                },
                consolidatdebudget_Year: {
                    required: function() {
                        // Make year required only if a file is selected
                        return $('#consolidatedbudget').get(0)?.files?.length > 0;
                    }
                },
            },
            messages: {
                xpat: {
                    required: "Please enter xpat."
                },
                local: {
                    required: "Please enter local."
                },
                consolidatdebudget_Year: {
                    required: "Please select Year when uploading a file."
                }
            },
            submitHandler: function (form) {
                // Create a new FormData object for file uploads
                var formData = new FormData(form);
                $(".ConsolidateBudget").prop('disabled', true);
                $.ajax({
                    url: "{{ route('resort.budget.UploadconfigFiles') }}", // Ensure correct route is specified
                    type: "POST",
                    data: formData,
                    contentType: false,  // Important for file uploads
                    processData: false,  // Important for file uploads
                    success: function (response) {
                        $(".ConsolidateBudget").prop('disabled', false);
                        if (response.success) {
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            // Clear file input and filename text after successful upload
                            $('#consolidatedbudget').val('');
                            $('#fname').text('No file selected').removeClass('cfg-set');
                            $('#chooseTxt').text('Choose File');
                            refreshUpload();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function (response) {
                        $(".ConsolidateBudget").prop('disabled', false);
                        var msg = (response.responseJSON && response.responseJSON.msg)
                            ? response.responseJSON.msg
                            : 'Something went wrong.';
                        toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
                    }
                });
            },
            errorPlacement: function (error, element) {
                if (element.hasClass("select2-hidden-accessible")) {
                    error.insertAfter(element.next('.select2')); // For Select2, place error after its container
                } else {
                    error.insertAfter(element); // Default error placement
                }
            },
            highlight: function (element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                } else {
                    $(element).addClass('is-invalid');
                }
            },
            unhighlight: function (element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                } else {
                    $(element).removeClass('is-invalid');
                }
            }
        });

        // ---- file picker: chosen filename + "Choose File" -> "Replace File" ----
        function refreshUpload() {
            var $upload = $('#uploadBtn');
            if (!$upload.length) return; // not rendered when the user lacks upload permission
            $upload.prop('disabled', !($('#consolidatedbudget').get(0).files.length && $('#year').val()));
        }
        window.refreshUpload = refreshUpload; // reused by the submit success handler above
        $('#consolidatedbudget').on('change', function () {
            var f = this.files[0];
            if (f) {
                $('#fname').text(f.name + ' · ' + (f.size / 1024).toFixed(0) + ' KB').addClass('cfg-set');
                $('#chooseTxt').text('Replace File');
            } else {
                $('#fname').text('No file selected').removeClass('cfg-set');
                $('#chooseTxt').text('Choose File');
            }
            refreshUpload();
            $('#BudgetConfigFiles').validate().element('[name="consolidatdebudget_Year"]');
        });

        // ---- year dropdown: placeholder colour off once a year is picked, and
        //      the Upload File button reacts to the real <select>'s value —
        //      the shared .dd component dispatches a real `change` on it. ----
        $('#year').on('change', function () {
            $('#yrv').toggleClass('cfg-ph', !this.value);
            refreshUpload();
        });

        // ---- Xpat:Local live split bar + 100% note (bound to the real,
        //      already-validated fields — purely an additional visual aid,
        //      the actual required/save validation above is unchanged) ----
        var $xpat = $('#xpat'), $local = $('#local');
        function syncRatio() {
            var x = parseInt($xpat.val(), 10); if (isNaN(x)) x = 0;
            var l = parseInt($local.val(), 10); if (isNaN(l)) l = 0;
            var sum = x + l, wx = sum ? Math.round(x / sum * 100) : 50, wl = 100 - wx;
            $('#cfgSegX').css('flex', '0 0 ' + wx + '%');
            $('#cfgSegL').css('flex', '0 0 ' + wl + '%');
            $('#cfgKx').text(x + '%'); $('#cfgKl').text(l + '%');
            if (sum !== 100) {
                $('#cfgNoteText').text('Xpat and Local add up to ' + sum + '% — they must total 100%.');
                $('#cfgNote').addClass('show');
            } else {
                $('#cfgNote').removeClass('show');
            }
        }
        $xpat.on('input', syncRatio); $local.on('input', syncRatio); syncRatio();
    });

    function populateYears() {
        const yearSelect = document.getElementById('year');
        const currentYear = new Date().getFullYear() - 1;
        const startYear = currentYear - 10; // Last 10 years + current year

        // Clear existing options except the first one
        while (yearSelect.options.length > 1) {
            yearSelect.remove(1);
        }

        // Add years to select dropdown
        for (let year = currentYear; year >= startYear; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }

        // Keep the visual dropdown (.dd) in sync with the real <select> —
        // documented cascading-select pattern from resorts._dropdown_script.
        if (window.wisdomDD) window.wisdomDD.rebuild('#year');
    }

    // Call the function when document is loaded
    $(document).ready(populateYears);
</script>
@endsection