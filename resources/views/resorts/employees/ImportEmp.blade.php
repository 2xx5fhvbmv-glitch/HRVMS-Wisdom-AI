@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                        <span>Employee</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="row g-4">
                <div class="col-xxl-7 col-xl-8 col-lg-9">
                    <div class="card">
                        {{-- <div class="row">
                            <div class="col-lg-6"> --}}
                                @php
                                    $BudgetConfig= Common::GetBudgetConfigLinks(Auth::guard('resort-admin')->user()->resort_id);
                                @endphp
                                <form id="BudgetConfigFiles">
                                    @csrf
                                    <div class="row g-md-4 g-3 mb-md-5 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label d-block" for="position">Export Department and Position File</label>
                                            <a href="{{ route('resort.export.Employee')}}"  class="btn btn-theme btn-small Employeefile mt-2">Download Template</a>
                                            <!-- <a href=""  class="btn btn-theme btn-small Employeefile mt-2">Download</a> -->
                                        </div>
                                        <!-- <div class="col-md-6">
                                            <label class="form-label d-block" for="position">How To Import Employees Using Excel File</label>
                                            <a href="{{ URL::asset('resorts_assets/demofiles/Employee.xlsx') }}" target="_blank" class="btn btn-theme btn-small Employeefile mt-2">Download </a>
                                        </div> -->
                                    </div>
                                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-md-12">
                                            
                                            <div class="uploadFile-block">
                                                <div class="uploadFile-btn">
                                                    <a href="javascript:void(0);" target="_blank" class="btn btn-themeBlue btn-sm">Upload File</a>
                                                    <input type="file" name="Employeefile" id="Employeefile" style="display: none;">
                                                </div>
                                                <span id="file-name-display" class="text-muted d-block mt-2"></span>
                                            </div>
                                            <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                                        </div>
                                    </div>

                                    <div class="card-footer text-end ">
                                        <button type="submit" class="btn btn-theme">Submit</button>
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
                            {{-- </div>
                        </div> --}}
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
<script>
$(document).ready(function () {

    // Trigger hidden file input when styled button is clicked
    $('.uploadFile-btn a').on('click', function () {
        $('#Employeefile').click();
    });

    // Show selected file name
    $('#Employeefile').on('change', function () {
        const fileName = this.files[0] ? this.files[0].name : '';
        $('#file-name-display').text(fileName);
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
            $btn.prop('disabled', true).text('Uploading...');

            const formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.Depat_Position_Emp_Import') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    $btn.prop('disabled', false).text('Submit');
                    if (response.success) {
                        toastr.success(response.msg, 'Success', { positionClass: 'toast-bottom-right' });
                        form.reset();
                        $('#file-name-display').text('');
                        $('#import-error-section').hide();
                        $('#import-error-table tbody').empty();
                    } else {
                        toastr.error(response.msg, 'Error', { positionClass: 'toast-bottom-right' });
                    }
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
