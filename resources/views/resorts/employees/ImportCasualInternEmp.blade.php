@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<style>
    #import-ci-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) { #import-ci-hero { padding-bottom: 0; } }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="import-ci-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Employee</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.Add.Employee') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Import Employee
                    </a>
                </div>
            </div>
        </div>
        <div class="ie-scope">
            <div class="ie-card">
                <form id="CasualInternImportForm">
                    @csrf
                    <div class="ie-steps">
                        <div class="ie-step">
                            <div class="ie-badge">1</div>
                            <div class="ie-body">
                                <div class="ie-t">Download Template</div>
                                <div class="ie-d">For Casual/Agency and Intern/Trainee staff already on-site — a lighter template than the Master Employee Import (name, ID, nationality, mobile, department, position, reporting manager, employment type).</div>
                                <div class="ie-controls">
                                    <a href="{{ route('resort.employee.downloadCasualInternTemplate') }}" class="ie-btn ie-ghost">
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
                                <div class="ie-d">Reporting Manager is required — that person marks this employee's attendance/leave going forward, since Casual/Intern staff have no mobile app of their own.</div>
                                <div class="ie-controls ie-filepick uploadFile-block">
                                    <label class="ie-choose">
                                        <input type="file" name="Employeefile" id="CIEmployeefile" hidden accept=".xls,.xlsx">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
                                        <span id="ciChooseTxt">Choose File</span>
                                    </label>
                                    <span class="ie-fname" id="ci-file-name-display">No file selected</span>
                                </div>
                                <span id="ci-file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="ie-foot">
                        <button type="submit" class="ie-btn ie-primary">Submit</button>
                    </div>
                </form>

                <div id="ci-import-error-section" class="mt-4" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-danger mb-0">Import Errors</h6>
                        <span id="ci-import-error-count" class="badge bg-danger"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr><th>Row</th><th>Name</th><th>Department</th><th>Position</th><th>Error</th></tr>
                            </thead>
                            <tbody id="ci-import-error-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@include('resorts.employees._import_employee_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    function ciPollStatus(statusUrl, $btn) {
        var tries = 0, maxTries = 100;
        var iv = setInterval(function () {
            tries++;
            $.ajax({ url: statusUrl, type: 'GET' }).done(function (res) {
                if (res && (res.status === 'queued' || res.status === 'processing')) {
                    if (tries >= maxTries) {
                        clearInterval(iv);
                        $btn.prop('disabled', false).text('Submit');
                        toastr.error('Still processing — please check back shortly.', 'Timeout', { positionClass: 'toast-bottom-right' });
                    }
                    return;
                }
                clearInterval(iv);
                $btn.prop('disabled', false).text('Submit');

                if (res && res.status === 'completed' && (!res.error_report || !res.error_report.length)) {
                    toastr.success(res.created_count + ' employee(s) created, ' + res.updated_count + ' updated.', 'Success', { positionClass: 'toast-bottom-right' });
                    $('#CasualInternImportForm')[0].reset();
                    $('#ci-file-name-display').text('No file selected').removeClass('ie-set');
                    $('#ciChooseTxt').text('Choose File');
                    $('#ci-import-error-section').hide();
                    return;
                }
                if (res && res.status === 'failed' && (!res.error_report || !res.error_report.length)) {
                    toastr.error(res.failure_message || 'Import failed.', 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
                var errors = (res && res.error_report) ? res.error_report : [];
                if (errors.length) {
                    var tbody = $('#ci-import-error-tbody').empty();
                    errors.forEach(function (err) {
                        tbody.append('<tr><td>' + err.row + '</td><td>' + (err.name || 'N/A') + '</td><td>' + (err.department || 'N/A') + '</td><td>' + (err.position || 'N/A') + '</td><td class="text-danger">' + err.error + '</td></tr>');
                    });
                    $('#ci-import-error-count').text(errors.length + ' error(s)');
                    $('#ci-import-error-section').show();
                    toastr.error(errors.length + ' row(s) could not be imported. See details below.', 'Import Errors', { positionClass: 'toast-bottom-right' });
                }
            }).fail(function () {
                if (tries >= maxTries) { clearInterval(iv); $btn.prop('disabled', false).text('Submit'); }
            });
        }, 3000);
    }

    $('#CIEmployeefile').on('change', function () {
        const file = this.files[0];
        if (file) {
            $('#ci-file-name-display').text(file.name + ' · ' + (file.size / 1024).toFixed(0) + ' KB').addClass('ie-set');
            $('#ciChooseTxt').text('Replace File');
        } else {
            $('#ci-file-name-display').text('No file selected').removeClass('ie-set');
            $('#ciChooseTxt').text('Choose File');
        }
        $('#ci-file-extension-error').hide();
    });

    $('#CasualInternImportForm').on('submit', function (e) {
        e.preventDefault();
        const fileInput = document.getElementById('CIEmployeefile');
        if (!fileInput.value || !/(\.xls|\.xlsx)$/i.exec(fileInput.value)) {
            $('#ci-file-extension-error').text('Please select a valid .xls or .xlsx file.').show();
            return;
        }
        $('#ci-file-extension-error').hide();

        const $btn = $(this).find('[type="submit"]');
        $btn.prop('disabled', true).text('Processing...');
        $('#ci-import-error-section').hide();

        const formData = new FormData(this);
        $.ajax({
            url: "{{ route('resort.employee.uploadCasualIntern') }}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response && response.processing && response.status_url) {
                    ciPollStatus(response.status_url, $btn);
                    return;
                }
                $btn.prop('disabled', false).text('Submit');
                toastr.error('Unexpected response from server.', 'Error', { positionClass: 'toast-bottom-right' });
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Submit');
                toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || 'Something went wrong.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });
});
</script>
@endsection
