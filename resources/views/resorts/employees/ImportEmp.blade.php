@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #import-emp-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #import-emp-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="import-emp-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Employee</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.budget.config') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Configuration
                    </a>
                </div>
            </div>
        </div>
        <div class="ie-scope">
            <div class="ie-card">
                <form id="BudgetConfigFiles">
                    @csrf
                    <div class="ie-steps">
                        <div class="ie-step">
                            <div class="ie-badge">1</div>
                            <div class="ie-body">
                                <div class="ie-t">Export Department &amp; Position File</div>
                                <div class="ie-d">Download the template, then fill in your employees against each department and position.</div>
                                <div class="ie-controls">
                                    <a href="{{ route('resort.export.Employee')}}" class="ie-btn ie-ghost">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                        Download Template
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="ie-step">
                            <div class="ie-badge">2</div>
                            <div class="ie-body">
                                <div class="ie-t">Upload Completed File</div>
                                <div class="ie-d">Upload the completed Excel file exported from the template.</div>
                                <div class="ie-controls ie-filepick uploadFile-block">
                                    <label class="ie-choose">
                                        <input type="file" name="Employeefile" id="Employeefile" hidden accept=".xls,.xlsx">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
                                        <span id="chooseTxt">Choose File</span>
                                    </label>
                                    <span class="ie-fname" id="file-name-display">No file selected</span>
                                </div>
                                <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="ie-foot">
                        <button type="submit" class="ie-btn ie-primary">Submit</button>
                    </div>
                </form>

                {{-- Error Table (shown after failed import) --}}
                <div id="import-error-section" class="mt-4" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-danger mb-0">Import Errors</h6>
                        <span id="import-error-count" class="badge bg-danger"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="import-error-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Row</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
{{-- _wfp_buttons_v2_styles is still needed here for the "Back to
     Configuration" button in the page header above (wfp-btn-neutral) —
     dropping it makes that button lose its background and disappear
     against the teal header band. --}}
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@include('resorts.employees._import_employee_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {

    // The upload now returns instantly with an import_history id; the actual
    // row-by-row import runs in the background via a queued job. Poll the
    // status endpoint until it's done/failed instead of waiting on the
    // upload request itself. Pattern mirrors resorts.Visa.XpactSync's
    // pollXpatSync().
    function pollImportStatus(statusUrl, $btn) {
        var tries = 0, maxTries = 100; // ~5 min ceiling at 3s
        var iv = setInterval(function () {
            tries++;
            $.ajax({ url: statusUrl, type: 'GET' })
                .done(function (res) {
                    if (res && (res.status === 'queued' || res.status === 'processing')) {
                        if (tries >= maxTries) {
                            clearInterval(iv);
                            $btn.prop('disabled', false).text('Submit');
                            toastr.error('Still processing — please check back shortly.', 'Timeout', { positionClass: 'toast-bottom-right' });
                        }
                        return; // keep waiting
                    }

                    clearInterval(iv);
                    $btn.prop('disabled', false).text('Submit');

                    if (res && res.status === 'completed' && (!res.error_report || !res.error_report.length)) {
                        toastr.success(res.created_count + ' employee(s) created, ' + res.updated_count + ' updated.', 'Success', { positionClass: 'toast-bottom-right' });
                        $('#BudgetConfigFiles')[0].reset();
                        $('#file-name-display').text('No file selected').removeClass('ie-set');
                        $('#chooseTxt').text('Choose File');
                        $('#import-error-section').hide();
                        $('#import-error-table tbody').empty();
                        return;
                    }

                    if (res && res.status === 'failed' && (!res.error_report || !res.error_report.length)) {
                        toastr.error(res.failure_message || 'Import failed.', 'Error', { positionClass: 'toast-bottom-right' });
                        return;
                    }

                    var errors = (res && res.error_report) ? res.error_report : [];
                    if (errors.length) {
                        var tbody = $('#import-error-table tbody').empty();
                        errors.forEach(function (err) {
                            tbody.append(
                                '<tr>' +
                                '<td>' + err.row + '</td>' +
                                '<td>' + (err.name || 'N/A') + '</td>' +
                                '<td>' + (err.email || 'N/A') + '</td>' +
                                '<td>' + (err.department || 'N/A') + '</td>' +
                                '<td>' + (err.position || 'N/A') + '</td>' +
                                '<td class="text-danger">' + err.error + '</td>' +
                                '</tr>'
                            );
                        });
                        $('#import-error-count').text(errors.length + ' error(s)');
                        $('#import-error-section').show();
                        toastr.error(errors.length + ' row(s) could not be imported. See details below.', 'Import Errors', { positionClass: 'toast-bottom-right' });
                    }
                })
                .fail(function () {
                    // transient network blip — keep polling until the ceiling
                    if (tries >= maxTries) {
                        clearInterval(iv);
                        $btn.prop('disabled', false).text('Submit');
                    }
                });
        }, 3000);
    }

    // Show selected file name + size, and switch the picker label to
    // "Replace File" — the hidden input is wrapped in a real <label> now,
    // so no click-forwarding JS is needed to open the native file picker.
    $('#Employeefile').on('change', function () {
        const file = this.files[0];
        if (file) {
            $('#file-name-display').text(file.name + ' · ' + (file.size / 1024).toFixed(0) + ' KB').addClass('ie-set');
            $('#chooseTxt').text('Replace File');
        } else {
            $('#file-name-display').text('No file selected').removeClass('ie-set');
            $('#chooseTxt').text('Choose File');
        }
        $('#file-extension-error').hide();
    });

    $('#BudgetConfigFiles').validate({
        rules: {
            Employeefile: {
                required: true,
            }
        },
        messages: {
            Employeefile: {
                required: "Please select an Employee Excel file.",
            }
        },
        submitHandler: function (form) {
            const fileInput = document.getElementById('Employeefile');
            const fileName  = fileInput.value;
            const allowed   = /(\.xls|\.xlsx)$/i;

            if (!allowed.exec(fileName)) {
                $('#file-extension-error').text('Only .xls or .xlsx files are allowed.').show();
                return false;
            }
            $('#file-extension-error').hide();

            const $btn = $(form).find('[type="submit"]');
            $btn.prop('disabled', true).text('Processing...');
            $('#import-error-section').hide();
            $('#import-error-table tbody').empty();

            const formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.Depat_Position_Emp_Import') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    // Upload accepted — the import runs in the background via
                    // a queued job. Poll status_url instead of waiting here.
                    if (response && response.processing && response.status_url) {
                        pollImportStatus(response.status_url, $btn);
                        return;
                    }
                    $btn.prop('disabled', false).text('Submit');
                    toastr.error('Unexpected response from server.', 'Error', { positionClass: 'toast-bottom-right' });
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Submit');
                    const response = xhr.responseJSON || {};

                    if (response.errors && Array.isArray(response.errors)) {
                        const tbody = $('#import-error-table tbody').empty();
                        response.errors.forEach(function (err) {
                            tbody.append(
                                '<tr>' +
                                '<td>' + err.row + '</td>' +
                                '<td>' + (err.name || 'N/A') + '</td>' +
                                '<td>' + (err.email || 'N/A') + '</td>' +
                                '<td>' + (err.department || 'N/A') + '</td>' +
                                '<td>' + (err.position || 'N/A') + '</td>' +
                                '<td class="text-danger">' + err.error + '</td>' +
                                '</tr>'
                            );
                        });
                        $('#import-error-count').text(response.errors.length + ' error(s)');
                        $('#import-error-section').show();
                        toastr.error(response.errors.length + ' row(s) could not be imported. See details below.', 'Import Errors', { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(response.msg || 'Something went wrong. Please try again.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        },
        errorPlacement: function (error, element) {
            error.insertAfter(element.closest('.uploadFile-block').length
                ? element.closest('.uploadFile-block')
                : element);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        }
    });

});
</script>
@endsection
